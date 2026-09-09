@extends('layouts.master')
@section('title')
    Laporan Bonus Karyawan
@endsection
@section('css')
<style>
    .table-grid {
        font-size: 11px;
    }
    .table-grid th, .table-grid td {
        vertical-align: middle;
        padding: 0.3rem 0.2rem;
        border: 1px solid #ced4da;
    }
    .table-grid th {
        background-color: #f3f6f9;
        font-weight: 700;
        text-align: center;
        white-space: nowrap;
    }
    .val-col {
        text-align: right;
        white-space: nowrap;
        cursor: pointer;
    }
    .center-col {
        text-align: center;
    }
    .total-row {
        font-weight: bold;
        background-color: #e9ecef;
    }
    .sticky-left {
        position: sticky;
        left: 0;
        background-color: #f3f6f9;
        z-index: 1;
        font-weight: bold;
        text-align: center;
    }
</style>
@endsection
@section('content')
    <x-breadcrumb title="Laporan Bonus Karyawan" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-table-line me-2 text-primary"></i>
                        Laporan Harian (Grid) - Bulan {{ $month->translatedFormat('F Y') }}
                    </h4>
                </div>

                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Pilih Bulan</label>
                                <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-grid mb-0">
                            <thead>
                                <tr>
                                    <th class="sticky-left">TGL</th>
                                    <th>SALES</th>
                                    <th>KOST</th>
                                    <th>AGODA</th>
                                    <th>REDDOORS</th>
                                    <th>TRAVELOKA</th>
                                    <th>UMUM</th>
                                    <th class="bg-light">TOTAL</th>
                                    <th>KANTIN</th>
                                    <th>OVERTIME</th>
                                    <th>PIJAT</th>
                                    <th>MOTOR</th>
                                    <th>LAUNDRY</th>
                                    <th>BED HS 1</th>
                                    <th>BED HS 2</th>
                                    <th>HANDUK/KIPAS</th>
                                    <th>TAMU</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 1; $i <= $daysInMonth; $i++)
                                <tr>
                                    <td class="sticky-left">{{ $i }}</td>
                                    <td class="val-col">{{ $grid[$i]['sales'] > 0 ? 'Rp '.number_format($grid[$i]['sales'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['kost'] > 0 ? 'Rp '.number_format($grid[$i]['kost'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['agoda'] > 0 ? 'Rp '.number_format($grid[$i]['agoda'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['reddoors'] > 0 ? 'Rp '.number_format($grid[$i]['reddoors'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['traveloka'] > 0 ? 'Rp '.number_format($grid[$i]['traveloka'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['umum'] > 0 ? 'Rp '.number_format($grid[$i]['umum'], 0, ',', '.') : '-' }}</td>
                                    
                                    <td class="val-col bg-light fw-bold">{{ $grid[$i]['total_kamar'] > 0 ? 'Rp '.number_format($grid[$i]['total_kamar'], 0, ',', '.') : '-' }}</td>
                                    
                                    <td class="val-col">{{ $grid[$i]['kantin'] > 0 ? 'Rp '.number_format($grid[$i]['kantin'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['overtime'] > 0 ? 'Rp '.number_format($grid[$i]['overtime'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['pijat'] > 0 ? 'Rp '.number_format($grid[$i]['pijat'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['motor'] > 0 ? 'Rp '.number_format($grid[$i]['motor'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['laundry'] > 0 ? 'Rp '.number_format($grid[$i]['laundry'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['bed_hs_1'] > 0 ? 'Rp '.number_format($grid[$i]['bed_hs_1'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['bed_hs_2'] > 0 ? 'Rp '.number_format($grid[$i]['bed_hs_2'], 0, ',', '.') : '-' }}</td>
                                    <td class="val-col">{{ $grid[$i]['handuk_kipas'] > 0 ? 'Rp '.number_format($grid[$i]['handuk_kipas'], 0, ',', '.') : '-' }}</td>
                                    
                                    <td class="center-col">{{ $grid[$i]['tamu'] > 0 ? $grid[$i]['tamu'] : '-' }}</td>
                                </tr>
                                @endfor
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td class="sticky-left">#</td>
                                    <td class="val-col">Rp {{ number_format($totals['sales'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['kost'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['agoda'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['reddoors'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['traveloka'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['umum'], 0, ',', '.') }}</td>
                                    
                                    <td class="val-col bg-light">Rp {{ number_format($totals['total_kamar'], 0, ',', '.') }}</td>
                                    
                                    <td class="val-col">Rp {{ number_format($totals['kantin'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['overtime'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['pijat'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['motor'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['laundry'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['bed_hs_1'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['bed_hs_2'], 0, ',', '.') }}</td>
                                    <td class="val-col">Rp {{ number_format($totals['handuk_kipas'], 0, ',', '.') }}</td>
                                    
                                    <td class="center-col">{{ number_format($totals['tamu'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4">
                        <div class="alert alert-info bg-info-subtle text-info border-0">
                            <strong>Catatan Sistem Pembacaan Data:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Kamar:</strong> Dihitung berdasarkan tanggal <em>Check-In</em>. Booking Online (Agoda, RedDoorz, Traveloka, Booking.com) akan otomatis dikelompokkan ke kolomnya.</li>
                                <li><strong>Layanan Tambahan (Kantin, Pijat, Laundry, dll):</strong> Dibaca secara otomatis dari <em>Menu POS</em> atau dari <em>Deskripsi Transaksi</em> (Extra Charge) yang diinputkan oleh kasir pada hari tersebut.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="bonusDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="bonusDetailTitle">Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="bonusDetailTable">
                            <thead id="bonusDetailHead"></thead>
                            <tbody id="bonusDetailBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    const detailData = @json($details);
    const columnLabels = {
        sales: 'SALES', kost: 'KOST', agoda: 'AGODA', reddoors: 'REDDOORS', traveloka: 'TRAVELOKA', umum: 'UMUM',
        kantin: 'KANTIN', overtime: 'OVERTIME', pijat: 'PIJAT', motor: 'MOTOR', laundry: 'LAUNDRY',
        bed_hs_1: 'BED HS 1', bed_hs_2: 'BED HS 2', handuk_kipas: 'HANDUK/KIPAS'
    };

    document.querySelectorAll('tbody .val-col').forEach(cell => {
        cell.addEventListener('click', function() {
            const row = this.closest('tr');
            const day = parseInt(row.cells[0].textContent.trim());
            if (!day || !detailData[day]) return;

            const colIdx = Array.from(row.cells).indexOf(this);
            const keys = ['_','sales','kost','agoda','reddoors','traveloka','umum','_','kantin','overtime','pijat','motor','laundry','bed_hs_1','bed_hs_2','handuk_kipas'];
            const key = keys[colIdx];
            if (!key || key === '_' || !detailData[day][key]) return;

            const items = detailData[day][key];
            document.getElementById('bonusDetailTitle').textContent = `${columnLabels[key] || key.toUpperCase()} — Tgl ${day}`;

            const isBooking = ['sales','kost','agoda','reddoors','traveloka','umum'].includes(key);
            document.getElementById('bonusDetailHead').innerHTML = isBooking
                ? '<tr><th>Booking ID</th><th>Room</th><th>Guest</th><th class="text-end">Nominal</th></tr>'
                : '<tr><th>Keterangan</th><th class="text-end">Nominal</th></tr>';

            document.getElementById('bonusDetailBody').innerHTML = items.map(item => isBooking
                ? `<tr><td>#${item.id}</td><td>${item.room}</td><td>${item.guest}</td><td class="text-end">Rp ${item.amount.toLocaleString('id-ID')}</td></tr>`
                : `<tr><td>${item.desc}</td><td class="text-end">Rp ${item.amount.toLocaleString('id-ID')}</td></tr>`
            ).join('');

            new bootstrap.Modal(document.getElementById('bonusDetailModal')).show();
        });
    });
</script>
@endsection
