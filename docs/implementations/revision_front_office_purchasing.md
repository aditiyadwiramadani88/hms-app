# Dokumentasi Implementasi: Perbaikan Revisi Front Office & Purchasing

**Tanggal**: 10 September 2026  
**Aplikasi**: Hotel Management System (HMS) - Laravel 12  
**Konteks**: Legacy Production System (Perubahan bersifat Non-Destruktif / Aman untuk Data Production)

---

## 1. Ringkasan & Prinsip Keamanan Data Produksi

Sistem ini merupakan aplikasi *legacy* yang sudah berjalan di lingkungan *production*. Seluruh perbaikan yang diimplementasikan berpegang pada prinsip berikut:
- **Zero Schema Destruction**: Tidak ada perubahan struktur tabel, penghapusan kolom (*drop column*), atau reset tabel yang dapat merusak data lama.
- **Backward Compatibility**: Logika baru tetap kompatibel dengan format data reservasi, invoice, transaksi, dan riwayat mutasi barang lama.
- **Data Integrity**: Transaksi lama tidak diubah secara sepihak. Perhitungan baru hanya aktif saat user memicu aksi baru (misal: perpanjangan baru, transfer kamar baru, atau PO baru).

---

## 2. Rincian Perbaikan & File yang Diubah

### A. Front Office: Tombol "Add Custom Invoice" pada List Booking
- **Masalah**: Hak akses (*permission*) untuk membuat custom invoice sudah ada, namun di halaman daftar booking (`/bookings`) hanya terdapat tombol ke daftar invoice (`Custom Invoice`) dan `New Booking`. Tombol cepat untuk tambah invoice kustom langsung belum tersedia.
- **Solusi**: Menambahkan tombol `Add Custom Invoice` pada action header yang dilindungi oleh permission check `@canany(['custom-invoices.create', 'manage reservations'])`.
- **File Diubah**:
  - `resources/views/bookings/index.blade.php`

---

### B. Front Office: Pindah Kamar + Perpanjang (Room Transfer), Total Tagihan Masuk ke Remaining Balance
- **Masalah**:
  - Di `RoomTransferService::previewTransfer()`, perhitungan selisih harga hanya memperhitungkan perbedaan tarif per malam untuk sisa malam lama (`$perNightDifference * $remainingNights`). Jika tamu sekaligus memperpanjang tanggal checkout (`new_check_out`), malam tambahan tersebut tidak dihitung ke biaya total baru.
  - Jika tipe kamar tujuannya bertarif sama, sistem menganggap tidak ada selisih biaya (`price_difference = 0`), sehingga durasi malam perpanjangan menjadi gratis dan tidak tercermin pada sisa tagihan (*remaining balance*).
  - Di modal tampilan `resources/views/bookings/show.blade.php`, input `#transfer_new_checkout` belum dikirimkan ke parameter AJAX preview.
- **Solusi**:
  - Memperbarui `RoomTransferService::previewTransfer()` dan `RoomTransferController::preview()` agar menerima parameter opsional `$newCheckOut`.
  - Mengubah formula selisih harga menjadi selisih total tagihan nyata: `$priceDifference = $newTotal - $oldTotal`.
  - Memastikan jika ada penambahan malam baru (`$hasExtension`), status tagihan dan transaksi referensi `BOOK-xxx` langsung diperbarui ke `$newTotalPrice`, sehingga *remaining balance* booking langsung terupdate akurat.
  - Menghubungkan event `change` pada input tanggal checkout di modal transfer agar otomatis merefresh kalkulasi preview harga.
- **File Diubah**:
  - `app/Services/RoomTransferService.php`
  - `app/Http/Controllers/RoomTransferController.php`
  - `resources/views/bookings/show.blade.php`

---

### C. Front Office: Ketersediaan Kamar Pasca-Checkout untuk Tanggal Berikutnya
- **Masalah**:
  - Pada `BookingService::getAvailableRooms()`, terdapat filter status fisik kamar: `$query->whereIn('status', ['Available', 'Clean', ...])`.
  - Ketika tamu melakukan checkout, status kamar berubah menjadi `'Checkout'` atau `'dirty'`.
  - Akibat filter status fisik tersebut, kamar yang berstatus `'Checkout'` langsung diabaikan dari hasil pencarian ketersediaan, sekalipun user mencari ketersediaan kamar untuk tanggal besok atau minggu depan.
- **Solusi**:
  - Mengubah filter ketersediaan fisik agar hanya mengecualikan kamar yang benar-benar rusak/tidak dapat dijual (`whereNotIn('status', ['Maintenance', 'maintenance', 'Out of Order', 'out_of_order', 'Closed', 'closed'])`).
  - Kamar dengan status `'Checkout'`, `'dirty'`, `'clean'`, `'In-House'` tetap dievaluasi berdasarkan jadwal reservasi (*booking overlap check*). Karena booking lama yang sudah checkout tidak lagi berstatus aktif (`pending/confirmed/checked_in`), kamar tersebut secara otomatis tampil sebagai kamar yang tersedia untuk tanggal berikutnya.
- **File Diubah**:
  - `app/Services/BookingService.php`

---

### D. Front Office: Validasi Tabrakan (Collision) Perpanjangan Kamar
- **Masalah**:
  - Pada `BookingController::processExtend()`, sistem hanya memvalidasi bahwa tanggal checkout baru lebih besar dari checkout saat ini. Tidak ada pengecekan apakah kamar tersebut sudah dipesan oleh tamu lain pada rentang tanggal perpanjangan.
  - Akibatnya, terjadi *double booking* pada kamar yang sama.
- **Solusi**:
  - Menambahkan query proteksi overlap pada `BookingController::processExtend()`:
    ```php
    $conflictBooking = Booking::where("room_id", $booking->room_id)
        ->where("id", "!=", $booking->id)
        ->whereNotIn("status", ["cancelled", "no_show", "checked_out"])
        ->where(function ($q) use ($currentCheckOut, $newCheckOut) {
            $q->whereDate("check_in", "<", $newCheckOut)
              ->whereDate("check_out", ">", $currentCheckOut);
        })
        ->first();
    ```
    Jika ditemukan reservasi yang bertabrakan, proses dibatalkan dengan notifikasi error yang mencantumkan nomor booking dan nama tamu yang sudah memesan.
  - Pada halaman `bookings.extend`, sistem mendeteksi booking berikutnya terdekat (`$nextBooking`) dan memasang batas maksimal pada tanggal input (`max="{{ $maxNewCheckOut }}"`). Jika tanggal checkout saat ini langsung berhimpitan dengan check-in berikutnya, tombol perpanjang dinonaktifkan dan user diarahkan menggunakan fitur *Pindah Kamar (Room Transfer)*.
- **File Diubah**:
  - `app/Http/Controllers/BookingController.php`
  - `resources/views/bookings/extend.blade.php`

---

### E. Purchasing: Alur Pembuatan Purchase Order, Item & Unit Price
- **Masalah**:
  - Di `PurchaseController::searchInventory()`, pencarian item mewajibkan parameter teks dan hanya membaca `price_per_unit` (yang pada inventaris bernilai 0 jika belum ada penjualan, sementara harga beli tersimpan di `purchase_price`).
  - Di `resources/views/purchases/create.blade.php`:
    - Fungsi `updateTotal()` membaca teks dari DOM yang sudah diformat rupiah (`parseRupiah($(this).text())`). Pada format sistem/browser tertentu (misal pemisah ribuan koma/titik), parsing regex ini merusak angka (misal Rp 50.000 menjadi 50).
    - Tidak ada validasi *client-side* jika user mengklik Simpan saat tabel item masih kosong.
    - Form menggunakan `data-ajax="true"` tanpa atribut `data-ajax-redirect`, sehingga setelah submit sukses halaman tidak otomatis berpindah ke daftar purchase order.
  - Di `PurchaseController::store()`, `hotel_id` belum diisi secara eksplisit pada model Purchase.
- **Solusi**:
  - `searchInventory()` diperbaiki untuk mendukung pencarian dengan atau tanpa term (menampilkan daftar 30 item teratas saat dibuka), mengambil harga beli yang tepat (`purchase_price > 0 ? purchase_price : price_per_unit`), serta penanganan hotel aktif.
  - `updateTotal()` diubah menjadi kalkulasi langsung dari nilai input numerik baris (`qty * price`), aman dari *locale formatting bug*.
  - Menambahkan validasi minimal 1 item sebelum submit.
  - Menambahkan `data-ajax-redirect="{{ route('purchases.index') }}"` dan menyertakan payload URL redirect pada controller response.
  - Memastikan `Purchase::create()` menyimpan `hotel_id`.
- **File Diubah**:
  - `app/Http/Controllers/PurchaseController.php`
  - `resources/views/purchases/create.blade.php`

---

## 3. Panduan Penerapan ke Server Production (Safe Deployment Guide)

Ketika Anda siap menerapkan perubahan ini ke server *production*, lakukan langkah-langkah berikut:

1. **Backup File & Database Terlebih Dahulu** (sebagai *best practice* standar):
   ```bash
   mysqldump -u <user> -p <database_name> > backup_before_patch.sql
   ```
2. **Terapkan File yang Diperbaiki**:
   File-file yang dimodifikasi hanyalah file Controller, Service, dan Blade:
   - `resources/views/bookings/index.blade.php`
   - `resources/views/bookings/show.blade.php`
   - `resources/views/bookings/extend.blade.php`
   - `resources/views/purchases/create.blade.php`
   - `app/Http/Controllers/BookingController.php`
   - `app/Http/Controllers/RoomTransferController.php`
   - `app/Http/Controllers/PurchaseController.php`
   - `app/Services/BookingService.php`
   - `app/Services/RoomTransferService.php`
3. **Bersihkan Cache Laravel**:
   ```bash
   php artisan view:clear
   php artisan route:clear
   php artisan config:clear
   ```
4. **Catatan Penting**:
   - **TIDAK PERLU** menjalankan `php artisan migrate:fresh` atau perintah database destruktif apapun.
   - Data riwayat tamu, booking lama, transaksi, dan inventaris di server *production* akan tetap aman 100% dan tidak mengalami perubahan struktur.

---

### F. Front Office: Pindah Kamar — Validasi Kebersihan, Tampilan Status, dan Dukungan Bahasa (i18n)

#### F.1 Validasi Kamar Kotor / Belum Dibersihkan
- **Masalah**:
  - Saat tamu ingin pindah kamar (Room Transfer), kamar tujuan yang baru saja checkout (status `Checkout`, `dirty`, `Dirty`, `Room Refresh`) maupun yang sedang ditempati (`In-House`, `Occupied`) tetap muncul di daftar pilihan dan bisa dipilih.
  - Di `RoomTransferService::transfer()`, validasi status kamar tujuan hanya memblokir status `In-House`, `Occupied`, `Maintenance`, `Out of Order`, `Closed` — tetapi **tidak** memblokir kamar kotor.
- **Solusi**:
  - **Backend** (`app/Services/RoomTransferService.php`): Menambahkan validasi tambahan yang menolak transfer ke kamar dengan status `dirty`, `Dirty`, `Checkout`, dan `Room Refresh`. Pesan error menyebutkan nomor kamar dan status, serta mengarahkan staff ke Housekeeping.
- **File Diubah**:
  - `app/Services/RoomTransferService.php`

#### F.2 Tampilan Semua Kamar dengan Badge Status
- **Masalah**: Jika kamar yang tidak tersedia disembunyikan, staff tidak tahu kenapa kamar tertentu tidak muncul (apakah kotor, ditempati, atau maintenance).
- **Solusi**:
  - **Frontend** (`resources/views/bookings/show.blade.php`): Modal Room Transfer kini menampilkan **semua kamar** dengan indikator visual yang jelas:
    - ✅ Kamar tersedia: badge **hijau** + tombol harga aktif (bisa diklik)
    - 🔴 Kamar terisi (`In-House`/`Occupied`): badge **merah** + keterangan + redup 50% (tidak bisa diklik)
    - 🟡 Kamar kotor (`Dirty`/`Checkout`): badge **kuning** + keterangan + redup 50%
    - ⚫ Kamar perbaikan (`Maintenance`/`Out of Order`): badge **hitam** + keterangan + redup 50%
    - 🔵 Kamar sudah dipesan (jadwal bentrok): badge **biru** + keterangan + redup 50%
- **File Diubah**:
  - `resources/views/bookings/show.blade.php`

#### F.3 Dukungan Bahasa (i18n) — English & Indonesia
- **Masalah**: Label status pada modal pindah kamar di-*hardcode* dalam satu bahasa, tidak mengikuti pengaturan bahasa aktif user di aplikasi (yang mendukung English dan Indonesia).
- **Solusi**:
  - Menambahkan **18 key terjemahan** baru ke file translation:
    - `resources/lang/en/translation.php` — label dalam Bahasa Inggris
    - `resources/lang/id/translation.php` — label dalam Bahasa Indonesia
  - Key yang ditambahkan meliputi: `room_status_occupied`, `room_status_dirty`, `room_status_clean`, `room_status_available`, `room_status_booked`, `room_block_occupied`, `room_block_dirty`, `room_block_maintenance`, `room_block_booked`, `room_label`, `no_rooms_found`, dll.
  - Di `show.blade.php`, terjemahan di-load melalui `@php` block + `@json()` sehingga JavaScript bisa membaca label sesuai bahasa aktif user secara otomatis.
- **File Diubah**:
  - `resources/lang/en/translation.php`
  - `resources/lang/id/translation.php`
  - `resources/views/bookings/show.blade.php`
