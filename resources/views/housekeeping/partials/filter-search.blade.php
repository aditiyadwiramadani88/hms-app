{{-- resources/views/housekeeping/partials/filter-search.blade.php --}}
<div class="card mb-4">
    <div class="card-header bg-light border-0">
        <h5 class="card-title mb-0"><i class="ri-search-line me-1 text-primary"></i> {{ __('housekeeping.search_filter') }}</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-md-3">
                <div class="search-box">
                    <input type="text" class="form-control" id="hkSearchInput" placeholder="{{ __('housekeeping.search_placeholder') }}">
                    <i class="ri-search-line search-icon"></i>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="hkFloorFilter">
                    <option value="all">{{ __('housekeeping.all_floors') }}</option>
                    @php
                        $floors = $allRooms->pluck('floor')->unique()->filter()->sort();
                    @endphp
                    @foreach($floors as $floor)
                        <option value="{{ $floor }}">{{ __('housekeeping.floor') }} {{ $floor }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-success hk-status-filter" data-status="Available">✅ {{ __('housekeeping.status.clean') }}</button>
                    <button class="btn btn-sm btn-outline-danger hk-status-filter" data-status="Checkout">🧹 {{ __('housekeeping.status.dirty') }}</button>
                    <button class="btn btn-sm btn-outline-warning hk-status-filter" data-status="Room Refresh">🔄 {{ __('housekeeping.status.cleaning') }}</button>
                    <button class="btn btn-sm btn-outline-primary hk-status-filter" data-status="In-House,Checkin">🏠 {{ __('housekeeping.status.occupied') }}</button>
                    <button class="btn btn-sm btn-outline-dark hk-status-filter" data-status="maintenance">⚠️ {{ __('housekeeping.status.maintenance') }}</button>
                    <button class="btn btn-sm btn-outline-secondary hk-status-filter" data-status="all">{{ __('housekeeping.all_status') }}</button>
                </div>
            </div>
            <div class="col-md-2 text-md-end">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary hk-view-toggle active" data-view="grid"><i class="ri-grid-fill"></i> Grid</button>
                    <button type="button" class="btn btn-outline-primary hk-view-toggle" data-view="list"><i class="ri-list-check"></i> List</button>
                </div>
            </div>
        </div>
    </div>
</div>
