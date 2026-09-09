<div class="table-responsive">
    <table class="table table-nowrap align-middle table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" style="width: 50px;">No</th>
                <th class="text-center" style="width: 80px;">Kamar</th>
                <th class="text-center" style="width: 50px;">Isi</th>
                <th>Nama Penghuni</th>
                <th>No Handphone</th>
                <th>Nama Ortu</th>
                <th>Handphone Ortu</th>
                <th class="text-end" style="width: 120px;">Harga</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenantData as $row)
            <tr>
                <td class="text-center">{{ $row['no'] }}</td>
                <td class="text-center fw-bold">{{ $row['kamar'] }}</td>
                <td class="text-center">{{ $row['isi'] }}</td>
                <td>{{ $row['nama_penghuni'] }}</td>
                <td>{{ $row['no_handphone'] }}</td>
                <td>{{ $row['nama_ortu'] }}</td>
                <td>{{ $row['handphone_ortu'] }}</td>
                <td class="text-end">
                    @if($row['harga'] > 0)
                        {{ number_format($row['harga'], 0, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                        <i class="ri-building-2-line fs-1 d-block mb-2"></i>
                        Tidak ada data penghuni kost.
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($tenantData) > 0)
        @php
            $totalIsi = collect($tenantData)->sum('isi');
            $totalPenghuni = collect($tenantData)->filter(fn($r) => $r['nama_penghuni'] !== '-')->count();
            $totalHarga = collect($tenantData)->sum('harga');
        @endphp
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="2" class="text-end">TOTAL</td>
                <td class="text-center">{{ $totalIsi }}</td>
                <td>{{ $totalPenghuni }} penghuni</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-end">Rp {{ number_format($totalHarga, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
