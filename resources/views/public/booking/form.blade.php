@extends('public.layouts.app')

@section('title', 'Book a Room')
@section('page_title', 'Book a Room')

@section('content')
<div style="padding-top: 140px; padding-bottom: 80px; min-height: 80vh; background: #f5f5f5;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7" data-aos="fade-up">
                <div style="background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div class="text-center mb-4">
                        <h3 style="font-family: 'Playfair Display', serif; color: #222;">Complete Your Booking</h3>
                        <p style="color: #666; font-size: 14px;">Fill in the details below to reserve your room</p>
                    </div>

                    @if($errors->any())
                        <div style="padding: 10px 15px; border-radius: 5px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-bottom: 20px;">
                            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" data-ajax="true" action="{{ route('public.booking.store') }}" id="bookingForm">
                        @csrf

                        {{-- Room Type --}}
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Room Type *</label>
                            <select name="room_type_id" id="room_type_select" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                                <option value="">Select Room Type</option>
                                @foreach($roomTypes as $rt)
                                    <option value="{{ $rt->id }}" {{ isset($roomType) && $roomType && $roomType->id == $rt->id ? 'selected' : '' }}>
                                        {{ $rt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Available Rooms --}}
                        <div id="availableRooms" style="margin-bottom: 20px; display: none;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 8px; display: block; color: #333;">Pilih Kamar</label>
                            <div id="roomList" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px;"></div>
                            <input type="hidden" name="room_id" id="room_id_input">
                            <div id="roomLoading" style="text-align:center; padding:12px; display:none; color:#999;">Memuat kamar...</div>
                        </div>

                        {{-- Dates --}}
                        <div class="row">
                            <div class="col-md-4" style="margin-bottom: 20px;">
                                <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Check-in Date *</label>
                                <input type="date" name="check_in" id="pub_check_in" value="{{ old('check_in', request('check_in', date('Y-m-d'))) }}" min="{{ date('Y-m-d') }}" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                            </div>
                            <div class="col-md-4" style="margin-bottom: 20px;">
                                <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Jumlah Malam</label>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="pub_minus_nights" style="width: 36px; height: 36px; border: 1px solid #ddd; border-radius: 5px; background: #fff; cursor: pointer; font-size: 18px;">-</button>
                                    <input type="number" id="pub_nights" name="nights" value="{{ request('nights', 1) }}" min="1" max="365" readonly style="width: 50px; text-align: center; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #f9f9f9;">
                                    <button type="button" id="pub_plus_nights" style="width: 36px; height: 36px; border: 1px solid #ddd; border-radius: 5px; background: #fff; cursor: pointer; font-size: 18px;">+</button>
                                </div>
                            </div>
                            <div class="col-md-4" style="margin-bottom: 20px;">
                                <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Check-out Date</label>
                                <input type="date" name="check_out" id="pub_check_out" readonly style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #666; background: #f9f9f9;">
                                <small style="color: #999; font-size: 11px;">Checkout jam 12:00 siang</small>
                            </div>
                        </div>

                        {{-- Guests --}}
                        <div class="row">
                            <div class="col-md-6" style="margin-bottom: 20px;">
                                <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Adults *</label>
                                <input type="number" name="adults" value="{{ old('adults', request('adults', 1)) }}" min="1" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                            </div>
                            <div class="col-md-6" style="margin-bottom: 20px;">
                                <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Children</label>
                                <input type="number" name="children" value="{{ old('children', request('children', 0)) }}" min="0" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                            </div>
                        </div>

                        <hr style="margin: 25px 0; border-color: #eee;">
                        <h4 style="text-align: center; margin-bottom: 20px; color: #222; font-family: 'Playfair Display', serif;">Guest Information</h4>

                        @guest('guest')
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Full Name *</label>
                            <input type="text" name="guest_name" value="{{ old('guest_name') }}" placeholder="Your full name" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Email Address *</label>
                            <input type="email" name="guest_email" value="{{ old('guest_email') }}" placeholder="your@email.com" required style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Phone Number</label>
                            <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="+62 xxx xxxx xxxx" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Password (optional - create account)</label>
                            <input type="password" name="guest_password" placeholder="Min 8 characters" style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff;">
                        </div>
                        @endguest

                        {{-- Breakfast --}}
                        @if($hasBreakfast ?? false)
                        <div id="breakfastSection" style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: #333;">
                                <input type="checkbox" name="breakfast" id="include_breakfast" value="1" style="width: 16px; height: 16px;">
                                <span>Include Breakfast? (<span id="bf_price_label">+Rp 0</span>/malam)</span>
                            </label>
                        </div>
                        @else
                        <div id="breakfastSection" style="margin-bottom: 15px; display: none;"></div>
                        @endif

                        {{-- Voucher --}}
                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Kode Voucher (opsional)</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" name="voucher_code" id="voucher_code" value="{{ request('voucher_code') }}" placeholder="ENTER CODE" style="flex: 1; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; text-transform: uppercase;">
                                <button type="button" onclick="checkVoucher()" style="padding: 10px 15px; background: #6c757d; color: #fff; border: none; border-radius: 5px; font-size: 12px; cursor: pointer;">Apply</button>
                            </div>
                            <div id="voucherMsg" style="font-size: 11px; margin-top: 4px;"></div>
                        </div>

                        <div style="margin-bottom: 25px;">
                            <label style="font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; color: #333;">Special Requests (optional)</label>
                            <textarea name="notes" rows="3" placeholder="Any special requests or notes..." style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333; background: #fff; resize: vertical;">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" data-submit-protect="true" style="display: block; width: 100%; padding: 14px; border: none; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer; background: #8b7355; color: #fff;">
                            Proceed to Payment →
                        </button>

                        <div class="text-center" style="margin-top: 15px;">
                            <a href="{{ route('public.rooms.index') }}" style="font-size: 13px; color: #999;">← Back to Rooms</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkIn = document.getElementById('pub_check_in');
    const checkOut = document.getElementById('pub_check_out');
    const nights = document.getElementById('pub_nights');
    const btnMinus = document.getElementById('pub_minus_nights');
    const btnPlus = document.getElementById('pub_plus_nights');
    const typeSelect = document.getElementById('room_type_select');
    const roomsDiv = document.getElementById('availableRooms');
    const roomList = document.getElementById('roomList');
    const roomInput = document.getElementById('room_id_input');
    const roomLoading = document.getElementById('roomLoading');
    const bfSection = document.getElementById('breakfastSection');
    const bfPriceLabel = document.getElementById('bf_price_label');
    const bfCheck = document.getElementById('include_breakfast');

    const breakfastPrices = @json($breakfastPrices);

    function updateBreakfast() {
        const typeId = typeSelect.value;
        const bf = breakfastPrices[typeId];
        if (bf && bf.public > 0) {
            bfSection.style.display = '';
            bfPriceLabel.textContent = '+Rp ' + new Intl.NumberFormat('id-ID').format(bf.public);
        } else {
            bfSection.style.display = 'none';
            if (bfCheck) bfCheck.checked = false;
        }
    }

    function updateCheckout() {
        if (checkIn.value) {
            const d = new Date(checkIn.value);
            d.setDate(d.getDate() + parseInt(nights.value));
            checkOut.value = d.toISOString().split('T')[0];
            loadRooms();
        }
    }

    function loadRooms() {
        const typeId = typeSelect.value;
        const ci = checkIn.value;
        const co = checkOut.value;
        if (!typeId || !ci || !co) { roomsDiv.style.display = 'none'; return; }

        roomsDiv.style.display = 'block';
        roomList.innerHTML = '';
        roomLoading.style.display = 'block';

        fetch('/api/available-rooms?room_type_id=' + typeId + '&check_in=' + ci + '&check_out=' + co)
            .then(r => r.json())
            .then(rooms => {
                roomLoading.style.display = 'none';
                if (rooms.length === 0) {
                    roomList.innerHTML = '<div style="padding:12px;color:#dc3545;text-align:center;">Tidak ada kamar tersedia untuk tanggal ini.</div>';
                    roomInput.value = '';
                    return;
                }
                roomList.innerHTML = rooms.map(r => {
                    const price = r.price_public || 0;
                    const name = r.room_name || ('Kamar ' + r.room_number);
                    return '<label style="display:flex;align-items:center;padding:10px 12px;border-bottom:1px solid #f0f0f0;cursor:pointer;margin:0;" onmouseover="this.style.background=\'#f9f9f9\'" onmouseout="this.style.background=\'\'">' +
                        '<input type="radio" name="_room_select" value="' + r.id + '" onchange="document.getElementById(\'room_id_input\').value=this.value" style="margin-right:10px;">' +
                        '<span style="flex:1;font-size:14px;color:#333;"><strong>' + name + '</strong></span>' +
                        '<span style="font-weight:600;color:#8b7355;">Rp ' + new Intl.NumberFormat('id-ID').format(price) + ' <small style="font-weight:400;font-size:11px;">/ malam</small></span>' +
                        '</label>';
                }).join('');
                const preSelected = roomInput.value;
                if (preSelected && roomList.querySelector('input[value="' + preSelected + '"]')) {
                    roomList.querySelector('input[value="' + preSelected + '"]').checked = true;
                } else if (!roomInput.value) {
                    roomInput.value = rooms[0].id;
                    roomList.querySelector('input[type=radio]').checked = true;
                }
            })
            .catch(() => {
                roomLoading.style.display = 'none';
                roomList.innerHTML = '<div style="padding:12px;color:#dc3545;">Gagal memuat kamar.</div>';
            });
    }

    btnPlus.addEventListener('click', function() {
        nights.value = parseInt(nights.value) + 1;
        updateCheckout();
    });

    btnMinus.addEventListener('click', function() {
        if (parseInt(nights.value) > 1) {
            nights.value = parseInt(nights.value) - 1;
            updateCheckout();
        }
    });

    checkIn.addEventListener('change', updateCheckout);
    typeSelect.addEventListener('change', function() { loadRooms(); updateBreakfast(); });
    updateCheckout();

    @if(request('room_type_id'))
        typeSelect.dispatchEvent(new Event('change'));
    @endif

    @if(request('room_id'))
        roomInput.value = '{{ request('room_id') }}';
    @endif

    window.checkVoucher = function() {
        const code = document.getElementById('voucher_code').value.trim();
        const msg = document.getElementById('voucherMsg');
        if (!code) { msg.innerHTML = ''; return; }
        msg.innerHTML = '<span class="text-muted">Validating...</span>';
        const params = new URLSearchParams({ code: code });
        fetch('{{ route("public.vouchers.check") }}?' + params.toString())
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    msg.innerHTML = '<span class="text-success">✓ ' + d.message + '</span>';
                } else {
                    msg.innerHTML = '<span class="text-danger">' + d.message + '</span>';
                }
            })
            .catch(() => { msg.innerHTML = '<span class="text-danger">Gagal validasi</span>'; });
    };
});
</script>
@endsection
