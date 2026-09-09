@extends('layouts.master')
@section('title')
    Branch Management
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Admin
        @endslot
        @slot('title')
            Branch Management
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
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
                            <h5 class="card-title mb-0"><i class="ri-building-line me-2 text-primary"></i>Branch Management</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('hotels.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Add Branch
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="sort text-uppercase">Code</th>
                                    <th class="sort text-uppercase">Name</th>
                                    <th class="sort text-uppercase">Phone</th>
                                    <th class="sort text-uppercase">Users</th>
                                    <th class="sort text-uppercase">Status</th>
                                    <th class="sort text-uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @forelse($hotels as $hotel)
                                <tr>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $hotel->code }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <span class="fw-medium">{{ $hotel->name }}</span>
                                                <br>
                                                <small class="text-muted">{{ $hotel->address }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $hotel->phone ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ $hotel->users_count }} Users</span>
                                    </td>
                                    <td>
                                        @if($hotel->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <a href="{{ route('hotels.edit', $hotel->id) }}" class="text-info d-inline-block">
                                                    <i class="ri-edit-2-line fs-16"></i>
                                                </a>
                                            </li>
                                            @if($hotel->code !== 'DEFAULT')
                                            <li class="list-inline-item">
                                                <form action="{{ route('hotels.destroy', $hotel->id) }}" method="POST" data-ajax="true" onsubmit="return confirm('Are you sure you want to delete this branch? All associated data will be lost.')" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" data-submit-protect="true" class="btn btn-link text-danger p-0">
                                                        <i class="ri-delete-bin-5-fill fs-16"></i>
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No branches found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $hotels->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
