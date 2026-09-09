@extends('layouts.master')
@section('title')
    Laporan Per Shift
@endsection
@section('content')
    <x-breadcrumb title="Laporan Per Shift" :links="[['label' => 'Reports', 'url' => route('reports.index')], ['label' => 'Laporan Bulanan', 'url' => route('reports.monthly', ['month' => $date->format('Y-m')])], ['label' => 'Laporan Harian', 'url' => route('reports.daily', ['date' => $date->format('Y-m-d')])]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-time-line me-2 text-primary"></i>
                        LAPORAN PER SHIFT
                        @if($selectedShift)
                            — <span class="text-primary">{{ strtoupper($selectedShift->name) }}</span>
                        @endif
                    </h4>
                    <div class="flex-shrink-0">
                        @if($selectedShift)
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#shiftExpenseModal">
                            <i class="ri-money-dollar-circle-line align-bottom me-1"></i> Catat Pengeluaran
                        </button>
                        <a href="{{ route('reports.shift.export.pdf', ['date' => $date->format('Y-m-d'), 'shift_id' => $selectedShift->id]) }}" class="btn btn-danger btn-sm">
                            <i class="ri-file-pdf-line align-bottom me-1"></i> Export PDF
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.shift') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}">
                            </div>
                            <div class="col-xxl-3 col-sm-4">
                                <label class="form-label">Shift</label>
                                <select name="shift_id" class="form-select">
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ $selectedShift && $selectedShift->id === $shift->id ? 'selected' : '' }}>
                                            {{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-3 col-sm-4 d-flex align-items-end">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Tampilkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body pt-3">
                    {{-- Shift Info --}}
                    @if($selectedShift)
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-primary-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-primary fs-12 mb-1">Shift</p>
                                    <h5 class="mb-0 text-primary">{{ $selectedShift->name }} ({{ \Carbon\Carbon::parse($selectedShift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($selectedShift->end_time)->format('H:i') }})</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-info fs-12 mb-1">Tanggal</p>
                                    <h5 class="mb-0 text-info">{{ $date->translatedFormat('l, d F Y') }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-secondary-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-secondary fs-12 mb-1">Karyawan Bertugas</p>
                                    <h5 class="mb-0 text-secondary">
                                        @if($employeesOnShift->count() > 0)
                                            {{ $employeesOnShift->pluck('name')->join(', ') }}
                                        @else
                                            <span class="text-muted">Belum ada jadwal</span>
                                        @endif
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-success-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-success fs-12 mb-1">Pemasukan Room</p>
                                    <h4 class="mb-0 text-success">Rp {{ number_format($totals['pemasukan_room'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-info fs-12 mb-1">Pemasukan Lain</p>
                                    <h4 class="mb-0 text-info">Rp {{ number_format($totals['pemasukan_lain'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger-subtle border-0">
                                <div class="card-body p-3">
                                    <p class="text-uppercase fw-medium text-danger fs-12 mb-1">Pengeluaran</p>
                                    <h4 class="mb-0 text-danger">Rp {{ number_format($totals['pengeluaran'], 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Serah Terima Diterima --}}
                    @if($confirmedHandovers->count() > 0)
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning-subtle">
                            <h6 class="card-title mb-0"><i class="ri-exchange-line align-middle me-1"></i> Serah Terima Diterima (dari shift sebelumnya)</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Dari Shift</th>
                                            <th>Petugas Keluar</th>
                                            <th class="text-end">Uang Diserahkan</th>
                                            <th>Dikonfirmasi</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($confirmedHandovers as $handover)
                                        <tr>
                                            <td>{{ $handover->outgoingShift->name ?? '-' }}</td>
                                            <td>{{ $handover->outgoingEmployee->name ?? '-' }}</td>
                                            <td class="text-end">Rp {{ number_format($handover->cash_amount, 0, ',', '.') }}</td>
                                            <td>{{ $handover->confirmed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                            <td>{{ $handover->notes ?? '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Main Table --}}
                    <div class="table-responsive table-card">
                        <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2" class="align-middle" style="width: 40px;">NO</th>
                                    <th rowspan="2" class="align-middle">ROOM</th>
                                    <th rowspan="2" class="align-middle">HARI</th>
                                    <th colspan="3" class="text-center">PEMASUKAN</th>
                                    <th rowspan="2" class="align-middle">PENGELUARAN</th>
                                    <th rowspan="2" class="align-middle">KETERANGAN</th>
                                    <th rowspan="2" class="align-middle">CARA BAYAR</th>
                                    <th rowspan="2" class="align-middle">P/M</th>
                                </tr>
                                <tr>
                                    <th>ROOM (TF)</th>
                                    <th>ROOM (CASH)</th>
                                    <th>LAIN 2</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData as $row)
                                <tr>
                                    <td class="text-center">{{ $row['no'] }}</td>
                                    <td class="text-center fw-medium">{{ $row['room'] }}</td>
                                    <td class="text-center">{{ $row['hari'] }}</td>
                                    <td class="text-end">
                                        @if($row['pemasukan_room_transfer'] > 0)
                                            <span class="text-success">Rp {{ number_format($row['pemasukan_room_transfer'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($row['pemasukan_room_cash'] > 0)
                                            <span class="text-success">Rp {{ number_format($row['pemasukan_room_cash'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($row['pemasukan_lain'] > 0)
                                            <span class="text-info">Rp {{ number_format($row['pemasukan_lain'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($row['pengeluaran'] > 0)
                                            <span class="text-danger">Rp {{ number_format($row['pengeluaran'], 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 200px; white-space: normal;">{{ Str::limit($row['description'] ?? '-', 60) }}</td>
                                    <td class="text-center">
                                        @if($row['payment_method'])
                                            <span class="badge bg-light text-dark">{{ strtoupper($row['payment_method']) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">
                                        @if($row['pengeluaran'] > 0)
                                            <span class="badge bg-danger">M</span>
                                        @else
                                            <span class="badge bg-success">P</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ri-time-line fs-1 d-block mb-2"></i>
                                            Tidak ada transaksi pada shift ini.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($reportData) > 0)
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTAL</td>
                                    <td class="text-end text-success">Rp {{ number_format($totals['pemasukan_room_transfer'], 0, ',', '.') }}</td>
                                    <td class="text-end text-success">Rp {{ number_format($totals['pemasukan_room_cash'], 0, ',', '.') }}</td>
                                    <td class="text-end text-info">Rp {{ number_format($totals['pemasukan_lain'], 0, ',', '.') }}</td>
                                    <td class="text-end text-danger">Rp {{ number_format($totals['pengeluaran'], 0, ',', '.') }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    {{-- Summary Footer (UANG MASUK, UANG KELUAR, SISA, PETUGAS) --}}
                    @if(count($reportData) > 0)
                    <div class="mt-3">
                        <table class="table table-bordered mb-0" style="max-width: 400px;">
                            <tbody>
                                <tr>
                                    <td class="fw-bold bg-light" style="width: 150px;">UANG MASUK</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($totals['pemasukan_room'] + $totals['pemasukan_lain'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold bg-light">UANG KELUAR</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($totals['pengeluaran'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="table-dark">
                                    <td class="fw-bold">SISA</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($totals['pemasukan_room'] + $totals['pemasukan_lain'] - $totals['pengeluaran'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold bg-light">PETUGAS</td>
                                    <td class="fw-medium">
                                        @if($employeesOnShift->count() > 0)
                                            {{ $employeesOnShift->pluck('name')->join(', ') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($selectedShift)
    <div class="modal fade" id="shiftExpenseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title text-danger">Catat Pengeluaran Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('reports.shift.expense') }}" method="POST">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                    <input type="hidden" name="shift_id" value="{{ $selectedShift->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category_id" required>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="amount" placeholder="0" min="1" required>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Detail pengeluaran..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-danger">Simpan Pengeluaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection
