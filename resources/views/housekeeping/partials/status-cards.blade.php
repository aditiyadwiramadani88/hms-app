{{-- resources/views/housekeeping/partials/status-cards.blade.php --}}
<div class="card mb-4">
    <div class="card-header bg-light border-0">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1"><i class="ri-bar-chart-2-fill me-1 text-primary"></i> {{ __('housekeeping.status_summary') }}</h5>
            <div class="flex-shrink-0">
                <select class="form-select form-select-sm" id="hkLangToggle" style="width: auto;">
                    <option value="id" {{ session('locale', 'id') == 'id' ? 'selected' : '' }}>🇮🇩 Indonesia</option>
                    <option value="en" {{ session('locale') == 'en' ? 'selected' : '' }}>🇬🇧 English</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Bersih --}}
            <div class="col-xl-2 col-md-4 col-6">
                <div class="hk-status-card status-clean p-3 text-center hk-status-filter" data-status="Available">
                    <div class="hk-status-icon text-success">✅</div>
                    <div class="hk-status-count text-success">{{ $cleanRooms->count() ?? 0 }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">{{ __('housekeeping.status.clean') }}</div>
                    <div class="fs-11 text-muted">{{ __('housekeeping.status_desc.clean') }}</div>
                </div>
            </div>

            {{-- Kotor --}}
            <div class="col-xl-2 col-md-4 col-6">
                <div class="hk-status-card status-dirty p-3 text-center hk-status-filter" data-status="Checkout">
                    <div class="hk-status-icon text-danger">🧹</div>
                    <div class="hk-status-count text-danger">{{ $dirtyRooms->count() ?? 0 }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">{{ __('housekeeping.status.dirty') }}</div>
                    <div class="fs-11 text-muted">{{ __('housekeeping.status_desc.dirty') }}</div>
                </div>
            </div>

            {{-- Sedang Dibersihkan --}}
            <div class="col-xl-2 col-md-4 col-6">
                <div class="hk-status-card status-cleaning p-3 text-center hk-status-filter" data-status="Room Refresh">
                    <div class="hk-status-icon text-warning">🔄</div>
                    <div class="hk-status-count text-warning">{{ $cleaningRooms->count() ?? 0 }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">{{ __('housekeeping.status.cleaning') }}</div>
                    <div class="fs-11 text-muted">{{ __('housekeeping.status_desc.cleaning') }}</div>
                </div>
            </div>

            {{-- Terisi --}}
            <div class="col-xl-2 col-md-4 col-6">
                <div class="hk-status-card status-occupied p-3 text-center hk-status-filter" data-status="In-House,Checkin">
                    <div class="hk-status-icon text-primary">🏠</div>
                    <div class="hk-status-count text-primary">{{ $occupiedRooms->count() ?? 0 }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">{{ __('housekeeping.status.occupied') }}</div>
                    <div class="fs-11 text-muted">{{ __('housekeeping.status_desc.occupied') }}</div>
                </div>
            </div>

            {{-- Maintenance --}}
            <div class="col-xl-2 col-md-4 col-6">
                <div class="hk-status-card status-maintenance p-3 text-center hk-status-filter" data-status="maintenance">
                    <div class="hk-status-icon text-warning">⚠️</div>
                    <div class="hk-status-count text-warning">{{ $maintenanceRooms->where('status', 'maintenance')->count() ?? 0 }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">{{ __('housekeeping.status.maintenance') }}</div>
                    <div class="fs-11 text-muted">{{ __('housekeeping.status_desc.maintenance') }}</div>
                </div>
            </div>

            {{-- Out of Order --}}
            <div class="col-xl-2 col-md-4 col-6">
                <div class="hk-status-card status-out-of-order p-3 text-center hk-status-filter" data-status="Out of Order">
                    <div class="hk-status-icon text-secondary">🚫</div>
                    <div class="hk-status-count text-secondary">{{ $maintenanceRooms->where('status', 'Out of Order')->count() ?? 0 }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">{{ __('housekeeping.status.out_of_order') }}</div>
                    <div class="fs-11 text-muted">{{ __('housekeeping.status_desc.out_of_order') }}</div>
                </div>
            </div>

            {{-- Uncategorized / Sisa --}}
            @php $otherCount = $otherRooms->count() ?? 0; @endphp
            <div class="col-xl-2 col-md-4 col-6 @if($otherCount === 0) d-none @endif">
                <div class="hk-status-card status-other p-3 text-center hk-status-filter" data-status="other">
                    <div class="hk-status-icon text-muted">❓</div>
                    <div class="hk-status-count text-muted">{{ $otherCount }}</div>
                    <div class="fs-12 text-muted">{{ __('housekeeping.rooms') }}</div>
                    <div class="mt-2 fw-semibold fs-14">Lainnya / Uncategorized</div>
                    <div class="fs-11 text-muted">Status tidak dikenal</div>
                </div>
            </div>
        </div>

        {{-- Total Verification --}}
        @php
            $cardTotal = $cleanRooms->count() + $dirtyRooms->count() + $cleaningRooms->count() + $occupiedRooms->count() + $maintenanceRooms->count() + $otherRooms->count();
        @endphp
        <div class="row mt-2">
            <div class="col-12">
                <div class="d-flex justify-content-center gap-2 fs-12 text-muted">
                    <span>Total: <strong>{{ $cardTotal }}</strong> / {{ $allRooms->count() }} rooms</span>
                    @if($cardTotal !== $allRooms->count())
                    <span class="text-danger">⚠️ {{ $allRooms->count() - $cardTotal }} room(s) uncategorized</span>
                    @else
                    <span class="text-success">✅ All {{ $allRooms->count() }} rooms accounted</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
