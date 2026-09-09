@extends('layouts.master')
@section('title')
    Mutasi Lintas Gudang
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Master Data
        @endslot
        @slot('title')
            Mutasi Lintas Gudang
        @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pindahkan Stok</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('warehouse.transfer.store') }}" method="POST">
                        @csrf
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Dari Gudang (Cabang Ini)</label>
                                <select name="from_warehouse_id" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($sourceWarehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ke Gudang (Bisa Lintas Cabang)</label>
                                <select name="to_warehouse_id" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach($allWarehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->hotel->name ?? 'Unknown Hotel' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="border rounded p-3 bg-light mb-3">
                            <h6 class="mb-3">Pilih Barang & Jumlah</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Barang</label>
                                    <select name="items[0][inventory_id]" class="form-select" required>
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($inventories as $inv)
                                            <option value="{{ $inv->id }}">{{ $inv->name }} (Stok saat ini: {{ $inv->stock }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Jumlah Pindah</label>
                                    <input type="number" name="items[0][quantity]" class="form-control" required min="1" value="1">
                                </div>
                            </div>
                            <p class="text-muted small mt-2 mb-0">*Catatan: Untuk transfer banyak barang sekaligus, UI ini bisa dikembangkan lebih lanjut dengan Javascript.</p>
                        </div>

                        <button type="submit" class="btn btn-primary" data-submit-protect="true">Lakukan Mutasi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Mutasi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Transfer</th>
                                    <th>Dari</th>
                                    <th>Ke</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $tr)
                                <tr>
                                    <td><code>{{ $tr->transfer_number }}</code></td>
                                    <td>{{ $tr->fromWarehouse->name ?? '-' }}</td>
                                    <td>{{ $tr->toWarehouse->name ?? '-' }}</td>
                                    <td>
                                        @if($tr->items->count() > 0)
                                            <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#trItems{{ $tr->id }}">
                                                {{ $tr->items->count() }} item <i class="ri-arrow-down-s-line"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tr->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($tr->status == 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($tr->status == 'in_transit')
                                            <span class="badge bg-info">In Transit</span>
                                        @else
                                            <span class="badge bg-danger">{{ $tr->status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $tr->transfer_date->format('d M Y') }}</td>
                                </tr>
                                @if($tr->items->count() > 0)
                                <tr class="collapse" id="trItems{{ $tr->id }}">
                                    <td colspan="6" class="p-0">
                                        <div class="bg-light-subtle p-2 ps-4">
                                            @foreach($tr->items as $item)
                                            <small class="d-block text-muted">
                                                • {{ $item->inventory->name ?? 'Item #'.$item->inventory_id }} — {{ $item->quantity }} pcs
                                            </small>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat transfer.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    @if(session('success'))
        Toastify({
            text: "{{ session('success') }}",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: { background: "linear-gradient(to right, #0ab39c, #405189)" }
        }).showToast();
    @endif

    @if(session('error'))
        Toastify({
            text: "{{ session('error') }}",
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            style: { background: "linear-gradient(to right, #f06548, #f7b84b)" }
        }).showToast();
    @endif

    @if($errors->any())
        @foreach($errors->all() as $error)
            Toastify({ text: "{{ $error }}", duration: 4000, close: true, gravity: "top", position: "right", stopOnFocus: true, style: { background: "linear-gradient(to right, #f06548, #f7b84b)" } }).showToast();
        @endforeach
    @endif
</script>
@endsection
