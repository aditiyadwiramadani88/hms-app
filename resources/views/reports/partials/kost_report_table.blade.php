{{-- Summary Widgets --}}
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card bg-primary-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-primary fs-12 mb-1">Periode</p>
                <h4 class="mb-0 text-primary">{{ $month->translatedFormat('F Y') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-info fs-12 mb-1">Total Penghuni</p>
                <h4 class="mb-0 text-info">{{ count($reportData) }} orang</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-success fs-12 mb-1">Total Harga Kost</p>
                <h4 class="mb-0 text-success">Rp {{ number_format($totals['harga_kost'], 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning-subtle border-0">
            <div class="card-body p-3">
                <p class="text-uppercase fw-medium text-warning fs-12 mb-1">Total Jaminan</p>
                <h4 class="mb-0 text-warning">Rp {{ number_format($totals['jaminan'], 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Main Table --}}
<div class="table-responsive table-card">
    <table class="table table-bordered table-nowrap align-middle mb-0 table-sm">
        <thead class="table-dark text-center">
            <tr>
                <th rowspan="2" class="align-middle" style="width: 40px;">NO</th>
                <th rowspan="2" class="align-middle">NAMA</th>
                <th rowspan="2" class="align-middle">TYPE</th>
                <th rowspan="2" class="align-middle">ROOM</th>
                <th rowspan="2" class="align-middle" style="width: 40px;">QTY</th>
                <th colspan="2" class="text-center">PERIODE</th>
                <th rowspan="2" class="align-middle">HARGA KOST</th>
                <th rowspan="2" class="align-middle">JAMINAN</th>
                <th rowspan="2" class="align-middle">TGL BAYAR</th>
                <th colspan="{{ count($bankAccounts) }}" class="text-center">PAYMENT</th>
            </tr>
            <tr>
                <th>IN</th>
                <th>OUT</th>
                @foreach($bankAccounts as $account)
                    <th>{{ strtoupper($account->name) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($reportData as $row)
            <tr>
                <td class="text-center">{{ $row['no'] }}</td>
                <td>{{ $row['nama'] }}</td>
                <td class="text-center">
                    <span class="badge bg-secondary-subtle text-secondary">{{ $row['type'] }}</span>
                </td>
                <td class="text-center fw-medium">{{ $row['room'] }}</td>
                <td class="text-center">{{ $row['qty'] }}</td>
                <td class="text-center text-nowrap">{{ $row['check_in'] }}</td>
                <td class="text-center text-nowrap">{{ $row['check_out'] }}</td>
                <td class="text-end">Rp {{ number_format($row['harga_kost'], 0, ',', '.') }}</td>
                <td class="text-end">
                    @if($row['jaminan'] > 0)
                        Rp {{ number_format($row['jaminan'], 0, ',', '.') }}
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-center text-nowrap">{{ $row['tgl_bayar'] ?: '-' }}</td>
                @foreach($bankAccounts as $account)
                    <td class="text-end">
                        @if(($row['payments_by_account'][$account->id] ?? 0) > 0)
                            <span class="text-success fw-medium">Rp {{ number_format($row['payments_by_account'][$account->id], 0, ',', '.') }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ 10 + count($bankAccounts) }}" class="text-center py-4">
                    Tidak ada data penghuni kost untuk periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($reportData) > 0)
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="7" class="text-end">TOTAL:</td>
                <td class="text-end">Rp {{ number_format($totals['harga_kost'], 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($totals['jaminan'], 0, ',', '.') }}</td>
                <td></td>
                @foreach($bankAccounts as $account)
                    <td class="text-end">
                        @if(($totals['payments'][$account->id] ?? 0) > 0)
                            Rp {{ number_format($totals['payments'][$account->id], 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                @endforeach
            </tr>
        </tfoot>
        @endif
    </table>
</div>
