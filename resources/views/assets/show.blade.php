@extends('layouts.master')
@section('title', 'Detail Asset')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asset Management @endslot
        @slot('title') Detail Asset @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-9">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            @if($asset->photo)
                                <img loading="lazy" src="{{ Storage::url($asset->photo) }}" alt="" class="img-thumbnail rounded" style="max-height: 250px; object-fit: contain;">
                            @else
                                <div class="p-5 bg-light rounded d-flex justify-content-center align-items-center h-100 min-h-200px">
                                    <div class="text-center">
                                        <i class="ri-image-line text-muted display-4"></i>
                                        <p class="text-muted mt-2 mb-0">Belum ada foto</p>
                                    </div>
                                </div>
                            @endif
                            <div class="mt-3">
                                @php
                                    $statusColor = 'success';
                                    if($asset->status == 'Rusak Ringan') $statusColor = 'warning';
                                    elseif($asset->status == 'Rusak Berat') $statusColor = 'danger';
                                    elseif($asset->status == 'Maintenance') $statusColor = 'info';
                                    elseif($asset->status == 'Menunggu Diganti') $statusColor = 'secondary';
                                    elseif($asset->status == 'Sudah Dibuang') $statusColor = 'dark';
                                @endphp
                                <span class="badge bg-{{ $statusColor }} fs-14 w-100 py-2 text-uppercase">{{ $asset->status }}</span>
                                <button type="button" class="btn btn-outline-warning w-100 mt-2" data-bs-toggle="modal" data-bs-target="#statusModal">
                                    <i class="ri-refresh-line align-bottom me-1"></i> Update Status
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-8 pt-3 pt-md-0">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h4 class="mb-0">{{ $asset->name }}</h4>
                                <div class="dropdown">
                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-fill align-middle"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('assets.edit', $asset->id) }}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit Asset</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" onsubmit="return confirm('Apakah Anda yakin ingin menghapus asset ini? Data riwayat juga akan ikut terhapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-fill align-bottom me-2 text-danger"></i> Hapus Asset</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <p class="text-muted fs-13 mb-4">Serial Number: <span class="fw-medium text-body">{{ $asset->serial_number ?? 'Tidak Ada' }}</span></p>
                            
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="p-3 border border-dashed rounded mb-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm">
                                                    <div class="avatar-title bg-light text-primary rounded-circle fs-20">
                                                        <i class="ri-price-tag-3-line"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="text-muted mb-0">Kategori</p>
                                                <h5 class="fs-14 mb-0">{{ $asset->category->name ?? '-' }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 border border-dashed rounded mb-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm">
                                                    <div class="avatar-title bg-light text-success rounded-circle fs-20">
                                                        <i class="ri-map-pin-user-line"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="text-muted mb-0">Lokasi Penempatan</p>
                                                <h5 class="fs-14 mb-0">
                                                    @if($asset->location_type == 'room' && $asset->room)
                                                        Room {{ $asset->room->room_number }}
                                                    @elseif($asset->location_type == 'area')
                                                        {{ $asset->area_name }}
                                                    @else
                                                        -
                                                    @endif
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 border border-dashed rounded mb-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm">
                                                    <div class="avatar-title bg-light text-info rounded-circle fs-20">
                                                        <i class="ri-user-settings-line"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="text-muted mb-0">PIC (Penanggung Jawab)</p>
                                                <h5 class="fs-14 mb-0">
                                                    @if($asset->pic_user_id && $asset->picUser)
                                                        {{ $asset->picUser->name }} <span class="badge bg-light text-body ms-1">User</span>
                                                    @elseif($asset->pic_role)
                                                        {{ $asset->pic_role }} <span class="badge bg-light text-body ms-1">Role</span>
                                                    @else
                                                        -
                                                    @endif
                                                </h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 border border-dashed rounded mb-2">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm">
                                                    <div class="avatar-title bg-light text-warning rounded-circle fs-20">
                                                        <i class="ri-calendar-event-line"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="text-muted mb-0">Tahun Beli</p>
                                                <h5 class="fs-14 mb-0">{{ $asset->purchase_year }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="fs-14 mb-3">Informasi Pembelian & Catatan</h5>
                            <div class="table-responsive">
                                <table class="table table-borderless table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted" style="width: 150px;">Harga Beli</td>
                                            <td class="fw-medium">: Rp {{ number_format($asset->purchase_price, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Supplier/Toko</td>
                                            <td class="fw-medium">: {{ $asset->supplier ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Ditambahkan</td>
                                            <td class="fw-medium">: {{ $asset->created_at->format('d M Y, H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted align-top">Catatan Kondisi</td>
                                            <td class="fw-medium">: {{ $asset->condition_notes ?? 'Tidak ada catatan.' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Riwayat Perbaikan</h4>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#repairModal">
                        <i class="ri-tools-line me-1"></i> Tambah Perbaikan
                    </button>
                </div>
                <div class="card-body">
                    @if($asset->repairs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Masalah</th>
                                    <th>Teknisi</th>
                                    <th>Biaya</th>
                                    <th>Status</th>
                                    <th>Hasil</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asset->repairs as $repair)
                                <tr>
                                    <td class="text-nowrap">{{ $repair->repair_date->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $repair->repair_type }}</span></td>
                                    <td style="max-width: 200px; white-space: normal;">{{ Str::limit($repair->problem_description, 50) }}</td>
                                    <td>{{ $repair->technician ?? '-' }}</td>
                                    <td class="text-end text-danger fw-medium">Rp {{ number_format($repair->cost, 0, ',', '.') }}</td>
                                    <td>
                                        @if($repair->status === 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @elseif($repair->status === 'in_progress')
                                            <span class="badge bg-warning">Proses</span>
                                        @else
                                            <span class="badge bg-danger">Gagal</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 150px; white-space: normal;">{{ $repair->result ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Biaya Perbaikan:</td>
                                    <td class="text-end text-danger fw-bold">Rp {{ number_format($asset->totalRepairCost(), 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-3">
                        <i class="ri-tools-line fs-1 d-block mb-2"></i>
                        Belum ada riwayat perbaikan.
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Riwayat Stok (Mutasi Barang)</h4>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#stockModal">
                        <i class="ri-add-line me-1"></i> Tambah/Kurangi Stok
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border mb-3 p-2 d-flex align-items-center">
                        <i class="ri-archive-line fs-4 me-2 text-primary"></i>
                        <span class="fw-bold fs-14">Stok Saat Ini: <span class="text-primary">{{ $asset->quantity }} unit</span></span>
                    </div>
                    @if($asset->stockMutations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Qty</th>
                                    <th>Alasan</th>
                                    <th>Catatan</th>
                                    <th>Referensi</th>
                                    <th>Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $mutationReasonLabels = \App\Models\AssetStockMutation::reasons(); @endphp
                                @foreach($asset->stockMutations as $mutation)
                                <tr>
                                    <td class="text-nowrap">{{ $mutation->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($mutation->type === 'in')
                                            <span class="badge bg-success">MASUK</span>
                                        @else
                                            <span class="badge bg-danger">KELUAR</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold {{ $mutation->type === 'in' ? 'text-success' : 'text-danger' }}">
                                        {{ $mutation->type === 'in' ? '+' : '-' }}{{ $mutation->quantity }}
                                    </td>
                                    <td>{{ $mutationReasonLabels[$mutation->reason] ?? $mutation->reason }}</td>
                                    <td style="max-width: 150px; white-space: normal;">{{ $mutation->notes ?? '-' }}</td>
                                    <td>{{ $mutation->reference ?? '-' }}</td>
                                    <td>{{ $mutation->user->name ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-3">Belum ada riwayat mutasi stok.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Riwayat Perubahan Status</h4>
                </div>
                <div class="card-body">
                    <div class="profile-timeline">
                        <div class="accordion accordion-flush" id="historyTimeline">
                            @forelse($asset->histories as $history)
                                <div class="accordion-item border-0">
                                    <div class="accordion-header" id="heading{{ $history->id }}">
                                        <a class="accordion-button p-2 shadow-none" data-bs-toggle="collapse" href="#collapse{{ $history->id }}" aria-expanded="true" aria-controls="collapse{{ $history->id }}">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 avatar-xs">
                                                    <div class="avatar-title bg-light text-primary rounded-circle">
                                                        <i class="ri-history-line"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="fs-14 mb-0">
                                                        @if($history->from_status)
                                                            Status berubah dari <span class="text-muted">{{ $history->from_status }}</span> ke <span class="fw-semibold text-body">{{ $history->to_status }}</span>
                                                        @else
                                                            Asset didaftarkan dengan status <span class="fw-semibold text-body">{{ $history->to_status }}</span>
                                                        @endif
                                                    </h6>
                                                    <small class="text-muted">{{ $history->created_at->format('d M Y, H:i') }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div id="collapse{{ $history->id }}" class="accordion-collapse collapse show" aria-labelledby="heading{{ $history->id }}" data-bs-parent="#historyTimeline">
                                        <div class="accordion-body ms-2 ps-5 pt-0">
                                            <p class="mb-1">{{ $history->notes ?? 'Tidak ada catatan.' }}</p>
                                            <p class="text-muted mb-0 fs-12">Oleh: {{ $history->user->name ?? 'Sistem' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    Belum ada riwayat tercatat.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('assets.update-status', $asset->id) }}" method="POST" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status Saat Ini</label>
                            <input type="text" class="form-control" value="{{ $asset->status }}" readonly disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Baru <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses as $st)
                                    <option value="{{ $st }}" {{ $asset->status == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Perubahan <span class="text-danger">*</span></label>
                            <textarea name="notes" class="form-control" rows="3" required placeholder="Jelaskan alasan perubahan status..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Repair Modal -->
    <div class="modal fade" id="repairModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-tools-line me-2"></i>Tambah Riwayat Perbaikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('assets.add-repair', $asset->id) }}" method="POST" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Perbaikan <span class="text-danger">*</span></label>
                                <input type="date" name="repair_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Perbaikan <span class="text-danger">*</span></label>
                                <select name="repair_type" class="form-select" required>
                                    @foreach($repairTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teknisi / Toko</label>
                                <input type="text" name="technician" class="form-control" placeholder="Nama teknisi atau toko service">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Biaya Perbaikan <span class="text-danger">*</span></label>
                                <input type="number" name="cost" class="form-control" min="0" value="0" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi Masalah <span class="text-danger">*</span></label>
                                <textarea name="problem_description" class="form-control" rows="2" required placeholder="Jelaskan masalah yang terjadi..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tindakan yang Dilakukan</label>
                                <textarea name="action_taken" class="form-control" rows="2" placeholder="Apa yang dilakukan untuk memperbaiki..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hasil Perbaikan</label>
                                <textarea name="result" class="form-control" rows="2" placeholder="Hasil setelah diperbaiki..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="completed">Selesai</option>
                                    <option value="in_progress">Masih Proses</option>
                                    <option value="failed">Gagal / Tidak Bisa Diperbaiki</option>
                                </select>
                                <div class="mt-2">
                                    <label class="form-label">Info Garansi</label>
                                    <input type="text" name="warranty_info" class="form-control" placeholder="Misal: Garansi 3 bulan">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perbaikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Mutation Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-archive-line me-2"></i>Tambah/Kurangi Stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('assets.add-stock-mutation', $asset->id) }}" method="POST" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info border-0 py-2 mb-3">
                            Stok saat ini: <strong>{{ $asset->quantity }} unit</strong>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipe <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="in">Masuk (Tambah)</option>
                                    <option value="out">Keluar (Kurangi)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alasan <span class="text-danger">*</span></label>
                                <select name="reason" class="form-select" required>
                                    @foreach($mutationReasons as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga per Unit</label>
                                <input type="number" name="unit_cost" class="form-control" min="0" placeholder="Untuk pembelian baru">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No Nota/Referensi</label>
                                <input type="text" name="reference" class="form-control" placeholder="No invoice/nota">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Mutasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
