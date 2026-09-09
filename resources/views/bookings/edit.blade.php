@extends('layouts.master')
@section('title')
    Edit Booking #{{ $booking->booking_number ?? $booking->id }}
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: 0.25rem !important; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px !important; padding-left: 12px !important; color: #495057 !important; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Bookings @endslot
        @slot('title') Edit Booking @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('bookings.update', $booking->id) }}" method="POST" data-ajax="true" id="bookingForm" onsubmit="return confirmDepositChange()">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-fill me-2 text-primary"></i>Guest Information</h5>
                    </div>
                    <div class="card-body">
                        <label for="guest_id" class="form-label">Guest <span class="text-danger">*</span></label>
                        <select class="form-control" id="guest_id" name="guest_id" required>
                            <option value="{{ $booking->guest_id }}" selected>{{ $booking->guest->name }} ({{ $booking->guest->phone ?? 'No Phone' }})</option>
                        </select>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-hotel-bed-fill me-2 text-success"></i>Room & Dates</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label d-block text-muted text-uppercase fw-semibold fs-11">Stay Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="stay_type" id="stay_daily" value="daily" {{ old('stay_type', $booking->stay_type ?? 'daily') === 'daily' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="stay_daily"><i class="ri-calendar-event-line me-1"></i> Harian (Daily)</label>
                                <input type="radio" class="btn-check" name="stay_type" id="stay_monthly" value="monthly" {{ old('stay_type', $booking->stay_type ?? 'daily') === 'monthly' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="stay_monthly"><i class="ri-calendar-2-line me-1"></i> Bulanan (Kos)</label>
                                <input type="radio" class="btn-check" name="stay_type" id="stay_yearly" value="yearly" {{ old('stay_type', $booking->stay_type ?? 'daily') === 'yearly' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="stay_yearly"><i class="ri-calendar-check-line me-1"></i> Tahunan (Yearly)</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Selected Room <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="room_display" class="form-control bg-light" readonly value="Room {{ $booking->room->room_number ?? 'N/A' }} ({{ $booking->room?->roomType?->name ?? 'Deleted Type' }})">
                                <input type="hidden" id="room_id" name="room_id" value="{{ old('room_id', $booking->room_id) }}" required>
                                @php
                                    $bookingKostMonths = max(1, (int) round($booking->check_in->diffInDays($booking->check_out) / 30));
                                    $bookingMonthlyRate = $booking->stay_type === 'monthly' ? round($booking->base_price / $bookingKostMonths) : null;
                                @endphp
                                <input type="hidden" id="manual_price" name="manual_price" value="{{ old('manual_price', ($booking->stay_type === 'monthly') ? $bookingMonthlyRate : ($booking->pricing_breakdown[0]['price'] ?? $booking->base_price)) }}">
                                <input type="hidden" id="tier_applied" name="tier_applied" value="{{ old('tier_applied', $booking->pricing_breakdown['tier_applied'] ?? 'public') }}">
                                <button class="btn btn-primary" type="button" id="btnBrowseRooms"><i class="ri-search-eye-line me-1"></i> Browse</button>
                            </div>
                            <div id="selected_room_info_box" class="mt-2">
                                <span class="badge bg-success-subtle text-success" id="selected_room_type">{{ $booking->room?->roomType?->name ?? 'Deleted Type' }}</span>
                                <span class="badge bg-info-subtle text-info" id="selected_room_price">Rp {{ number_format(($booking->stay_type === 'monthly') ? $bookingMonthlyRate : ($booking->pricing_breakdown[0]['price'] ?? $booking->base_price), 0, ',', '.') }} / {{ $booking->stay_type === 'monthly' ? 'bulan' : 'night' }}</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="check_in" class="form-label">Check-in Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $booking->check_in->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="check_in_time" class="form-label">Jam <span class="text-danger">*</span></label>
                                <select name="check_in_time" id="check_in_time" class="form-select" required>
                                    @foreach(['00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00','23:30'] as $t)
                                    <option value="{{ $t }}" @selected(old('check_in_time', $booking->check_in_time ?? '14:00') === $t)>{{ $t }} @if($t >= '00:00' && $t < '06:00')(Dini Hari)@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="stay_duration" class="form-label">Nights</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_minus_days">-</button>
                                    <input type="number" class="form-control text-center" id="stay_duration" value="{{ max(1, $booking->check_in->diffInDays($booking->check_out)) }}" min="1" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="btn_plus_days">+</button>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="check_out" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $booking->check_out->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="check_out_time" class="form-label">Jam Check-out</label>
                                <input type="text" class="form-control" id="check_out_time_display" value="{{ old('check_out_time', $booking->check_out_time ?? '12:00') }}" readonly disabled>
                                <input type="hidden" name="check_out_time" value="{{ old('check_out_time', $booking->check_out_time ?? '12:00') }}">
                            </div>
                        </div>
                        <div class="alert alert-warning d-none mt-2" id="earlyCheckinAlert">
                            <i class="ri-moon-line me-2"></i>
                            <strong>Check-in Dini Hari!</strong>
                            <p class="mb-0 mt-1">Tamu check-in setelah tengah malam. Dihitung sebagai malam sebelumnya. Checkout tetap <strong id="alertCheckoutDate">-</strong> jam 12:00 siang.</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="adults" class="form-label">Adults <span class="text-danger">*</span></label>
                                <select class="form-select" name="adults" id="adults" required>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('adults', $booking->adults) == $i ? 'selected' : '' }}>{{ $i }} Adult(s)</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="children" class="form-label">Children</label>
                                <select class="form-select" name="children" id="children">
                                    @for($i = 0; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('children', $booking->children) == $i ? 'selected' : '' }}>{{ $i }} Child(ren)</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="booking_status" class="form-label">Booking Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="booking_status" name="status" required>
                                    @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_in' => 'Checked In', 'checked_out' => 'Checked Out', 'cancelled' => 'Cancelled', 'no_show' => 'No Show'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', $booking->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="payment_status_select" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_status_select" name="payment_status" required>
                                    @foreach(['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid', 'refunded' => 'Refunded'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('payment_status', $booking->payment_status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="booking_source_id" class="form-label">Sumber Booking</label>
                            <select class="form-select" id="booking_source_id" name="booking_source_id" style="width:100%">
                                <option value="">-- Pilih Sumber --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests / Notes</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3">{{ old('special_requests', $booking->notes) }}</textarea>
                        </div>

                        {{-- Voucher Code --}}
                        <div class="mb-3">
                            <label class="form-label">Voucher Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="voucher_code" name="voucher_code" value="{{ old('voucher_code', $booking->voucher_code) }}" placeholder="ENTER CODE" style="text-transform: uppercase;">
                                <button class="btn btn-soft-secondary" type="button" id="applyVoucherBtn">Apply</button>
                            </div>
                            <div id="voucherResponse" class="mt-1"></div>
                            @if($booking->voucher_code)
                                <small class="text-success"><i class="ri-check-line me-1"></i>Voucher aktif: {{ $booking->voucher_code }}</small>
                            @endif
                        </div>

                        <div class="row mb-3" id="deposit_row">
                            <div class="col-md-12">
                                <label for="deposit_amount" class="form-label text-muted text-uppercase fw-semibold fs-11">Security Deposit (Jaminan)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" value="{{ old('deposit_amount', $booking->deposit_amount ?? 0) }}" min="0">
                                </div>
                                <small class="text-muted">Isi 0 jika tanpa deposit.</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="include_breakfast" id="include_breakfast" value="1" onchange="calculatePrice()" {{ old('include_breakfast', $booking->include_breakfast) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="include_breakfast">Include Breakfast?</label>
                                </div>
                                <small class="text-muted">Breakfast price from room master (per malam).</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Price Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span>Room Rate:</span><span id="roomRate">Rp {{ number_format($booking->pricing_breakdown[0]['price'] ?? $booking->base_price, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Nights:</span><span id="numNights">{{ max(1, $booking->check_in->diffInDays($booking->check_out)) }}</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span id="subtotal">Rp {{ number_format($booking->base_price, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="discountRow" style="display: {{ $booking->discount_amount > 0 ? 'flex' : 'none' }};"><span>Discount:</span><span id="discountAmount">- Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2" id="depositRowSummary" style="display: {{ ($booking->deposit_amount ?? 0) > 0 ? 'flex' : 'none' }};"><span>Security Deposit:</span><span id="depositAmountSummary">Rp {{ number_format($booking->deposit_amount ?? 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2" id="breakfastRowPerPerson" style="display: {{ $booking->include_breakfast ? 'flex' : 'none' }};"><span class="text-muted">Breakfast / Person / Night:</span><span id="breakfastPerPerson" class="text-muted">Rp {{ number_format($booking->include_breakfast ? (($booking->pricing_breakdown['breakfast_total'] ?? 0) / max(1, ($booking->adults + $booking->children)) / max(1, $booking->check_in->diffInDays($booking->check_out))) : 0, 0, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between mb-2" id="breakfastRowSummary" style="display: {{ $booking->include_breakfast ? 'flex' : 'none' }};"><span class="text-success">Breakfast Total:</span><span id="breakfastAmountSummary" class="text-success">Rp {{ number_format($booking->pricing_breakdown['breakfast_total'] ?? 0, 0, ',', '.') }}</span></div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3"><span class="fs-16 fw-bold">Grand Total:</span><span id="totalPrice" class="fs-16 fw-bold text-success">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span></div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" data-submit-protect="true" class="btn btn-success btn-lg">Update Booking</button>
                    <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title">Select Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" class="form-control search" id="filter_room_search" placeholder="Cari nomor kamar..." oninput="filterRoomCards()">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-4"><select class="form-select" id="filter_room_type" onchange="loadAvailableRooms()"><option value="">All Types</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                    </div>
                    <div class="row g-3" id="room_list_container"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#guest_id').select2({
                ajax: { url: '{{ route("guests.search") }}', dataType: 'json', delay: 250, data: (p) => ({ q: p.term }), processResults: (d) => ({ results: d }), cache: true },
                placeholder: 'Search guest...', minimumInputLength: 2
            }).on('change', function() { if($('#room_id').val()) loadAvailableRooms(); });

            const currentSourceId = @json(old('booking_source_id', $booking->booking_source_id));
            $.getJSON('{{ route("booking-sources.api") }}', function(data) {
                $.each(data, function(i, src) {
                    $('#booking_source_id').append(new Option(src.name, src.id, false, src.id === currentSourceId));
                });
                $('#booking_source_id').select2({ placeholder: '-- Pilih Sumber --', allowClear: true });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const checkIn = document.getElementById('check_in');
            const checkOut = document.getElementById('check_out');
            const checkInTime = document.getElementById('check_in_time');
            const earlyAlert = document.getElementById('earlyCheckinAlert');
            const alertCheckoutDate = document.getElementById('alertCheckoutDate');
            const stayDuration = document.getElementById('stay_duration');
            window.currentRoomPrice = parseFloat(document.getElementById('manual_price').value) || 0;
            window.appliedDiscountAmount = {{ (float) $booking->discount_amount }};
            window.roomBreakfastPrice = 0;
            @if($booking->include_breakfast && $booking->room)
                @php
                    $tier = $booking->pricing_breakdown['tier_applied'] ?? 'public';
                    $bfPrice = match($tier) {
                        'sales' => $booking->room->price_breakfast_sales ?? 0,
                        'high_season' => $booking->room->price_breakfast_high_season ?? 0,
                        default => $booking->room->price_breakfast_public ?? 0,
                    };
                @endphp
                window.roomBreakfastPrice = {{ (float) $bfPrice }};
            @endif
            window.currentTier = '{{ $booking->pricing_breakdown["tier_applied"] ?? "public" }}';
            window.originalDepositAmount = {{ (float) ($booking->deposit_amount ?? 0) }};

            window.formatRupiah = function(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n); };

            window.confirmDepositChange = function() {
                const newDeposit = parseFloat(document.getElementById('deposit_amount').value) || 0;
                if (window.originalDepositAmount > 0 && newDeposit < window.originalDepositAmount) {
                    return confirm('Deposit akan berubah dari ' + window.formatRupiah(window.originalDepositAmount) + ' menjadi ' + window.formatRupiah(newDeposit) + '. Lanjutkan?');
                }
                return true;
            };

            function updateEarlyCheckinAlert() {
                const time = checkInTime.value;
                const isDiniHari = time >= '00:00' && time < '06:00';
                if (isDiniHari && checkIn.value) {
                    earlyAlert.classList.remove('d-none');
                    alertCheckoutDate.textContent = checkOut.value;
                } else {
                    earlyAlert.classList.add('d-none');
                }
            }

            function disablePastCheckinTimes() {
                const now = new Date();
                const currentTotal = parseInt(now.getHours().toString().padStart(2, '0') + now.getMinutes().toString().padStart(2, '0'));
                Array.from(checkInTime.options).forEach(opt => {
                    if (!opt.value) return;
                    const optTotal = parseInt(opt.value.replace(':', ''));
                    opt.disabled = optTotal < currentTotal && checkIn.value === now.toISOString().split('T')[0];
                });
                if (checkInTime.selectedOptions[0]?.disabled) {
                    const firstEnabled = Array.from(checkInTime.options).find(o => !o.disabled && o.value);
                    if (firstEnabled) checkInTime.value = firstEnabled.value;
                }
            }

            function calculatePrice(manualPrice = null) {
                let price = parseFloat(manualPrice || window.currentRoomPrice || 0);
                window.currentRoomPrice = price;
                let nights = parseInt(stayDuration.value) || 1;
                const stayType = document.querySelector('input[name="stay_type"]:checked').value;
                let kostMonths = stayType === 'monthly' ? Math.max(1, Math.round(nights / 30)) : 1;
                let subtotal = stayType === 'monthly' ? price * kostMonths : price * nights;
                let deposit = parseFloat(document.getElementById('deposit_amount').value) || 0;
                let discount = parseFloat(window.appliedDiscountAmount) || 0;

                // Breakfast Logic
                let breakfastTotal = 0;
                let breakfastPerPerson = 0;
                const includeBreakfastCheck = document.getElementById('include_breakfast');
                if (includeBreakfastCheck && includeBreakfastCheck.checked) {
                    const roomBreakfastPrice = parseFloat(window.roomBreakfastPrice) || 0;
                    const adults = parseInt(document.querySelector('select[name="adults"]')?.value) || 0;
                    const children = parseInt(document.querySelector('select[name="children"]')?.value) || 0;
                    // Harga breakfast sudah per kamar (include 2 orang)
                    breakfastPerPerson = roomBreakfastPrice;
                    breakfastTotal = roomBreakfastPrice * nights;
                }

                let grandTotal = Math.max(0, subtotal - discount) + deposit + breakfastTotal;

                document.getElementById('roomRate').textContent = window.formatRupiah(price);
                document.getElementById('numNights').textContent = nights;
                document.getElementById('subtotal').textContent = window.formatRupiah(subtotal);
                document.getElementById('discountRow').style.display = discount > 0 ? 'flex' : 'none';
                document.getElementById('discountAmount').textContent = '- ' + window.formatRupiah(discount);
                document.getElementById('depositRowSummary').style.display = deposit > 0 ? 'flex' : 'none';
                document.getElementById('depositAmountSummary').textContent = window.formatRupiah(deposit);

                const bfRowPerPerson = document.getElementById('breakfastRowPerPerson');
                if(bfRowPerPerson) {
                    bfRowPerPerson.style.display = breakfastTotal > 0 ? 'flex' : 'none';
                    document.getElementById('breakfastPerPerson').textContent = window.formatRupiah(breakfastPerPerson);
                }
                const bfRow = document.getElementById('breakfastRowSummary');
                if(bfRow) {
                    bfRow.style.display = breakfastTotal > 0 ? 'flex' : 'none';
                    document.getElementById('breakfastAmountSummary').textContent = window.formatRupiah(breakfastTotal);
                }

                document.getElementById('totalPrice').textContent = window.formatRupiah(grandTotal);
            }

            function updateDates() {
                if(checkIn.value) {
                    let nights = parseInt(stayDuration.value);
                    if (checkInTime.value >= '00:00' && checkInTime.value < '06:00') {
                        nights = Math.max(0, nights - 1);
                    }
                    let d = new Date(checkIn.value);
                    d.setDate(d.getDate() + nights);
                    checkOut.value = d.toISOString().split('T')[0];
                    updateEarlyCheckinAlert();
                    calculatePrice();
                }
            }

            function updateDuration() {
                let start = new Date(checkIn.value);
                let end = new Date(checkOut.value);
                let diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                stayDuration.value = diff > 0 ? diff : 1;
                calculatePrice();
            }

            document.getElementById('btn_plus_days').onclick = () => { stayDuration.value++; updateDates(); };
            document.getElementById('btn_minus_days').onclick = () => { if(stayDuration.value > 1) { stayDuration.value--; updateDates(); } };
            checkIn.onchange = () => { updateDates(); disablePastCheckinTimes(); };
            checkOut.onchange = updateDuration;
            checkInTime.onchange = updateEarlyCheckinAlert;
            disablePastCheckinTimes();
            document.getElementById('deposit_amount').oninput = () => calculatePrice();

            document.querySelectorAll('input[name="stay_type"]').forEach(input => {
                input.addEventListener('change', function() {
                    document.getElementById('room_id').value = '';
                    document.getElementById('room_display').value = '';
                    document.getElementById('selected_room_info_box').style.display = 'none';
                    window.currentRoomPrice = 0;
                    document.getElementById('manual_price').value = '';
                    if (this.value === 'monthly') {
                        stayDuration.value = 30;
                    } else {
                        stayDuration.value = 1;
                    }
                    updateDates();
                });
            });

            const roomModal = new bootstrap.Modal(document.getElementById('roomModal'));
            document.getElementById('btnBrowseRooms').onclick = () => {
                const guestId = $('#guest_id').val();
                if (!guestId) {
                    Toastify({ text: "Silakan pilih tamu (Guest) terlebih dahulu sebelum memilih kamar.", duration: 3000, gravity: "top", position: "right", style: { background: "#f06548" } }).showToast();
                    return;
                }
                roomModal.show();
                loadAvailableRooms();
            };

            window.loadAvailableRooms = function() {
                const stayType = document.querySelector('input[name="stay_type"]:checked').value;
                const params = new URLSearchParams({
                    check_in: checkIn.value,
                    check_out: checkOut.value,
                    check_in_time: document.getElementById('check_in_time')?.value || '',
                    check_out_time: document.querySelector('input[name="check_out_time"]')?.value || '',
                    room_type_id: $('#filter_room_type').val(),
                    guest_id: $('#guest_id').val(),
                    stay_type: stayType
                });
                fetch(`{{ route('bookings.available-rooms') }}?${params.toString()}`).then(r => r.json()).then(rooms => {
                    const container = document.getElementById('room_list_container');
                    container.innerHTML = '';
                    rooms.forEach(room => {
                        let priceSection = '';
                        let hasBreakfast = false;
                        if (stayType === 'monthly') {
                            priceSection = `<button class="btn btn-sm btn-soft-success w-100 mt-2" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_kos}, 0, 'public')">Pilih (Kos): ${window.formatRupiah(room.price_kos)}</button>`;
                        } else {
                            const bfPublic = room.price_breakfast_public || 0;
                            const bfSales = room.price_breakfast_sales || 0;
                            const bfHighSeason = room.price_breakfast_high_season || 0;
                            hasBreakfast = bfPublic > 0 || bfSales > 0 || bfHighSeason > 0;
                            priceSection = `<div class="d-grid gap-1 mt-2"><button class="btn btn-sm btn-soft-primary" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_public}, ${bfPublic}, 'public')">Umum: ${window.formatRupiah(room.price_public)}${bfPublic > 0 ? '<br><small class=\\"text-muted\\">+ Breakfast: ' + window.formatRupiah(bfPublic) + '</small>' : ''}</button><button class="btn btn-sm btn-soft-info" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_sales}, ${bfSales}, 'sales')">Sales: ${window.formatRupiah(room.price_sales)}${bfSales > 0 ? '<br><small class=\\"text-muted\\">+ Breakfast: ' + window.formatRupiah(bfSales) + '</small>' : ''}</button><button class="btn btn-sm btn-soft-danger" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_high_season}, ${bfHighSeason}, 'high_season')">High Season: ${window.formatRupiah(room.price_high_season)}${bfHighSeason > 0 ? '<br><small class=\\"text-muted\\">+ Breakfast: ' + window.formatRupiah(bfHighSeason) + '</small>' : ''}</button></div>`;
                        }
                        const col = document.createElement('div');
                        col.className = 'col-md-3';
                        col.innerHTML = `<div class="card border shadow-none h-100"><div class="card-body p-3"><h5 class="fs-14 mb-1">Room ${room.room_number}</h5><span class="badge bg-primary-subtle text-primary mb-2">${room.room_type}</span>${hasBreakfast ? '<span class="badge bg-warning-subtle text-warning mb-2 ms-1">Breakfast</span>' : ''}${priceSection}</div></div>`;
                        container.appendChild(col);
                    });
                });
            };

            window.filterRoomCards = function() {
                const search = document.getElementById('filter_room_search').value.toLowerCase();
                const cards = document.querySelectorAll('#room_list_container > div');
                cards.forEach(card => {
                    const roomNum = card.querySelector('h5')?.textContent?.toLowerCase() || '';
                    card.style.display = roomNum.includes(search) ? '' : 'none';
                });
            };

            window.selectRoom = (id, num, type, price, breakfastPrice = 0, tier = 'public') => {
                document.getElementById('room_id').value = id;
                document.getElementById('manual_price').value = price;
                document.getElementById('tier_applied').value = tier;
                window.roomBreakfastPrice = breakfastPrice;
                window.currentTier = tier;
                document.getElementById('room_display').value = `Room ${num} (${type})`;
                document.getElementById('selected_room_info_box').style.display = 'block';
                document.getElementById('selected_room_type').textContent = type;
                document.getElementById('selected_room_price').textContent = window.formatRupiah(price) + ' / night' + (breakfastPrice > 0 ? ` (+ Breakfast: ${window.formatRupiah(breakfastPrice)})` : '');
                calculatePrice(price);
                roomModal.hide();
            };

            calculatePrice();

            // Voucher apply
            document.getElementById('applyVoucherBtn').onclick = function() {
                var code = document.getElementById('voucher_code').value.trim();
                var responseEl = document.getElementById('voucherResponse');
                if (!code) {
                    responseEl.innerHTML = '<span class="text-danger fs-12">Masukkan kode voucher</span>';
                    window.appliedDiscountAmount = 0;
                    calculatePrice();
                    return;
                }
                var subtotal = window.currentRoomPrice * (parseInt(document.getElementById('stay_duration').value) || 1);
                var roomId = document.getElementById('room_id') ? document.getElementById('room_id').value : '';
                var params = new URLSearchParams({ code: code, subtotal: subtotal, room_id: roomId });
                fetch('{{ route("vouchers.check") }}?' + params.toString())
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        window.appliedDiscountAmount = d.success ? d.discount_amount : 0;
                        responseEl.innerHTML = '<span class="text-' + (d.success ? 'success' : 'danger') + ' fs-12">' + d.message + '</span>';
                        calculatePrice();
                    })
                    .catch(function() {
                        responseEl.innerHTML = '<span class="text-danger fs-12">Gagal memvalidasi voucher</span>';
                    });
            };
        });
    </script>
@endsection
