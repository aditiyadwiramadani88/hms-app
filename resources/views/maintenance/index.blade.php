@extends('layouts.master')
@section('title') Maintenance @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Operations @endslot
        @slot('title') Maintenance @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-tools-fill me-2 text-warning"></i>Daftar Maintenance</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-2">
                                <a href="{{ route('maintenance.report') }}" class="btn btn-soft-info btn-sm">
                                    <i class="ri-file-list-3-line align-bottom me-1"></i> Laporan
                                </a>
                                <a href="{{ route('maintenance.categories.index') }}" class="btn btn-soft-secondary btn-sm">
                                    <i class="ri-price-tag-3-line align-bottom me-1"></i> Kategori
                                </a>
                                <a href="{{ route('maintenance.records.create') }}" class="btn btn-success btn-sm">
                                    <i class="ri-add-line align-bottom me-1"></i> Tambah Record
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('maintenance.records.index') }}" class="row g-2">
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" name="room_id">
                                <option value="">Semua Kamar</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ request('room_id') == $room->id ? 'selected' : '' }}>{{ $room->room_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" name="status">
                                <option value="">Semua Status</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}" placeholder="Dari">
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}" placeholder="Sampai">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Tgl</th>
                                    <th>Kamar</th>
                                    <th>Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Tindakan</th>
                                    <th>Teknisi</th>
                                    <th>Biaya</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $record)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $record->maintenance_date->format('d/m/Y') }}</td>
                                    <td>{{ $record->room?->room_number ?? '-' }}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ $record->category?->name ?? '-' }}</span></td>
                                    <td>{{ \Illuminate\Support\Str::limit($record->description, 40) }}</td>
                                    <td>
                                        @if($record->actions && count($record->actions) > 0)
                                            <div class="d-flex flex-wrap gap-1">
                                            @foreach($record->actions as $action)
                                                <span class="badge bg-info-subtle text-info fs-10">{{ $action }}</span>
                                            @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $record->technician_name ?? '-' }}</td>
                                    <td>{{ $record->cost > 0 ? 'Rp ' . number_format($record->cost, 0, ',', '.') : '-' }}</td>
                                    <td>
                                        @if($record->status === 'completed')
                                            <span class="badge bg-success-subtle text-success">Selesai</span>
                                        @elseif($record->status === 'in_progress')
                                            <span class="badge bg-warning-subtle text-warning">Proses</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Jadwal</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="{{ route('maintenance.records.edit', $record->id) }}" class="text-primary d-inline-block">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item">
                                                <form action="{{ route('maintenance.records.destroy', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Hapus record ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0">
                                                        <i class="ri-delete-bin-5-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">Belum ada record maintenance.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
