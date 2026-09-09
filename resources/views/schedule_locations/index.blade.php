@extends('layouts.master')
@section('title') Master Lokasi @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Employee Schedule @endslot
        @slot('title') Master Lokasi @endslot
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
                            <h5 class="card-title mb-0"><i class="ri-map-pin-line me-2 text-primary"></i>Master Lokasi</h5>
                        </div>
                        <div class="col-sm-auto">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#locationModal">
                                <i class="ri-add-line align-bottom me-1"></i> Add Location
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
                                    <th class="text-uppercase">Status</th>
                                    <th class="text-uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($locations as $location)
                                    <tr>
                                        <td><span class="badge bg-primary">{{ $location->code }}</span></td>
                                        <td>{{ $location->name }}</td>
                                        <td>
                                            @if($location->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-soft-warning btn-sm" data-bs-toggle="modal" data-bs-target="#locationModal{{ $location->id }}">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('schedule-locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4">No locations yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div class="modal fade" id="locationModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('schedule-locations.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Add Location</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" value="1" id="locActive" checked>
                                    <label class="form-check-label" for="locActive">Active</label>
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
    @foreach($locations as $location)
        <div class="modal fade" id="locationModal{{ $location->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" action="{{ route('schedule-locations.update', $location) }}">
                    @csrf @method('PUT')
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Edit Location: {{ $location->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ $location->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" value="{{ $location->code }}" required>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" value="1" id="locActive{{ $location->id }}" @checked($location->is_active)>
                                        <label class="form-check-label" for="locActive{{ $location->id }}">Active</label>
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
