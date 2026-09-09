@extends('layouts.master')
@section('title')
    New Booking
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
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
        @slot('title') New Booking @endslot
    @endcomponent

    <form action="{{ route('bookings.store') }}" method="POST"  id="bookingForm" data-ajax="true" data-ajax-redirect="{{ route('bookings.index') }}">
        @csrf
        
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Global Validation Errors --}}
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

        <div class="row">
            <div class="col-lg-8">
                {{-- Guest Selection --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-user-fill me-2 text-primary"></i>Guest Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="guest_id" class="form-label">Guest <span class="text-danger">*</span></label>
                            <select class="form-control" id="guest_id" name="guest_id" required>
                                <option value="">Type to search guest name, phone, or ID...</option>
                            </select>
                            <div class="mt-2">
                                <small class="text-muted">Can't find the guest? <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addGuestModal" class="btn btn-sm btn-soft-primary py-0 px-2 ms-1">Add New Guest</a></small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Room & Dates --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-hotel-bed-fill me-2 text-success"></i>Room & Dates</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label d-block text-muted text-uppercase fw-semibold fs-11">Stay Type</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="stay_type" id="stay_daily" value="daily" checked>
                                <label class="btn btn-outline-primary" for="stay_daily"><i class="ri-calendar-event-line me-1"></i> Harian (Daily)</label>
                                <input type="radio" class="btn-check" name="stay_type" id="stay_monthly" value="monthly">
                                <label class="btn btn-outline-primary" for="stay_monthly"><i class="ri-calendar-2-line me-1"></i> Bulanan (Kos)</label>
                                <input type="radio" class="btn-check" name="stay_type" id="stay_yearly" value="yearly">
                                <label class="btn btn-outline-primary" for="stay_yearly"><i class="ri-calendar-check-line me-1"></i> Tahunan (Yearly)</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="check_in" class="form-label">Check-in Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="check_in" name="check_in" value="{{ now()->toDateString() }}" {{ (auth()->user()->can('manage reservations') || auth()->user()->can('bookings.create')) ? '' : 'min="'.now()->toDateString().'"' }} required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="check_in_time" class="form-label">Jam <span class="text-danger">*</span></label>
                                <select name="check_in_time" id="check_in_time" class="form-select" required>
                                    @foreach(['00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','07:00','08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00','23:30'] as $t)
                                    <option value="{{ $t }}" @selected($t === '14:00')>{{ $t }} @if($t >= '00:00' && $t < '06:00')(Dini Hari)@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="stay_duration" class="form-label">Nights</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_minus_days">-</button>
                                    <input type="number" class="form-control text-center" id="stay_duration" value="1" min="1" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="btn_plus_days">+</button>
                                </div>
                            </div>

                            {{-- Kost Duration Selector (hidden by default, shown when monthly) --}}
                            <div class="col-md-3 mb-3" id="kost_duration_row" style="display: none;">
                                <label for="kost_months" class="form-label">Durasi Kost</label>
                                <select class="form-select" id="kost_months" name="kost_duration_months">
                                    <option value="1">1 Bulan</option>
                                    <option value="3">3 Bulan</option>
                                    <option value="6">6 Bulan</option>
                                    <option value="12">12 Bulan (1 Tahun)</option>
                                    <option value="custom">Custom...</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3" id="kost_custom_row" style="display: none;">
                                <label for="kost_custom_months" class="form-label">Jumlah Bulan</label>
                                <input type="number" class="form-control" id="kost_custom_months" min="1" max="24" value="1">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="check_out" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out" name="check_out"
                                       value="{{ request('check_out') }}"
                                       {{ (auth()->user()->can('manage reservations') || auth()->user()->can('bookings.create')) ? '' : 'min="'.now()->toDateString().'"' }} required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="check_out_time" class="form-label">Jam Check-out</label>
                                <input type="text" class="form-control" id="check_out_time_display" value="12:00" readonly disabled>
                                <input type="hidden" name="check_out_time" value="12:00">
                            </div>
                        </div>
                        <div class="alert alert-warning d-none mt-2" id="earlyCheckinAlert">
                            <i class="ri-moon-line me-2"></i>
                            <strong>Check-in Dini Hari!</strong>
                            <p class="mb-0 mt-1">Tamu check-in setelah tengah malam. Dihitung sebagai malam sebelumnya. Checkout tetap <strong id="alertCheckoutDate">-</strong> jam 12:00 siang.</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Selected Room <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="room_display" class="form-control bg-light" readonly placeholder="Pilih tanggal di atas, lalu klik Browse...">
                                <input type="hidden" id="room_id" name="room_id" required>
                                <input type="hidden" id="manual_price" name="manual_price">
                                <input type="hidden" id="tier_applied" name="tier_applied" value="">
                                <button class="btn btn-primary" type="button" id="btnBrowseRooms"><i class="ri-search-eye-line me-1"></i> Browse</button>
                            </div>
                            <div id="selected_room_info_box" class="mt-2" style="display: none;">
                                <span class="badge bg-success-subtle text-success" id="selected_room_type"></span>
                                <span class="badge bg-info-subtle text-info" id="selected_room_price"></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="adults" class="form-label">Adults <span class="text-danger">*</span></label>
                                <select class="form-select" name="adults" id="adults" required>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('adults', 1) == $i ? 'selected' : '' }}>{{ $i }} Adult(s)</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="children" class="form-label">Children</label>
                                <select class="form-select" name="children" id="children">
                                    @for($i = 0; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('children', 0) == $i ? 'selected' : '' }}>{{ $i }} Child(ren)</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="booking_source_id" class="form-label">Sumber Booking</label>
                            <select class="form-select" id="booking_source_id" name="booking_source_id" style="width:100%">
                                <option value="">-- Pilih Sumber --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="ri-ticket-line me-2 text-info"></i>Voucher & Notes</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="voucher_code" class="form-label">Voucher Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="voucher_code" name="voucher_code" placeholder="ENTER CODE" style="text-transform: uppercase;">
                                <button class="btn btn-soft-secondary" type="button" id="applyVoucher">Apply</button>
                            </div>
                            <div id="voucherResponse" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests / Notes</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="2" placeholder="Any notes..."></textarea>
                        </div>

                        <div class="row" id="deposit_row">
                            <div class="col-md-12 mb-3">
                                <label for="deposit_amount" class="form-label text-muted text-uppercase fw-semibold fs-11">Security Deposit (Jaminan)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="deposit_amount" id="deposit_amount" value="0" min="0">
                                </div>
                                <small class="text-muted">Isi 0 jika tanpa deposit. Deposit juga bisa ditambahkan lewat checkbox saat Check In.</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="include_breakfast" id="include_breakfast" value="1" onchange="calculatePrice()">
                                    <label class="form-check-label fw-bold" for="include_breakfast">Include Breakfast?</label>
                                </div>
                                <small class="text-muted">Harga breakfast per kamar per malam (sudah termasuk 2 orang).</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Price Summary --}}
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Price Summary</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span>Room Rate:</span><span id="roomRate">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2 text-muted" id="kostOriginalRow" style="display: none;"><span class="text-decoration-line-through">Original Rate:</span><span id="kostOriginalRate" class="text-decoration-line-through">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="kostSavingsRow" style="display: none;"><span>Diskon Durasi:</span><span id="kostSavingsAmount">-Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Nights:</span><span id="numNights">0</span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span id="subtotal">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2 text-success" id="discountRow" style="display: none;"><span>Discount:</span><span id="discountAmount">-Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2" id="depositRowSummary" style="display: none;"><span>Security Deposit:</span><span id="depositAmountSummary">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2" id="extraPersonRow" style="display: none;"><span>Extra Person:</span><span id="extraPersonAmount" class="text-info">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2" id="breakfastRowPerPerson" style="display: none;"><span class="text-muted">Breakfast / Person / Night:</span><span id="breakfastPerPerson" class="text-muted">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2" id="breakfastRowSummary" style="display: none;"><span class="text-success">Breakfast Total:</span><span id="breakfastAmountSummary" class="text-success">Rp 0</span></div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3"><span class="fs-16 fw-bold">Grand Total:</span><span id="totalPrice" class="fs-16 fw-bold text-success">Rp 0</span></div>
                        
                        <div class="bg-light p-3 rounded border border-dashed mb-3">
                            <label class="form-label text-muted text-uppercase fw-semibold fs-11">Initial Payment (Down Payment)</label>
                            <div class="row g-2 mb-2">
                                <div class="col-8">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="down_payment" name="down_payment" value="0">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control" id="down_payment_percent" placeholder="0" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-2" id="payment_account_div" style="display: none;">
                                <label class="form-label fs-11 mb-1">Payment Account</label>
                                <select class="form-select form-select-sm" name="bank_account_id" id="bank_account_id">
                                    <option value="">Select Account...</option>
                                    @foreach(\App\Models\BankAccount::where('hotel_id', active_hotel_id())->where('is_active', true)->get() as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex justify-content-between mt-2">
                                <span class="fs-13 text-muted">Remaining Balance:</span>
                                <span id="remaining_balance" class="fw-bold text-danger">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" data-submit-protect="true" class="btn btn-success btn-lg" data-submit-protect="true">Create Booking</button>
                    <a href="{{ route('bookings.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Modals --}}
    <div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-light"><h5 class="modal-title" id="roomModalTitle">Select Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="search-box">
                                <input type="text" class="form-control search" id="filter_room_search" placeholder="Cari nomor kamar..." oninput="filterRoomCards()">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="filter_room_type">
                                <option value="">All Types</option>
                                @foreach($roomTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="show_all_rooms">
                                <label class="form-check-label fs-11 text-muted" for="show_all_rooms">Show All (incl. occupied)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3" id="room_list_container"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addGuestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Add New Guest</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addGuestForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div id="guestValidationErrors" class="alert alert-danger d-none"></div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="Full Name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Guest Category</label>
                                <select class="form-select" name="guest_category_id" id="guest_category_select">
                                    <option value="">General</option>
                                    @foreach(\App\Models\GuestCategory::all() as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" placeholder="Phone Number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Email Address</label>
                                <input type="email" class="form-control" name="email" placeholder="Email Address">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Identity Type</label>
                                <select class="form-select" name="identity_type">
                                    <option value="KTP">KTP</option>
                                    <option value="Passport">Passport</option>
                                    <option value="SIM">SIM</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">ID Number (KTP/Passport) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="id_number" placeholder="ID Number" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Citizenship</label>
                                <select class="form-select" name="citizenship_code">
                                    <option value="WNI">WNI (Warga Negara Indonesia)</option>
                                    <option value="WNA">WNA (Warga Negara Asing)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Nationality</label>
                                <input type="text" class="form-control" name="nationality" placeholder="e.g. Indonesian">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Gender</label>
                                <div class="mt-1">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male" checked>
                                        <label class="form-check-label" for="genderMale">Male</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female">
                                        <label class="form-check-label" for="genderFemale">Female</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Company Name</label>
                                <input type="text" class="form-control" name="company_name" placeholder="Optional">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Vehicle Number</label>
                                <input type="text" class="form-control" name="vehicle_number" placeholder="e.g. B 1234 ABC">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Full Address"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Reference Source</label>
                                <input type="text" class="form-control" name="reference_source" placeholder="e.g. Traveloka, Tiket.com, Walk-in">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label text-muted text-uppercase fw-semibold fs-11">Photo ID (Optional)</label>
                                <input type="file" class="form-control" name="id_card_photo" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btnSaveGuest">Save Guest</button>
                    </div>
                </form>
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
            $('#guest_category_select').select2({ dropdownParent: $('#addGuestModal') });

            $.getJSON('{{ route("booking-sources.api") }}', function(data) {
                $.each(data, function(i, src) {
                    $('#booking_source_id').append(new Option(src.name, src.id));
                });
                $('#booking_source_id').select2({ placeholder: '-- Pilih Sumber --', allowClear: true });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.canBackdate = {{ (auth()->user()->can('manage reservations') || auth()->user()->can('bookings.create')) ? 'true' : 'false' }};
            const checkIn = document.getElementById('check_in');
            const checkOut = document.getElementById('check_out');
            const checkInTime = document.getElementById('check_in_time');
            const checkOutTimeHidden = document.querySelector('input[name="check_out_time"]');
            const earlyAlert = document.getElementById('earlyCheckinAlert');
            const alertCheckoutDate = document.getElementById('alertCheckoutDate');
            const stayDuration = document.getElementById('stay_duration');
            window.appliedDiscountAmount = 0;

            // Make formatRupiah globally available
            window.formatRupiah = function(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n); };

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
                const today = '{{ get_hotel_date() }}';
                const now = new Date();
                const currentHours = now.getHours().toString().padStart(2, '0');
                const currentMinutes = now.getMinutes().toString().padStart(2, '0');
                const currentTotal = parseInt(currentHours + currentMinutes);

                if (window.canBackdate) {
                    checkIn.min = '';
                    Array.from(checkInTime.options).forEach(opt => {
                        if (!opt.value) return;
                        opt.disabled = false;
                    });
                    return;
                }

                // If checkout is today, allow check-in from yesterday with all times open
                if (checkOut.value && checkOut.value <= today) {
                    const yesterday = new Date(now);
                    yesterday.setDate(yesterday.getDate() - 1);
                    checkIn.min = yesterday.toISOString().split('T')[0];

                    Array.from(checkInTime.options).forEach(opt => {
                        opt.disabled = false;
                    });
                } else {
                    checkIn.min = today;
                    Array.from(checkInTime.options).forEach(opt => {
                        if (!opt.value) return;
                        const optTotal = parseInt(opt.value.replace(':', ''));
                        opt.disabled = optTotal < currentTotal && checkIn.value === today;
                    });
                }

                if (checkInTime.selectedOptions[0]?.disabled) {
                    const firstEnabled = Array.from(checkInTime.options).find(o => !o.disabled && o.value);
                    if (firstEnabled) checkInTime.value = firstEnabled.value;
                }
            }

            function calculatePrice(manualPrice = null) {
                // If overridden globally, use that
                if (window.calculatePrice && window.calculatePrice !== calculatePrice) {
                    return window.calculatePrice(manualPrice);
                }
                
                let price = parseFloat(manualPrice || window.currentRoomPrice || 0);
                window.currentRoomPrice = price;
                window.roomBreakfastPrice = window.roomBreakfastPrice || 0;
                window.roomExtraPersonPrice = window.roomExtraPersonPrice || 0;
                let nights = parseInt(stayDuration.value) || 1;
                const stayType = document.querySelector('input[name="stay_type"]:checked').value;
                let kostMonths = 1;
                if (stayType === 'monthly' || stayType === 'yearly') {
                    const selector = document.getElementById('kost_months');
                    kostMonths = selector.value === 'custom' 
                        ? (parseInt(document.getElementById('kost_custom_months').value) || 1) 
                        : (parseInt(selector.value) || 1);
                }
                let subtotal = (stayType === 'monthly' || stayType === 'yearly') ? price * kostMonths : price * nights;
                let deposit = parseFloat(document.getElementById('deposit_amount').value) || 0;
                let discount = parseFloat(window.appliedDiscountAmount) || 0;
                let subAfterDisc = Math.max(0, subtotal - discount);
                
                const bfCheckbox = document.getElementById('include_breakfast');
                let breakfastTotal = 0;
                let breakfastPerPerson = 0;
                if(bfCheckbox && bfCheckbox.checked) {
                    // Harga breakfast sudah per kamar (include 2 orang)
                    breakfastPerPerson = window.roomBreakfastPrice;
                    breakfastTotal = breakfastPerPerson * nights;
                }
                
                // Extra person charge
                const adults = parseInt(document.getElementById('adults').value) || 1;
                const extraPersonPrice = window.roomExtraPersonPrice || 0;
                const extraPersons = Math.max(0, adults - 1);
                const extraPersonCharge = extraPersons * extraPersonPrice * nights;
                
                let grandTotal = subAfterDisc + deposit + breakfastTotal + extraPersonCharge;

                document.getElementById('roomRate').textContent = window.formatRupiah(price);
                document.getElementById('numNights').textContent = nights;
                document.getElementById('subtotal').textContent = window.formatRupiah(subtotal);
                
                const discRow = document.getElementById('discountRow');
                if(discRow) discRow.style.display = discount > 0 ? 'flex' : 'none';
                const discAmt = document.getElementById('discountAmount');
                if(discAmt) discAmt.textContent = '- ' + window.formatRupiah(discount);
                
                const depRow = document.getElementById('depositRowSummary');
                if(depRow) depRow.style.display = deposit > 0 ? 'flex' : 'none';
                const depAmt = document.getElementById('depositAmountSummary');
                if(depAmt) depAmt.textContent = window.formatRupiah(deposit);

                const extraRow = document.getElementById('extraPersonRow');
                if(extraRow) extraRow.style.display = extraPersonCharge > 0 ? 'flex' : 'none';
                const extraAmt = document.getElementById('extraPersonAmount');
                if(extraAmt) extraAmt.textContent = window.formatRupiah(extraPersonCharge);

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

                // Initial Payment Logic
                const downPaymentInput = document.getElementById('down_payment');
                const downPaymentPercent = document.getElementById('down_payment_percent');
                let downPayment = parseFloat(downPaymentInput.value) || 0;
                
                if(document.activeElement === downPaymentPercent) {
                    const pct = parseFloat(downPaymentPercent.value) || 0;
                    downPayment = grandTotal * (pct / 100);
                    downPaymentInput.value = downPayment;
                } else if (grandTotal > 0 && document.activeElement !== downPaymentInput) {
                    downPaymentPercent.value = ((downPayment / grandTotal) * 100).toFixed(2);
                }
                
                const cbFullPayment = document.getElementById('cb_full_payment');
                if(cbFullPayment && cbFullPayment.checked) {
                    downPaymentPercent.value = 100;
                    downPayment = grandTotal;
                    downPaymentInput.value = downPayment;
                }

                const remaining = Math.max(0, grandTotal - downPayment);
                document.getElementById('remaining_balance').textContent = window.formatRupiah(remaining);
                
                // Show/hide bank account selection
                document.getElementById('payment_account_div').style.display = downPayment > 0 ? 'block' : 'none';
                document.getElementById('bank_account_id').required = downPayment > 0;
            }

            // Room availability depends on the chosen dates & times -- once they
            // change, a previously selected room may no longer be free, so force
            // the user to re-browse.
            window.clearSelectedRoom = function(notify = false) {
                const hadRoom = !!document.getElementById('room_id').value;
                document.getElementById('room_id').value = '';
                document.getElementById('room_display').value = '';
                document.getElementById('selected_room_info_box').style.display = 'none';
                document.getElementById('manual_price').value = '';
                window.currentRoomPrice = 0;
                if (hadRoom && notify) {
                    Toastify({ text: 'Tanggal/jam berubah — silakan pilih kamar lagi (Browse).', duration: 3500, gravity: 'top', position: 'right', style: { background: '#f7b84b' } }).showToast();
                }
                calculatePrice();
            };

            document.getElementById('btn_plus_days').onclick = () => { stayDuration.value++; clearSelectedRoom(true); updateDates(); };
            document.getElementById('btn_minus_days').onclick = () => { if(stayDuration.value > 1) { stayDuration.value--; clearSelectedRoom(true); updateDates(); } };
            checkIn.onchange = () => { clearSelectedRoom(true); updateDates(); disablePastCheckinTimes(); };
            checkOut.onchange = () => { clearSelectedRoom(true); updateDuration(); disablePastCheckinTimes(); };
            checkInTime.onchange = () => { clearSelectedRoom(true); updateEarlyCheckinAlert(); };
            disablePastCheckinTimes();
            document.getElementById('deposit_amount').addEventListener('input', function() { calculatePrice(); });
            document.getElementById('deposit_amount').addEventListener('change', function() { calculatePrice(); });

            document.getElementById('down_payment').oninput = function() {
                const total = parseFloat(document.getElementById('totalPrice').textContent.replace(/[^0-9]/g, '')) || 0;
                const val = parseFloat(this.value) || 0;
                if (total > 0) {
                    document.getElementById('down_payment_percent').value = Math.round((val / total) * 100);
                }
                calculatePrice();
            };

            document.getElementById('down_payment_percent').oninput = function() {
                const total = parseFloat(document.getElementById('totalPrice').textContent.replace(/[^0-9]/g, '')) || 0;
                const percent = parseFloat(this.value) || 0;
                if (total > 0) {
                    document.getElementById('down_payment').value = Math.round((percent / 100) * total);
                }
                calculatePrice();
            };

            // Initialize values on page load
            updateDates();
            calculatePrice();

            // Robust Stay Type Toggle
            document.querySelectorAll('input[name="stay_type"]').forEach(input => {
                input.addEventListener('change', function() {
                    // Clear selected room when stay type changes
                    document.getElementById('room_id').value = '';
                    document.getElementById('room_display').value = '';
                    document.getElementById('selected_room_info_box').style.display = 'none';
                    window.currentRoomPrice = 0;

                    if (this.value === 'monthly') {
                        document.getElementById('kost_duration_row').style.display = 'block';
                        updateKostDuration();
                    } else if (this.value === 'yearly') {
                        document.getElementById('kost_duration_row').style.display = 'block';
                        stayDuration.value = 12;
                        updateKostDuration();
                    } else {
                        stayDuration.value = 1;
                        document.getElementById('kost_duration_row').style.display = 'none';
                        document.getElementById('kost_custom_row').style.display = 'none';
                        clearKostTierDisplay();
                    }
                    updateDates();
                });
            });

            // Kost Duration Handler
            document.getElementById('kost_months').addEventListener('change', function() {
                if (this.value === 'custom') {
                    document.getElementById('kost_custom_row').style.display = 'block';
                } else {
                    document.getElementById('kost_custom_row').style.display = 'none';
                }
                clearSelectedRoom(true);
                updateKostDuration();
            });

            document.getElementById('kost_custom_months').addEventListener('input', function() {
                clearSelectedRoom(true);
                updateKostDuration();
            });

            document.getElementById('filter_room_type').addEventListener('change', function() {
                loadAvailableRooms();
            });
            document.getElementById('show_all_rooms').addEventListener('change', function() {
                loadAvailableRooms();
            });

            function updateKostDuration() {
                const selector = document.getElementById('kost_months');
                let months = parseInt(selector.value);
                if (selector.value === 'custom') {
                    months = parseInt(document.getElementById('kost_custom_months').value) || 1;
                }
                stayDuration.value = months * 30;
                updateDates();
                fetchKostTierPrice();
            }

            function getKostDurationMonths() {
                const selector = document.getElementById('kost_months');
                if (selector.value === 'custom') {
                    return parseInt(document.getElementById('kost_custom_months').value) || 1;
                }
                return parseInt(selector.value) || 1;
            }

            function fetchKostTierPrice() {
                const roomId = window.selectedRoomId || document.getElementById('room_id').value;
                if (!roomId) {
                    calculatePrice(window.selectedRoomBasePrice || 0);
                    return;
                }
                const months = getKostDurationMonths();
                fetch(`{{ route('bookings.kost-price') }}?room_id=${roomId}&duration_months=${months}`)
                    .then(r => r.json())
                    .then(data => {
                        window.kostTierData = data;
                        applyKostTierPrice(data);
                    })
                    .catch(() => {
                        clearKostTierDisplay();
                        calculatePrice(window.selectedRoomBasePrice || 0);
                    });
            }

            function applyKostTierPrice(data) {
                window.kostTierData = data;
                document.getElementById('manual_price').value = data.monthly_rate;
                document.getElementById('roomRate').textContent = window.formatRupiah(data.monthly_rate);

                if (data.has_discount) {
                    document.getElementById('kostOriginalRow').style.display = 'flex';
                    document.getElementById('kostOriginalRate').textContent = window.formatRupiah(data.original_rate);
                    document.getElementById('kostSavingsRow').style.display = 'flex';
                    document.getElementById('kostSavingsAmount').textContent = '- ' + window.formatRupiah(data.savings);
                    if (document.getElementById('selected_room_price')) {
                        document.getElementById('selected_room_price').textContent = window.formatRupiah(data.monthly_rate) + ' / month (discounted)';
                    }
                } else {
                    clearKostTierDisplay();
                }
                calculatePrice(data.monthly_rate);
            }

            function clearKostTierDisplay() {
                document.getElementById('kostOriginalRow').style.display = 'none';
                document.getElementById('kostSavingsRow').style.display = 'none';
                window.kostTierData = null;
            }

            const roomModal = new bootstrap.Modal(document.getElementById('roomModal'));
            document.getElementById('btnBrowseRooms').onclick = () => {
                const guestId = $('#guest_id').val();
                if (!guestId) {
                    Toastify({
                        text: "Silakan pilih tamu (Guest) terlebih dahulu sebelum memilih kamar.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#f06548" }
                    }).showToast();
                    return;
                }
                if (!checkIn.value || !checkOut.value) {
                    Toastify({
                        text: "Pilih tanggal check-in & check-out terlebih dahulu.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        style: { background: "#f06548" }
                    }).showToast();
                    return;
                }
                const fmtDate = (v) => { const [y, m, d] = v.split('-'); return `${d}/${m}/${y}`; };
                document.getElementById('roomModalTitle').textContent =
                    `Kamar Tersedia: ${fmtDate(checkIn.value)} ${checkInTime.value} → ${fmtDate(checkOut.value)} 12:00`;
                roomModal.show();
                loadAvailableRooms();
            };

            function loadAvailableRooms() {
                const stayType = document.querySelector('input[name="stay_type"]:checked').value;
                const showAll = document.getElementById('show_all_rooms')?.checked || false;
                const params = new URLSearchParams({
                    check_in: checkIn.value,
                    check_out: checkOut.value,
                    check_in_time: document.getElementById('check_in_time')?.value || '',
                    check_out_time: document.querySelector('input[name="check_out_time"]')?.value || '',
                    room_type_id: $('#filter_room_type').val(),
                    guest_id: $('#guest_id').val(),
                    stay_type: stayType,
                    include_unavailable: showAll ? '1' : ''
                });
                fetch(`{{ route('bookings.available-rooms') }}?${params.toString()}`).then(r => r.json()).then(rooms => {
                    const container = document.getElementById('room_list_container');
                    container.innerHTML = '';
                    rooms.forEach(room => {
                        const isAvailable = room.available !== false;
                        let priceSection = '';
                        let hasBreakfast = false;
                        
                        const extraPersonPrice = room.price_extra_person || 0;
                        const maxOccupancy = room.max_occupancy || 2;

                        if (!isAvailable) {
                            const bk = room.current_booking;
                            priceSection = `<div class="mt-2">
                                <span class="badge bg-danger-subtle text-danger mb-1">${room.status || 'Occupied'}</span>
                                ${bk ? `<br><small class="text-muted">#${bk.id} - ${bk.guest_name || '-'}</small>` : ''}
                            </div>`;
                        } else if (stayType === 'yearly') {
                            const yp = (room.yearly_price || room.price_kos || 0);
                            priceSection = `
                                <button class="btn btn-sm btn-soft-success w-100 mt-2" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${yp}, 0, 'public', ${extraPersonPrice}, ${maxOccupancy})">
                                    Pilih (Tahunan): ${window.formatRupiah(yp)} / thn
                                </button>`;
                        } else if (stayType === 'monthly') {
                            priceSection = `
                                <button class="btn btn-sm btn-soft-success w-100 mt-2" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_kos}, 0, 'public', ${extraPersonPrice}, ${maxOccupancy})">
                                    Pilih (Kos): ${window.formatRupiah(room.price_kos)}
                                </button>`;
                        } else {
                            const bfPublic = room.price_breakfast_public || 0;
                            const bfSales = room.price_breakfast_sales || 0;
                            const bfHighSeason = room.price_breakfast_high_season || 0;
                            hasBreakfast = bfPublic > 0 || bfSales > 0 || bfHighSeason > 0;
                            const dynamicPrice = room.price_default || room.price_public;
                            const dynamicTier = room.price_default_tier || 'public';
                            const priceMismatch = Math.abs(dynamicPrice - room.price_public) > 0.01;
                            priceSection = `
                                <div class="d-grid gap-1 mt-2">
                                    <button class="btn btn-sm btn-soft-primary" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${dynamicPrice}, ${bfPublic}, '${dynamicTier}', ${extraPersonPrice}, ${maxOccupancy})">
                                        Umum: ${window.formatRupiah(dynamicPrice)}${priceMismatch ? '<br><small class=\\"text-muted\\">(base: ' + window.formatRupiah(room.price_public) + ')</small>' : ''}${bfPublic > 0 ? '<br><small class=\\"text-muted\\">+ Breakfast: ' + window.formatRupiah(bfPublic) + '</small>' : ''}
                                    </button>
                                    <button class="btn btn-sm btn-soft-info" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_sales}, ${bfSales}, 'sales', ${extraPersonPrice}, ${maxOccupancy})">
                                        Sales: ${window.formatRupiah(room.price_sales)}${bfSales > 0 ? '<br><small class=\\"text-muted\\">+ Breakfast: ' + window.formatRupiah(bfSales) + '</small>' : ''}
                                    </button>
                                    <button class="btn btn-sm btn-soft-danger" onclick="selectRoom(${room.id}, '${room.room_number}', '${room.room_type}', ${room.price_high_season}, ${bfHighSeason}, 'high_season', ${extraPersonPrice}, ${maxOccupancy})">
                                        High Season: ${window.formatRupiah(room.price_high_season)}${bfHighSeason > 0 ? '<br><small class=\\"text-muted\\">+ Breakfast: ' + window.formatRupiah(bfHighSeason) + '</small>' : ''}
                                    </button>
                                </div>`;
                        }

                        const col = document.createElement('div'); col.className = 'col-md-3';
                        col.innerHTML = `
                            <div class="card border shadow-none h-100 ${isAvailable ? '' : 'bg-light opacity-50'}">
                                <div class="card-body p-3">
                                    <h5 class="fs-14 mb-1">Room ${room.room_number}</h5>
                                    <span class="badge bg-primary-subtle text-primary mb-2">${room.room_type}</span>
                                    ${isAvailable ? '' : `<span class="badge bg-danger-subtle text-danger mb-2 ms-1">${room.status || 'Occupied'}</span>`}
                                    ${isAvailable && hasBreakfast ? '<span class="badge bg-warning-subtle text-warning mb-2 ms-1">Breakfast</span>' : ''}
                                    ${priceSection}
                                </div>
                            </div>`;
                        container.appendChild(col);
                    });
                });
            }

            window.filterRoomCards = function() {
                const search = document.getElementById('filter_room_search').value.toLowerCase();
                const cards = document.querySelectorAll('#room_list_container > div');
                cards.forEach(card => {
                    const roomNum = card.querySelector('h5')?.textContent?.toLowerCase() || '';
                    card.style.display = roomNum.includes(search) ? '' : 'none';
                });
            };

            window.selectRoom = (id, num, type, price, breakfastPrice = 0, tier = 'public', extraPersonPrice = 0, maxOccupancy = 2) => {
                document.getElementById('room_id').value = id;
                document.getElementById('manual_price').value = price;
                document.getElementById('tier_applied').value = tier;
                window.roomBreakfastPrice = breakfastPrice;
                window.currentTier = tier;
                window.roomExtraPersonPrice = extraPersonPrice;
                window.roomMaxOccupancy = maxOccupancy;
                window.selectedRoomId = id;
                window.selectedRoomBasePrice = price;
                document.getElementById('room_display').value = `Room ${num} (${type})`;
                document.getElementById('selected_room_info_box').style.display = 'block';
                document.getElementById('selected_room_type').textContent = type;
                document.getElementById('selected_room_price').textContent = window.formatRupiah(price) + ' / night' + (breakfastPrice > 0 ? ` (+ Breakfast: ${window.formatRupiah(breakfastPrice)})` : '');
                roomModal.hide();
                
                // If monthly, fetch kost tier pricing
                const stayType = document.querySelector('input[name="stay_type"]:checked').value;
                if (stayType === 'monthly' || stayType === 'yearly') {
                    fetchKostTierPrice();
                } else {
                    clearKostTierDisplay();
                    calculatePrice(price);
                }
            };

            document.getElementById('applyVoucher').onclick = () => {
                const params = new URLSearchParams({ code: $('#voucher_code').val(), subtotal: window.currentRoomPrice * stayDuration.value, room_id: $('#room_id').val() });
                fetch(`{{ route('vouchers.check') }}?${params.toString()}`).then(r => r.json()).then(d => {
                    window.appliedDiscountAmount = d.success ? d.discount_amount : 0;
                    document.getElementById('voucherResponse').innerHTML = `<span class="text-${d.success?'success':'danger'} fs-12">${d.message}</span>`;
                    calculatePrice();
                });
            };

            document.getElementById('btnSaveGuest').onclick = function() {
                const form = document.getElementById('addGuestForm');
                const btn = this;
                const errorDiv = document.getElementById('guestValidationErrors');
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
                errorDiv.classList.add('d-none');
                errorDiv.innerHTML = '';

                let formData = new FormData(form);
                
                fetch('{{ route("guests.store") }}', { 
                    method: 'POST', 
                    body: formData, 
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest', 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    } 
                })
                .then(r => r.json().then(data => ({ status: r.status, body: data })))
                .then(res => {
                    if(res.status === 200 || res.status === 201) {
                        const d = res.body;
                        const guest = d.data || d.guest || d;
                        let opt = new Option(guest.name + ' (' + (guest.phone || 'No Phone') + ')', guest.id, true, true);
                        $('#guest_id').append(opt).trigger('change');
                        bootstrap.Modal.getInstance(document.getElementById('addGuestModal')).hide();
                        form.reset();
                        Toastify({ 
                            text: "Guest added successfully!", 
                            duration: 3000, 
                            gravity: "top", 
                            position: "right", 
                            style: { background: "#0ab39c" } 
                        }).showToast();
                    } else {
                        // Handle validation errors
                        errorDiv.classList.remove('d-none');
                        let errorMessage = "Validation failed. Please check the form.";
                        
                        if (res.body.errors) {
                            let list = '<ul class="mb-0">';
                            Object.entries(res.body.errors).forEach(([field, errs]) => {
                                errs.forEach(e => { 
                                    list += `<li><strong>${field.replace('_', ' ')}:</strong> ${e}</li>`; 
                                });
                            });
                            list += '</ul>';
                            errorDiv.innerHTML = list;
                            errorMessage = res.body.message || "Data duplikat atau input tidak valid.";
                        } else {
                            errorMessage = res.body.message || 'An error occurred while saving the guest.';
                            errorDiv.innerHTML = errorMessage;
                        }

                        // Also show a toast for error
                        Toastify({ 
                            text: "Error: " + errorMessage, 
                            duration: 5000, 
                            gravity: "top", 
                            position: "right", 
                            style: { background: "#f06548" } 
                        }).showToast();
                    }
                })
                .catch(err => {
                    errorDiv.classList.remove('d-none');
                    errorDiv.innerHTML = 'System error: ' + err.message;
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Save Guest';
                });
            };
        });
    </script>
    
    {{-- Breakfast Calculation Override --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const guestCategories = @json($guestCategories->keyBy('id'));
            const originalCalculatePrice = window.calculatePrice;
            
            window.calculatePrice = function(manualPrice = null) {
                let price = parseFloat(manualPrice || window.currentRoomPrice || 0);
                window.currentRoomPrice = price;
                let nights = parseInt(document.getElementById('stay_duration').value) || 1;
                const stayType = document.querySelector('input[name="stay_type"]:checked').value;
                let kostMonths = 1;
                if (stayType === 'monthly' || stayType === 'yearly') {
                    const selector = document.getElementById('kost_months');
                    kostMonths = selector.value === 'custom' 
                        ? (parseInt(document.getElementById('kost_custom_months').value) || 1) 
                        : (parseInt(selector.value) || 1);
                }
                let subtotal = (stayType === 'monthly' || stayType === 'yearly') ? price * kostMonths : price * nights;
                let deposit = parseFloat(document.getElementById('deposit_amount').value) || 0;
                let discount = parseFloat(window.appliedDiscountAmount) || 0;
                
                const bfCheckbox = document.getElementById('include_breakfast');
                let breakfastTotal = 0;
                let breakfastPerPerson = 0;
                if(bfCheckbox && bfCheckbox.checked) {
                    // Harga breakfast sudah per kamar (include 2 orang)
                    breakfastPerPerson = window.roomBreakfastPrice;
                    breakfastTotal = breakfastPerPerson * nights;
                }

                // Extra person charge
                const adults = parseInt(document.getElementById('adults').value) || 1;
                const extraPersonPrice = window.roomExtraPersonPrice || 0;
                const extraPersons = Math.max(0, adults - 1);
                const extraPersonCharge = extraPersons * extraPersonPrice * nights;

                let subAfterDisc = Math.max(0, subtotal - discount);
                let grandTotal = subAfterDisc + deposit + breakfastTotal + extraPersonCharge;
                
                document.getElementById('roomRate').textContent = window.formatRupiah(price);
                document.getElementById('numNights').textContent = nights;
                document.getElementById('subtotal').textContent = window.formatRupiah(subtotal);
                
                const discRow = document.getElementById('discountRow');
                if(discRow) discRow.style.display = discount > 0 ? 'flex' : 'none';
                const discAmt = document.getElementById('discountAmount');
                if(discAmt) discAmt.textContent = '- ' + window.formatRupiah(discount);
                
                const depRow = document.getElementById('depositRowSummary');
                if(depRow) depRow.style.display = deposit > 0 ? 'flex' : 'none';
                const depAmt = document.getElementById('depositAmountSummary');
                if(depAmt) depAmt.textContent = window.formatRupiah(deposit);

                const extraRow = document.getElementById('extraPersonRow');
                if(extraRow) extraRow.style.display = extraPersonCharge > 0 ? 'flex' : 'none';
                const extraAmt = document.getElementById('extraPersonAmount');
                if(extraAmt) extraAmt.textContent = window.formatRupiah(extraPersonCharge);

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

                // Initial Payment Logic
                const downPaymentInput = document.getElementById('down_payment');
                let downPayment = parseFloat(downPaymentInput.value) || 0;
                
                const cbFullPayment = document.getElementById('cb_full_payment');
                if(cbFullPayment && cbFullPayment.checked) {
                    downPayment = grandTotal;
                    downPaymentInput.value = downPayment;
                }

                const remaining = Math.max(0, grandTotal - downPayment);
                document.getElementById('remaining_balance').textContent = window.formatRupiah(remaining);
                
                const payDiv = document.getElementById('payment_account_div');
                if(payDiv) payDiv.style.display = downPayment > 0 ? 'block' : 'none';
                const bankAcc = document.getElementById('bank_account_id');
                if(bankAcc) bankAcc.required = downPayment > 0;
            };

            // Trigger on pax changes
            document.querySelector('select[name="adults"]').addEventListener('change', () => window.calculatePrice());
            document.querySelector('select[name="children"]').addEventListener('change', () => window.calculatePrice());
        });
    </script>
@endsection
