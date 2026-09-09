@extends('layouts.master')
@section('title')
    Edit Room
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Rooms
        @endslot
        @slot('title')
            Edit Room
        @endslot
    @endcomponent

    <form action="{{ route('rooms.update', $room->id) }}" method="POST" data-ajax="true" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-hotel-bed-fill me-2 text-primary"></i>Room Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('room_number') is-invalid @enderror"
                                   id="room_number" name="room_number" value="{{ old('room_number', $room->room_number) }}"
                                   placeholder="e.g., 101, 205, 310" required>
                            @error('room_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="room_type_id" class="form-label">Room Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('room_type_id') is-invalid @enderror"
                                            id="room_type_id" name="room_type_id" required>
                                        <option value="">Select Room Type</option>
                                        @foreach($roomTypes ?? [] as $type)
                                            <option value="{{ $type->id }}"
                                                {{ old('room_type_id', $room->room_type_id) == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }} - {{ $type->description }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('room_type_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="floor" class="form-label">Floor <span class="text-danger">*</span></label>
                                    <select class="form-select @error('floor') is-invalid @enderror"
                                            id="floor" name="floor" required>
                                        <option value="">Select Floor</option>
                                        @for($i = 1; $i <= 20; $i++)
                                            <option value="{{ $i }}" {{ old('floor', $room->floor) == $i ? 'selected' : '' }}>
                                                Floor {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                    @error('floor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="room_state" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="room_state" name="status" required>
                                <option value="">Select Status</option>
                                @foreach($statuses ?? [] as $status)
                                <option value="{{ $status->name }}" {{ old('status', $room->status) === $status->name ? 'selected' : '' }}>{{ trim($status->name) }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      id="notes" name="notes" rows="3"
                                      placeholder="Any special notes about this room...">{{ old('notes', $room->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Room Pricing Card --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-money-dollar-circle-fill me-2 text-success"></i>Room Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price_public" class="form-label">Harga Umum <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_public') is-invalid @enderror"
                                           id="price_public" name="price_public"
                                           value="{{ old('price_public', $room->price_public) }}"
                                           placeholder="Harga Umum" step="1000" min="0" required>
                                    @error('price_public')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label for="price_breakfast_public" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_breakfast_public') is-invalid @enderror"
                                           id="price_breakfast_public" name="price_breakfast_public"
                                           value="{{ old('price_breakfast_public', $room->price_breakfast_public) }}"
                                           placeholder="Harga Breakfast" step="1000" min="0">
                                    @error('price_breakfast_public')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">per orang/malam</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="price_sales" class="form-label">Harga Sales <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_sales') is-invalid @enderror"
                                           id="price_sales" name="price_sales"
                                           value="{{ old('price_sales', $room->price_sales) }}"
                                           placeholder="Harga Sales" step="1000" min="0" required>
                                    @error('price_sales')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label for="price_breakfast_sales" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_breakfast_sales') is-invalid @enderror"
                                           id="price_breakfast_sales" name="price_breakfast_sales"
                                           value="{{ old('price_breakfast_sales', $room->price_breakfast_sales) }}"
                                           placeholder="Harga Breakfast" step="1000" min="0">
                                    @error('price_breakfast_sales')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">per orang/malam</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="price_high_season" class="form-label">Harga High Season <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_high_season') is-invalid @enderror"
                                           id="price_high_season" name="price_high_season"
                                           value="{{ old('price_high_season', $room->price_high_season) }}"
                                           placeholder="Harga High Season" step="1000" min="0" required>
                                    @error('price_high_season')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label for="price_breakfast_high_season" class="form-label">Breakfast</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('price_breakfast_high_season') is-invalid @enderror"
                                           id="price_breakfast_high_season" name="price_breakfast_high_season"
                                           value="{{ old('price_breakfast_high_season', $room->price_breakfast_high_season) }}"
                                           placeholder="Harga Breakfast" step="1000" min="0">
                                    @error('price_breakfast_high_season')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">per orang/malam</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_kos" name="is_kos" value="1"
                                       {{ old('is_kos', $room->is_kos) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_kos">
                                    Bisa di kos kan?
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="price_kos_field" style="{{ old('is_kos', $room->is_kos) ? '' : 'display: none;' }}">
                            <label for="price_kos" class="form-label">Harga Kos</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('price_kos') is-invalid @enderror"
                                       id="price_kos" name="price_kos"
                                       value="{{ old('price_kos', $room->price_kos) }}"
                                       placeholder="Harga Kos" step="1000" min="0">
                                @error('price_kos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Harga bulanan jika kamar ini dijadikan kos.</small>
                        </div>

                        <div class="mb-3" id="yearly_price_field" style="{{ old('is_kos', $room->is_kos) ? '' : 'display: none;' }}">
                            <label for="yearly_price" class="form-label">Harga Tahunan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('yearly_price') is-invalid @enderror"
                                       id="yearly_price" name="yearly_price"
                                       value="{{ old('yearly_price', $room->yearly_price) }}"
                                       placeholder="Harga per tahun" step="10000" min="0">
                                @error('yearly_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Harga tahunan (opsional). Kalau diset, booking 12+ bulan otomatis pakai harga ini.</small>
                        </div>

                        {{-- Diskon Durasi Kost --}}
                        <div class="border rounded p-3 bg-light-subtle mb-3" id="kost_pricing_section" style="{{ old('is_kos', $room->is_kos) ? '' : 'display: none;' }}">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0"><i class="ri-discount-percent-line me-1 text-primary"></i> Diskon Durasi Kost</h6>
                                <button type="button" class="btn btn-sm btn-soft-primary" id="addPricingTier">
                                    <i class="ri-add-line me-1"></i> Tambah Tier
                                </button>
                            </div>
                            <p class="text-muted small mb-3">Atur harga khusus berdasarkan durasi kontrak. Biarkan kosong jika tidak ada diskon.</p>
                            <div id="pricing_tiers_container">
                                @php
                                    $tiers = old('pricing_tiers', $room->kostPricingTiers->toArray());
                                @endphp
                                @if(count($tiers) > 0)
                                    @foreach($tiers as $idx => $tier)
                                        <div class="row g-2 mb-2 pricing-tier-row">
                                            <div class="col-md-2">
                                                <input type="number" class="form-control form-control-sm" name="pricing_tiers[{{ $idx }}][duration_months]"
                                                       value="{{ $tier['duration_months'] ?? '' }}" placeholder="Durasi (bln)" min="1" required>
                                            </div>
                                            <div class="col-md-2">
                                                <select class="form-select form-select-sm discount-type-select" name="pricing_tiers[{{ $idx }}][discount_type]">
                                                    <option value="fixed" {{ ($tier['discount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>Harga Tetap</option>
                                                    <option value="percentage" {{ ($tier['discount_type'] ?? '') === 'percentage' ? 'selected' : '' }}>Persen</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 tier-value-col">
                                                @if(($tier['discount_type'] ?? '') === 'fixed')
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="number" class="form-control form-control-sm" name="pricing_tiers[{{ $idx }}][fixed_price]"
                                                               value="{{ $tier['fixed_price'] ?? '' }}" placeholder="Harga" step="1000" min="0">
                                                    </div>
                                                @elseif(($tier['discount_type'] ?? '') === 'percentage')
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control form-control-sm" name="pricing_tiers[{{ $idx }}][percentage_value]"
                                                               value="{{ $tier['percentage_value'] ?? '' }}" placeholder="%" step="0.01" min="0.01" max="99.99">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                @else
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">Rp</span>
                                                        <input type="number" class="form-control form-control-sm" name="pricing_tiers[{{ $idx }}][fixed_price]"
                                                               placeholder="Harga" step="1000" min="0">
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" class="form-control form-control-sm tier-effective-price" readonly
                                                       value="{{ isset($tier['fixed_price'], $room->price_kos) ? 'Rp ' . number_format($tier['discount_type'] === 'fixed' ? (float)$tier['fixed_price'] : (float)$room->price_kos * (1 - (float)($tier['percentage_value'] ?? 0)/100), 0, ',', '.') : '' }}"
                                                       placeholder="Harga Efektif">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-sm btn-soft-danger w-100 remove-tier">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="text-center text-muted py-2" id="no_tiers_msg" style="{{ count($tiers) > 0 ? 'display: none;' : '' }}">
                                <small>Belum ada tier diskon durasi.</small>
                            </div>
                        </div>
                        {{-- End Diskon Durasi Kost --}}

                        {{-- Occupancy & Extra Person --}}
                        <div class="border rounded p-3 bg-light-subtle mb-3">
                            <h6 class="mb-3"><i class="ri-group-line me-1 text-primary"></i> Occupancy & Extra Person</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="max_occupancy" class="form-label">Max Occupancy (orang)</label>
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy"
                                           value="{{ old('max_occupancy', $room->max_occupancy ?? 2) }}" min="1" max="20">
                                    <small class="text-muted">Jumlah maksimum tamu per kamar.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="price_extra_person" class="form-label">Harga Extra Person (/malam)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="price_extra_person" name="price_extra_person"
                                               value="{{ old('price_extra_person', $room->price_extra_person ?? 0) }}" step="1000" min="0">
                                    </div>
                                    <small class="text-muted">Charge tambahan per orang ekstra per malam.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Checklist Cleaning --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1"><i class="ri-list-check me-2 text-info"></i>Checklist Cleaning</h5>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-soft-success btn-sm" id="selectAllChecklist">Pilih Semua</button>
                            <button type="button" class="btn btn-soft-danger btn-sm" id="unselectAllChecklist">Hapus Semua</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Pilih item checklist yang berlaku untuk kamar ini. Jika tidak ada yang dipilih, semua item akan digunakan saat assign OB.</p>
                        @if(isset($checklistTemplates) && $checklistTemplates->count() > 0)
                            <div class="row">
                                @foreach($checklistTemplates as $template)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input checklist-checkbox" type="checkbox" 
                                               name="checklist_items[]" 
                                               value="{{ $template->id }}" 
                                               id="checklist_{{ $template->id }}"
                                               {{ in_array($template->id, old('checklist_items', $selectedChecklistIds ?? [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="checklist_{{ $template->id }}">
                                            {{ $template->name }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="ri-list-check fs-1 d-block mb-2"></i>
                                <p class="mb-0">Belum ada template checklist. <a href="{{ route('housekeeping.checklist-templates.index') }}">Tambah di sini</a>.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" data-submit-protect="true" class="btn btn-success">
                                <i class="ri-save-line me-1 align-bottom"></i> Update Room
                            </button>
                            <a href="{{ route('rooms.index') }}" class="btn btn-soft-secondary">
                                <i class="ri-arrow-left-line me-1 align-bottom"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-image-line me-2 text-warning"></i>Room Photo</h5>
                    </div>
                    <div class="card-body">
                        @if($room->image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($room->image) }}" alt="Room Photo" style="width: 100%; border-radius: 6px; max-height: 200px; object-fit: cover;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Max 2MB. jpg, png, webp.</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Room Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="text-muted">Created:</span>
                                <span class="fw-medium">{{ $room->created_at ? $room->created_at->format('d M Y, H:i') : 'N/A' }}</span>
                            </li>
                            <li>
                                <span class="text-muted">Last Updated:</span>
                                <span class="fw-medium">{{ $room->updated_at ? $room->updated_at->format('d M Y, H:i') : 'N/A' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <script>
        const baseKosPrice = {{ $room->price_kos ?? 0 }};

        document.getElementById('is_kos').addEventListener('change', function() {
            document.getElementById('price_kos_field').style.display = this.checked ? '' : 'none';
            document.getElementById('price_kos').required = this.checked;
            document.getElementById('kost_pricing_section').style.display = this.checked ? '' : 'none';
        });

        // Initialize state on load
        document.addEventListener('DOMContentLoaded', function() {
            var kosToggle = document.getElementById('is_kos');
            document.getElementById('price_kos').required = kosToggle.checked;

            var selectAllBtn = document.getElementById('selectAllChecklist');
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    document.querySelectorAll('.checklist-checkbox').forEach(cb => cb.checked = true);
                });
            }

            var unselectAllBtn = document.getElementById('unselectAllChecklist');
            if (unselectAllBtn) {
                unselectAllBtn.addEventListener('click', function() {
                    document.querySelectorAll('.checklist-checkbox').forEach(cb => cb.checked = false);
                });
            }

            // Pricing tiers: add new row
            document.getElementById('addPricingTier').addEventListener('click', function() {
                addPricingTierRow();
            });

            // Delegate events for remove-tier buttons
            document.getElementById('pricing_tiers_container').addEventListener('click', function(e) {
                if (e.target.closest('.remove-tier')) {
                    e.target.closest('.pricing-tier-row').remove();
                    reindexTiers();
                }
            });

            // Delegate events for discount type change
            document.getElementById('pricing_tiers_container').addEventListener('change', function(e) {
                if (e.target.classList.contains('discount-type-select')) {
                    onDiscountTypeChange(e.target);
                }
                if (e.target.closest('.tier-value-col') && (e.target.name.includes('fixed_price') || e.target.name.includes('percentage_value'))) {
                    updateEffectivePrice(e.target.closest('.pricing-tier-row'));
                }
            });

            // Effective price on input
            document.getElementById('pricing_tiers_container').addEventListener('input', function(e) {
                if (e.target.name && (e.target.name.includes('fixed_price') || e.target.name.includes('percentage_value'))) {
                    updateEffectivePrice(e.target.closest('.pricing-tier-row'));
                }
            });
        });

        function reindexTiers() {
            const rows = document.querySelectorAll('#pricing_tiers_container .pricing-tier-row');
            const noTiersMsg = document.getElementById('no_tiers_msg');
            noTiersMsg.style.display = rows.length > 0 ? 'none' : '';

            rows.forEach((row, idx) => {
                row.querySelectorAll('input, select').forEach(input => {
                    input.name = input.name.replace(/pricing_tiers\[\d+\]/, 'pricing_tiers[' + idx + ']');
                });
            });
        }

        function onDiscountTypeChange(select) {
            const row = select.closest('.pricing-tier-row');
            const valueCol = row.querySelector('.tier-value-col');
            const type = select.value;
            const idx = select.name.match(/\[(\d+)\]/)[1];

            if (type === 'fixed') {
                valueCol.innerHTML = `<div class="input-group input-group-sm">
                    <span class="input-group-text">Rp</span>
                    <input type="number" class="form-control form-control-sm" name="pricing_tiers[${idx}][fixed_price]" placeholder="Harga" step="1000" min="0">
                </div>`;
            } else {
                valueCol.innerHTML = `<div class="input-group input-group-sm">
                    <input type="number" class="form-control form-control-sm" name="pricing_tiers[${idx}][percentage_value]" placeholder="%" step="0.01" min="0.01" max="99.99">
                    <span class="input-group-text">%</span>
                </div>`;
            }
            updateEffectivePrice(row);
        }

        function updateEffectivePrice(row) {
            const select = row.querySelector('.discount-type-select');
            const effectiveInput = row.querySelector('.tier-effective-price');
            if (!select || !effectiveInput) return;

            const type = select.value;
            let effective = 0;

            if (type === 'fixed') {
                const fixedInput = row.querySelector('input[name*="fixed_price"]');
                effective = fixedInput ? parseFloat(fixedInput.value) || 0 : 0;
            } else {
                const pctInput = row.querySelector('input[name*="percentage_value"]');
                const pct = pctInput ? parseFloat(pctInput.value) || 0 : 0;
                effective = baseKosPrice * (1 - pct / 100);
            }

            effectiveInput.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(effective));
        }

        function addPricingTierRow() {
            const container = document.getElementById('pricing_tiers_container');
            const rows = container.querySelectorAll('.pricing-tier-row');
            const idx = rows.length > 0 ? parseInt(rows[rows.length - 1].querySelector('select').name.match(/\[(\d+)\]/)[1]) + 1 : 0;

            const html = `<div class="row g-2 mb-2 pricing-tier-row">
                <div class="col-md-2">
                    <input type="number" class="form-control form-control-sm" name="pricing_tiers[${idx}][duration_months]" placeholder="Durasi (bln)" min="1" required>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm discount-type-select" name="pricing_tiers[${idx}][discount_type]">
                        <option value="fixed">Harga Tetap</option>
                        <option value="percentage">Persen</option>
                    </select>
                </div>
                <div class="col-md-3 tier-value-col">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control form-control-sm" name="pricing_tiers[${idx}][fixed_price]" placeholder="Harga" step="1000" min="0">
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm tier-effective-price" readonly placeholder="Harga Efektif">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-soft-danger w-100 remove-tier">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </div>`;

            container.insertAdjacentHTML('beforeend', html);
            document.getElementById('no_tiers_msg').style.display = 'none';
            reindexTiers();
        }
    </script>
@endsection
