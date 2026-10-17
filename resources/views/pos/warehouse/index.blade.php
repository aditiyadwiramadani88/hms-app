@extends('layouts.master')
@section('title')
    Gudang & Etalase
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
            Gudang & Etalase
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
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Gudang / Etalase</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('warehouse.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Gudang</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: Etalase Resepsionis">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe</label>
                            <select name="type" class="form-select">
                                <option value="storage">Gudang Penyimpanan</option>
                                <option value="display">Etalase (Display)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" data-submit-protect="true">Tambah</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Gudang</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Gudang</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($warehouses as $wh)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $wh->name }}</div>
                                        <small class="text-muted">
                                            <i class="ri-archive-line me-1"></i>{{ $wh->stocks->where('stock', '>', 0)->count() }} Barang &bull; {{ number_format($wh->stocks->sum('stock'), 0, ',', '.') }} Unit
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $wh->type == 'display' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                                            {{ $wh->type == 'display' ? 'Etalase' : 'Gudang' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($wh->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#detailModal{{ $wh->id }}" title="Lihat isi list barang">
                                                <i class="ri-eye-line me-1"></i> Detail
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editModal{{ $wh->id }}">
                                                <i class="ri-pencil-line me-1"></i> Edit
                                            </button>
                                            <form action="{{ route('warehouse.destroy', $wh->id) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" data-submit-protect="true" onclick="return confirm('Yakin hapus gudang ini?')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Modal Detail Barang di Gudang / Etalase --}}
                                <div class="modal fade" id="detailModal{{ $wh->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $wh->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header bg-light py-3 border-bottom">
                                                <div>
                                                    <h5 class="modal-title fw-bold mb-1" id="detailModalLabel{{ $wh->id }}">
                                                        <i class="ri-store-2-line text-primary me-2"></i> Detail Isi Barang: {{ $wh->name }}
                                                    </h5>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge {{ $wh->type == 'display' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                                                            {{ $wh->type == 'display' ? 'Etalase (Display)' : 'Gudang Penyimpanan' }}
                                                        </span>
                                                        @if($wh->is_active)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                @php
                                                    $availableStocks = $wh->stocks->where('stock', '>', 0);
                                                    $totalItems = $availableStocks->count();
                                                    $totalQuantity = $availableStocks->sum('stock');
                                                    $totalSellValue = $availableStocks->sum(fn($s) => $s->stock * ($s->inventory->price_per_unit ?? 0));
                                                    $totalCostValue = $availableStocks->sum(fn($s) => $s->stock * ($s->inventory->purchase_price ?? 0));
                                                @endphp

                                                {{-- KPI Cards --}}
                                                <div class="row g-3 mb-4">
                                                    <div class="col-sm-6 col-xl-3">
                                                        <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 mb-0 h-100">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar-sm flex-shrink-0">
                                                                        <span class="avatar-title bg-primary text-white rounded-circle fs-4">
                                                                            <i class="ri-archive-line"></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Jenis Barang</p>
                                                                        <h4 class="mb-0 fw-bold text-primary">{{ number_format($totalItems) }} <span class="fs-13 fw-normal text-muted">Item</span></h4>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-xl-3">
                                                        <div class="card border border-success-subtle bg-success-subtle bg-opacity-10 mb-0 h-100">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar-sm flex-shrink-0">
                                                                        <span class="avatar-title bg-success text-white rounded-circle fs-4">
                                                                            <i class="ri-stack-line"></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Total Kuantitas</p>
                                                                        <h4 class="mb-0 fw-bold text-success">{{ number_format($totalQuantity) }} <span class="fs-13 fw-normal text-muted">Unit</span></h4>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-xl-3">
                                                        <div class="card border border-info-subtle bg-info-subtle bg-opacity-10 mb-0 h-100">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar-sm flex-shrink-0">
                                                                        <span class="avatar-title bg-info text-white rounded-circle fs-4">
                                                                            <i class="ri-money-dollar-circle-line"></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Estimasi Nilai Jual</p>
                                                                        <h4 class="mb-0 fw-bold text-info fs-16">Rp {{ number_format($totalSellValue, 0, ',', '.') }}</h4>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 col-xl-3">
                                                        <div class="card border border-warning-subtle bg-warning-subtle bg-opacity-10 mb-0 h-100">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar-sm flex-shrink-0">
                                                                        <span class="avatar-title bg-warning text-white rounded-circle fs-4">
                                                                            <i class="ri-shopping-cart-line"></i>
                                                                        </span>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Estimasi Modal/Beli</p>
                                                                        <h4 class="mb-0 fw-bold text-warning fs-16">Rp {{ number_format($totalCostValue, 0, ',', '.') }}</h4>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Table Header with Search Bar --}}
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                                    <h6 class="fs-14 fw-bold mb-0">
                                                        <i class="ri-list-check-2 me-1 text-primary"></i> Daftar Barang di {{ $wh->name }}
                                                    </h6>
                                                    <div class="d-flex gap-2">
                                                        <div class="input-group input-group-sm" style="width: 280px;">
                                                            <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                                                            <input type="text" class="form-control form-control-sm border-start-0 search-item-input" data-target="table-wh-{{ $wh->id }}" placeholder="Cari nama barang atau kategori...">
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Items Table --}}
                                                <div class="table-responsive border rounded">
                                                    <table class="table table-hover align-middle mb-0" id="table-wh-{{ $wh->id }}">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 50px;">#</th>
                                                                <th>Nama Barang</th>
                                                                <th>Kategori</th>
                                                                <th class="text-end">Harga Beli</th>
                                                                <th class="text-end">Harga Jual</th>
                                                                <th class="text-center">Stok di Sini</th>
                                                                <th>Status</th>
                                                                <th class="text-center" style="width: 110px;">Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($wh->stocks->sortByDesc('stock') as $stock)
                                                                @php
                                                                    $inv = $stock->inventory;
                                                                    if (!$inv) continue;
                                                                @endphp
                                                                <tr class="item-row">
                                                                    <td>{{ $loop->iteration }}</td>
                                                                    <td>
                                                                        <span class="fw-semibold text-dark item-name">{{ $inv->name }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-secondary-subtle text-secondary border item-category">
                                                                            {{ $inv->category ?? 'General' }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end text-muted">
                                                                        Rp {{ number_format($inv->purchase_price ?? 0, 0, ',', '.') }}
                                                                    </td>
                                                                    <td class="text-end fw-semibold text-primary">
                                                                        Rp {{ number_format($inv->price_per_unit ?? 0, 0, ',', '.') }}
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="fs-14 fw-bold {{ $stock->stock <= 0 ? 'text-danger' : ($stock->stock <= ($inv->min_stock ?? 0) ? 'text-warning' : 'text-success') }}">
                                                                            {{ number_format($stock->stock) }}
                                                                        </span>
                                                                        <span class="text-muted fs-12">{{ $inv->unit ?? 'Pcs' }}</span>
                                                                    </td>
                                                                    <td>
                                                                        @if($stock->stock <= 0)
                                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Habis</span>
                                                                        @elseif($stock->stock <= ($inv->min_stock ?? 0))
                                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Stok Menipis</span>
                                                                        @else
                                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Tersedia</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <a href="{{ route('inventory.show', $inv->id) }}" class="btn btn-sm btn-soft-primary" title="Lihat Riwayat & Mutasi Barang" target="_blank">
                                                                            <i class="ri-exchange-line me-1"></i> Mutasi
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="8" class="text-center py-5 text-muted">
                                                                        <div class="avatar-md mx-auto mb-3">
                                                                            <span class="avatar-title bg-light text-muted rounded-circle fs-1">
                                                                                <i class="ri-inbox-line"></i>
                                                                            </span>
                                                                        </div>
                                                                        <h6 class="fw-semibold">Belum Ada Barang di {{ $wh->name }}</h6>
                                                                        <p class="text-muted mb-3 fs-13">Anda dapat menambahkan stok atau memutasi barang ke lokasi ini melalui menu POS Inventory.</p>
                                                                        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-primary">
                                                                            <i class="ri-arrow-left-right-line me-1"></i> Ke Menu Mutasi Inventory
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2 border-top">
                                                <a href="{{ route('inventory.index') }}" class="btn btn-soft-primary me-auto">
                                                    <i class="ri-arrow-left-right-line me-1"></i> Buka Mutasi Inventory
                                                </a>
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Modal Edit Gudang --}}
                                <div class="modal fade" id="editModal{{ $wh->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('warehouse.update', $wh->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Gudang</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Gudang</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $wh->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Tipe</label>
                                                        <select name="type" class="form-select">
                                                            <option value="storage" {{ $wh->type == 'storage' ? 'selected' : '' }}>Gudang Penyimpanan</option>
                                                            <option value="display" {{ $wh->type == 'display' ? 'selected' : '' }}>Etalase (Display)</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwitch{{ $wh->id }}" {{ $wh->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="activeSwitch{{ $wh->id }}">Active</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary" data-submit-protect="true">Simpan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada gudang terdaftar di cabang ini.</td>
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
    document.addEventListener('DOMContentLoaded', function () {
        // Instant search inside warehouse items modal
        document.querySelectorAll('.search-item-input').forEach(function (input) {
            input.addEventListener('keyup', function () {
                const query = this.value.toLowerCase().trim();
                const tableId = this.getAttribute('data-target');
                const rows = document.querySelectorAll('#' + tableId + ' tbody tr.item-row');
                rows.forEach(function (row) {
                    const name = row.querySelector('.item-name')?.textContent.toLowerCase() || '';
                    const category = row.querySelector('.item-category')?.textContent.toLowerCase() || '';
                    if (name.includes(query) || category.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });

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
