@extends('layouts.master')
@section('title') Master Shift @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Employee Schedule @endslot
        @slot('title') Master Shift @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-time-line me-2 text-primary"></i>Master Shift</h5>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#shiftModal">
                                <i class="ri-add-line align-bottom me-1"></i> Add Shift
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="text-uppercase">Code</th>
                                    <th class="text-uppercase">Name</th>
                                    <th class="text-uppercase">Color</th>
                                    <th class="text-uppercase">Start</th>
                                    <th class="text-uppercase">End</th>
                                    <th class="text-uppercase">Break Time</th>
                                    <th class="text-uppercase">Session 2</th>
                                    <th class="text-uppercase">Type</th>
                                    <th class="text-uppercase">Order</th>
                                    <th class="text-uppercase">Actions</th>
                                </tr>
                            </thead>                            <tbody>
                                @forelse($shifts as $shift)
                                    <tr>
                                        <td><span class="badge" style="background: {{ $shift->color }}">{{ $shift->code }}</span></td>
                                        <td>{{ $shift->name }}</td>
                                        <td><span class="badge" style="background: {{ $shift->color }}; color: #fff">{{ $shift->color }}</span></td>
                                        <td>{{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '-' }}</td>
                                        <td>{{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '-' }}</td>
                                        <td>
                                            @if($shift->break_start_time && $shift->break_end_time)
                                                {{ \Carbon\Carbon::parse($shift->break_start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->break_end_time)->format('H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($shift->start_time_2 && $shift->end_time_2)
                                                {{ \Carbon\Carbon::parse($shift->start_time_2)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time_2)->format('H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($shift->is_off)
                                                <span class="badge bg-secondary">Libur</span>
                                            @else
                                                <span class="badge bg-success">Shift</span>
                                            @endif
                                        </td>
                                        <td>{{ $shift->sort_order }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-soft-warning btn-sm" data-bs-toggle="modal" data-bs-target="#shiftModal{{ $shift->id }}">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('shifts.destroy', $shift) }}" onsubmit="return confirm('Delete this shift?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center py-4">No shifts yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div class="modal fade" id="shiftModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('shifts.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Add Shift</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Color</label>
                                <input type="color" name="color" class="form-control form-control-color" value="#28a745">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Break Start</label>
                                <input type="time" name="break_start_time" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Break End</label>
                                <input type="time" name="break_end_time" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Time (Sesi 2)</label>
                                <input type="time" name="start_time_2" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Time (Sesi 2)</label>
                                <input type="time" name="end_time_2" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_off" class="form-check-input" value="1" id="isOff">
                                    <label class="form-check-label" for="isOff">This is a day off / libur</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" id="isActive" checked>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modals --}}
    @foreach($shifts as $shift)
        <div class="modal fade" id="shiftModal{{ $shift->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('shifts.update', $shift) }}">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Shift: {{ $shift->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ $shift->name }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" value="{{ $shift->code }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Color</label>
                                    <input type="color" name="color" class="form-control form-control-color" value="{{ $shift->color }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ $shift->sort_order }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" value="{{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control" value="{{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Break Start</label>
                                    <input type="time" name="break_start_time" class="form-control" value="{{ $shift->break_start_time ? \Carbon\Carbon::parse($shift->break_start_time)->format('H:i') : '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Break End</label>
                                    <input type="time" name="break_end_time" class="form-control" value="{{ $shift->break_end_time ? \Carbon\Carbon::parse($shift->break_end_time)->format('H:i') : '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Time (Sesi 2)</label>
                                    <input type="time" name="start_time_2" class="form-control" value="{{ $shift->start_time_2 ? \Carbon\Carbon::parse($shift->start_time_2)->format('H:i') : '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Time (Sesi 2)</label>
                                    <input type="time" name="end_time_2" class="form-control" value="{{ $shift->end_time_2 ? \Carbon\Carbon::parse($shift->end_time_2)->format('H:i') : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_off" class="form-check-input" value="1" id="isOff{{ $shift->id }}" @checked($shift->is_off)>
                                        <label class="form-check-label" for="isOff{{ $shift->id }}">This is a day off / libur</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" value="1" id="isActive{{ $shift->id }}" @checked($shift->is_active)>
                                        <label class="form-check-label" for="isActive{{ $shift->id }}">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
