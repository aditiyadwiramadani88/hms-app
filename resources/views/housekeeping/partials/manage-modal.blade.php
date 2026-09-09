{{-- resources/views/housekeeping/partials/manage-modal.blade.php --}}
<div class="modal fade" id="quickManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden">
            <div class="modal-header bg-light">
                <h5 class="modal-title">🚪 {{ __('housekeeping.manage_room') }} <span id="modalRoomNumber" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="quickManageForm" method="POST" data-ajax="true">
                @csrf
                <div class="modal-body bg-light-subtle">
                    
                    {{-- Current Status Info --}}
                    <div class="card shadow-none border mb-3">
                        <div class="card-header bg-white border-bottom py-2">
                            <h6 class="card-title mb-0 fs-13 text-muted text-uppercase">📊 {{ __('housekeeping.current_status') }}</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="fs-4 me-2" id="modalCurrentIcon"></span>
                                <strong class="fs-15" id="modalCurrentStatusText"></strong>
                            </div>
                            <div class="text-muted fs-13">
                                <div><i class="ri-time-line me-1"></i> {{ __('housekeeping.last_updated') }}: <span class="text-dark">Baru Saja</span></div>
                                <div><i class="ri-user-unfollow-line me-1"></i> {{ __('housekeeping.checkout_guest') }}: <span class="text-dark">10:30 WIB</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Change Status --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">🔄 {{ __('housekeeping.change_status') }}</label>
                        <div class="hk-radio-group">
                            <div class="form-check p-0 mb-0">
                                <input type="radio" class="btn-check hk-radio-input" name="status" id="status_clean" value="Available" autocomplete="off">
                                <label class="hk-radio-label w-100 m-0" for="status_clean">
                                    <span class="me-2 fs-5">✅</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ __('housekeeping.status.clean') }}</div>
                                        <div class="fs-12 text-muted">{{ __('housekeeping.status_desc.clean') }}</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="form-check p-0 mb-0">
                                <input type="radio" class="btn-check hk-radio-input" name="status" id="status_dirty" value="Checkout" autocomplete="off">
                                <label class="hk-radio-label w-100 m-0" for="status_dirty">
                                    <span class="me-2 fs-5">🧹</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ __('housekeeping.status.dirty') }}</div>
                                        <div class="fs-12 text-muted">{{ __('housekeeping.status_desc.dirty') }}</div>
                                    </div>
                                </label>
                            </div>

                            <div class="form-check p-0 mb-0">
                                <input type="radio" class="btn-check hk-radio-input" name="status" id="status_cleaning" value="Room Refresh" autocomplete="off">
                                <label class="hk-radio-label w-100 m-0" for="status_cleaning">
                                    <span class="me-2 fs-5">🔄</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ __('housekeeping.status.cleaning') }}</div>
                                        <div class="fs-12 text-muted">{{ __('housekeeping.status_desc.cleaning') }}</div>
                                    </div>
                                </label>
                            </div>

                            <div class="form-check p-0 mb-0">
                                <input type="radio" class="btn-check hk-radio-input" name="status" id="status_occupied" value="In-House" autocomplete="off">
                                <label class="hk-radio-label w-100 m-0" for="status_occupied">
                                    <span class="me-2 fs-5">🏠</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">{{ __('housekeeping.status.occupied') }}</div>
                                        <div class="fs-12 text-muted">{{ __('housekeeping.status_desc.occupied') }}</div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <div class="form-check p-0 mb-0">
                                        <input type="radio" class="btn-check hk-radio-input" name="status" id="status_maintenance" value="maintenance" autocomplete="off">
                                        <label class="hk-radio-label w-100 m-0 py-2" for="status_maintenance">
                                            <span class="me-2">⚠️</span> <span class="fs-13 fw-semibold">{{ __('housekeeping.status.maintenance') }}</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check p-0 mb-0">
                                        <input type="radio" class="btn-check hk-radio-input" name="status" id="status_ooo" value="Out of Order" autocomplete="off">
                                        <label class="hk-radio-label w-100 m-0 py-2" for="status_ooo">
                                            <span class="me-2">🚫</span> <span class="fs-13 fw-semibold">{{ __('housekeeping.status.out_of_order') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Assign OB --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">👤 {{ __('housekeeping.assign_ob') }}</label>
                        <select class="form-select select2-modal" name="assigned_staff_id" id="modalStaffSelect" style="width: 100%;">
                            <option value="">-- {{ __('housekeeping.choose_ob') }} --</option>
                            @foreach($staffs as $staff)
                                {{-- Mock workload indicator (bisa diupdate dinamis dari controller) --}}
                                @php $activeRooms = rand(0, 5); @endphp 
                                <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $activeRooms }} {{ __('housekeeping.active_rooms') }}) ⭐⭐⭐⭐⭐</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Notes --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">📝 {{ __('housekeeping.notes') }}</label>
                        <textarea class="form-control" name="reason" rows="2" placeholder="{{ __('housekeeping.notes_placeholder') }}"></textarea>
                    </div>

                    <div class="alert alert-info border-0 shadow-none mb-0 py-2">
                        <div class="d-flex align-items-center fs-13">
                            <i class="ri-timer-line fs-5 me-2"></i>
                            <strong>{{ __('housekeeping.cleaning_time') }}:</strong> <span class="ms-1">30-45 menit</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">❌ {{ __('housekeeping.cancel') }}</button>
                    <button type="submit" data-submit-protect="true" class="btn btn-primary">✅ {{ __('housekeeping.save_assign') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Will attach these specifically to the modal logic inside index.blade.php
</script>
