@extends('layouts.master')
@section('title', 'Daftar Asset')
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Asset Management @endslot
        @slot('title') Daftar Asset @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card" id="assetList">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Asset</h5>
                        <div class="flex-shrink-0">
                            <a href="{{ route('assets.create') }}" class="btn btn-success"><i class="ri-add-line align-bottom me-1"></i> Tambah Asset</a>
                        </div>
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form>
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control search" placeholder="Cari nama atau serial number..." value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-6">
                                <div>
                                    <select class="form-control" name="category_id">
                                        <option value="">Semua Kategori</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <div>
                                    <select class="form-control" name="status">
                                        <option value="">Semua Status</option>
                                        @foreach($statuses as $st)
                                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <div>
                                    <select class="form-control" name="location_type">
                                        <option value="">Semua Lokasi</option>
                                        <option value="room" {{ request('location_type') == 'room' ? 'selected' : '' }}>Kamar (Room)</option>
                                        <option value="area" {{ request('location_type') == 'area' ? 'selected' : '' }}>Area Umum</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <div>
                                    <button type="submit" class="btn btn-primary w-100"> <i class="ri-equalizer-fill me-1 align-bottom"></i> Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card mb-1">
                        <table class="table table-nowrap align-middle" id="assetTable">
                            <thead class="text-muted table-light">
                                <tr class="text-uppercase">
                                    <th>Nama Asset</th>
                                    <th>Kategori</th>
                                    <th>Qty</th>
                                    <th>Lokasi</th>
                                    <th>PIC</th>
                                    <th>Status</th>
                                    <th>Tahun Beli</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($assets as $asset)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($asset->photo)
                                                    <img loading="lazy" src="{{ Storage::url($asset->photo) }}" alt="" class="avatar-xs rounded-circle me-2">
                                                @else
                                                    <div class="avatar-xs me-2">
                                                        <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-13">
                                                            {{ substr($asset->name, 0, 2) }}
                                                        </div>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h5 class="fs-14 my-1"><a href="{{ route('assets.show', $asset->id) }}" class="text-reset">{{ $asset->name }}</a></h5>
                                                    <span class="text-muted fs-12">SN: {{ $asset->serial_number ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $asset->category->name ?? '-' }}</td>
                                        <td class="text-center fw-bold">{{ $asset->quantity }}</td>
                                        <td>
                                            @if($asset->location_type == 'room' && $asset->room)
                                                <span class="badge bg-primary-subtle text-primary">Room {{ $asset->room->room_number }}</span>
                                            @elseif($asset->location_type == 'area')
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $asset->area_name }}</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($asset->pic_user_id && $asset->picUser)
                                                {{ $asset->picUser->name }} <small class="text-muted">(User)</small>
                                            @elseif($asset->pic_role)
                                                {{ $asset->pic_role }} <small class="text-muted">(Role)</small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColor = 'success';
                                                if($asset->status == 'Rusak Ringan') $statusColor = 'warning';
                                                elseif($asset->status == 'Rusak Berat') $statusColor = 'danger';
                                                elseif($asset->status == 'Maintenance') $statusColor = 'info';
                                                elseif($asset->status == 'Menunggu Diganti') $statusColor = 'secondary';
                                                elseif($asset->status == 'Sudah Dibuang') $statusColor = 'dark';
                                            @endphp
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} text-uppercase">{{ $asset->status }}</span>
                                        </td>
                                        <td>{{ $asset->purchase_year }}</td>
                                        <td>
                                            <ul class="list-inline hstack gap-2 mb-0">
                                                <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="View">
                                                    <a href="{{ route('assets.show', $asset->id) }}" class="text-primary d-inline-block">
                                                        <i class="ri-eye-fill fs-16"></i>
                                                    </a>
                                                </li>
                                                <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                    <a href="{{ route('assets.edit', $asset->id) }}" class="text-primary d-inline-block">
                                                        <i class="ri-pencil-fill fs-16"></i>
                                                    </a>
                                                </li>
                                                <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Update Status">
                                                    <a href="javascript:void(0);" class="text-warning d-inline-block" data-bs-toggle="modal" data-bs-target="#statusModal{{ $asset->id }}">
                                                        <i class="ri-refresh-line fs-16"></i>
                                                    </a>
                                                </li>
                                            </ul>

                                            <!-- Status Modal -->
                                            <div class="modal fade" id="statusModal{{ $asset->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Status Asset: {{ $asset->name }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="{{ route('assets.update-status', $asset->id) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status Baru</label>
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
                                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada data asset yang ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $assets->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
