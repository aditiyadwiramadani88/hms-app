# Dokumentasi Perbaikan Bug Harga Booking & Grand Total

**Tanggal:** 12 September 2026
**Modul:** Booking, Invoice, Dashboard

## 1. Ringkasan Masalah
Terdapat tiga bug kritis yang memengaruhi kalkulasi harga dan tagihan (Grand Total):
1. **Harga Bulanan Salah (Fallback Bug):** Jika tamu menginap bulanan namun kamar tidak memiliki harga bulanan (`price_kos`), sistem memanggil harga harian (`price_public`) dan hanya menagih 1 malam alih-alih 30 malam.
2. **Deposit & Breakfast Pax (Daily Booking Bug):** Uang jaminan (Deposit) dimasukkan paksa ke dalam `total_price` (harga dasar sewa), membuat invoice seolah-olah harga kamarnya membengkak. Selain itu, perhitungan sarapan selalu di-*hardcode* menjadi 1 pax, mengabaikan jumlah aktual `adults + children`.
3. **Extra Charge / POS Order di PDF Invoice:** Jika ada tambahan makanan atau snack (via POS/Extra Charge) dan tamu langsung membayarnya (mengubah status menjadi *paid*), maka sisa tagihan di PDF Invoice akan menjadi *lebih rendah dari harga aslinya*. Hal ini dikarenakan Invoice PDF memanggil variabel yang salah (dan tidak pernah ada) yaitu `$booking->pos_charges` dan `$booking->additional_charges`, sehingga biaya makanan tidak tertagih di Invoice namun uang pembayarannya ikut memotong tagihan kamar.

## 2. Solusi yang Diterapkan

### A. Perbaikan Harga Bulanan (Fallback)
* **File:** `app/Services/BookingService.php` dan `app/Services/PricingService.php`
* **Cara Kerja:** Menyesuaikan blok kondisi logika. Jika `price_kos` tidak ada, maka harga dasar bulanan diambil dari `$room->price_public * 30`.

### B. Pemisahan Deposit dan Dinamisasi Breakfast
* **File:** `app/Services/BookingService.php` dan `app/Http/Controllers/BookingController.php`
* **Cara Kerja:** Menghapus penambahan `$depositAmount` ke dalam array `$priceData['total_price']`. Deposit tetap dicatat di tabel `transactions` sebagai `charge` (is_deposit = true). Rumus harga sarapan disesuaikan dengan mengalikan harga dengan total `$pax` (`adults + children`).

### C. Pembenahan PDF Invoice & Dashboard
* **File:** `resources/views/pdf/invoice.blade.php`, `resources/views/dashboard/index.blade.php`, `app/Services/BookingAuditService.php`
* **Cara Kerja:** Menghapus variabel fiktif di invoice PDF dan menggantinya dengan relasi yang benar (`$booking->posOrders->sum('total_amount')` dan `$booking->transactions`). Total tagihan (`Grand Total`) di UI (Dashboard & Web Invoice) diperbarui agar kembali menambahkan komponen `$totalDeposit` mengingat kini ia sudah dilepas dari `total_price`.

## 3. File yang Diubah
- `app/Services/PricingService.php`
- `app/Services/BookingService.php`
- `app/Services/BookingAuditService.php`
- `app/Http/Controllers/BookingController.php`
- `resources/views/pdf/invoice.blade.php`
- `resources/views/dashboard/index.blade.php`
