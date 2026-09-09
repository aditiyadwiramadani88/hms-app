@php
    $transactions = $transactionDetails ?? [];
@endphp
<div class="card">
    <div class="card-header">
        <a class="btn btn-link text-decoration-none" data-bs-toggle="collapse" href="#transactionDetailCollapse" role="button">
            <i class="ri-list-check"></i> Detail Transaksi
            <span class="badge bg-info ms-1">{{ count($transactions) }} transaksi</span>
        </a>
    </div>
    <div class="collapse" id="transactionDetailCollapse">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kamar</th>
                            <th>Deskripsi</th>
                            <th>Tamu</th>
                            <th>Pemasukan Room</th>
                            <th>Pemasukan Lain</th>
                            <th>Pengeluaran</th>
                            <th>Cara Bayar</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                        <tr>
                            <td>{{ $trx['no'] ?? $loop->iteration }}</td>
                            <td>{{ $trx['room'] ?? '-' }}</td>
                            <td>{{ $trx['description'] ?? '-' }}</td>
                            <td>{{ $trx['guest_name'] ?? '-' }}</td>
                            <td class="text-end">{{ ($trx['pemasukan_room'] ?? 0) > 0 ? number_format($trx['pemasukan_room'], 0, ',', '.') : '-' }}</td>
                            <td class="text-end">{{ ($trx['pemasukan_lain'] ?? 0) > 0 ? number_format($trx['pemasukan_lain'], 0, ',', '.') : '-' }}</td>
                            <td class="text-end text-danger">{{ ($trx['pengeluaran'] ?? 0) > 0 ? number_format($trx['pengeluaran'], 0, ',', '.') : '-' }}</td>
                            <td>{{ strtoupper($trx['payment_method'] ?? '-') }}</td>
                            <td>{{ $trx['time'] ?? '' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">Tidak ada transaksi pada shift ini</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
