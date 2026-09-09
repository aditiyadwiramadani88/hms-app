@extends('layouts.master')
@section('title') Room Check-In/Out Activity @endsection
@section('content')
    <x-breadcrumb title="Room Check-In/Out Activity" :links="[['label' => 'Reports', 'url' => route('reports.index')]]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Filter Activity</h4>
                    <div class="flex-shrink-0">
                        <a href="{{ route('reports.export.room_activity', request()->all()) }}" class="btn btn-success btn-sm">
                            <i class="ri-file-excel-2-line align-bottom me-1"></i> Export to Excel
                        </a>
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form action="{{ route('reports.room_activity') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-2 col-sm-4">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" value="{{ $startTime }}">
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" value="{{ $endTime }}">
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <label class="form-label">Type</label>
                                <select name="activity_type" class="form-select">
                                    <option value="all" {{ $activityType === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="checkin" {{ $activityType === 'checkin' ? 'selected' : '' }}>Check-In</option>
                                    <option value="checkout" {{ $activityType === 'checkout' ? 'selected' : '' }}>Check-Out</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Guest / Room..." value="{{ $search ?? '' }}">
                            </div>
                            <div class="col-xxl-12 col-sm-12 d-flex align-items-end justify-content-end">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                    <i class="ri-equalizer-fill me-1 align-bottom"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body pt-0 mt-3">
                    @include('reports.partials.room_activity_table')
                    <div class="mt-3">
                        {{ $activities->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection