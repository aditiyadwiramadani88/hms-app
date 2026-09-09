@extends('public.layouts.app')

@php
    $roomType = $room->roomType;
    $price = $room->price_public ?? $roomType->base_price ?? 0;
@endphp

@section('title', 'Room ' . $room->room_number . ' - ' . ($roomType->name ?? 'Detail'))
@section('page_title', 'Room ' . $room->room_number)

@section('content')
<div style="padding-top: 120px; padding-bottom: 80px; background: #f5f5f5;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div style="background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px;">
                    @if($roomType->image ?? false)
                    <div style="margin-bottom: 25px; border-radius: 8px; overflow: hidden; max-height: 350px;">
                        <img src="{{ Storage::url($roomType->image) }}" alt="{{ $roomType->name }}" style="width: 100%; height: auto; display: block;">
                    </div>
                    @endif
                    <div style="background: #8b7355; border-radius: 8px; padding: 40px 30px; margin-bottom: 25px;">
                        <h1 style="font-family: 'Playfair Display', serif; color: #fff; font-size: 32px; margin-bottom: 10px;">Room {{ $room->room_number }} — {{ $roomType->name ?? '' }}</h1>
                        <p style="color: rgba(255,255,255,0.8); font-size: 18px; margin: 0;">Harga <strong>Rp {{ number_format($price, 0, ',', '.') }}</strong> / malam</p>
                    </div>

                    <div class="row" style="margin-bottom: 25px;">
                        <div class="col-md-3 col-6" style="margin-bottom: 15px;">
                            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 24px; margin-bottom: 5px;">🛏️</div>
                                <p style="color: #333; font-size: 13px; margin: 0;">{{ $roomType->bed_type ?? '2 Bed' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6" style="margin-bottom: 15px;">
                            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 24px; margin-bottom: 5px;">📐</div>
                                <p style="color: #333; font-size: 13px; margin: 0;">{{ $roomType->size_sqm ?? 'N/A' }} m²</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6" style="margin-bottom: 15px;">
                            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 24px; margin-bottom: 5px;">👥</div>
                                <p style="color: #333; font-size: 13px; margin: 0;">{{ $roomType->max_guests ?? 2 }} Tamu</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6" style="margin-bottom: 15px;">
                            <div style="text-align: center; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                                <div style="font-size: 24px; margin-bottom: 5px;">🚿</div>
                                <p style="color: #333; font-size: 13px; margin: 0;">Kamar Mandi</p>
                            </div>
                        </div>
                    </div>

                    <h4 style="color: #222; margin-bottom: 10px;">Deskripsi</h4>
                    <p style="color: #666; line-height: 1.8; margin-bottom: 25px;">{{ $roomType->description ?? 'Experience luxury and comfort in our beautifully designed room. Equipped with modern amenities for a perfect stay.' }}</p>

                    <h4 style="color: #222; margin-bottom: 15px;">Fasilitas</h4>
                    <div class="row" style="margin-bottom: 25px;">
                        <div class="col-md-6">
                            <p style="color: #555; margin-bottom: 8px;">✓ AC</p>
                            <p style="color: #555; margin-bottom: 8px;">✓ TV</p>
                            <p style="color: #555; margin-bottom: 8px;">✓ Free WiFi</p>
                        </div>
                        <div class="col-md-6">
                            <p style="color: #555; margin-bottom: 8px;">✓ Kamar Mandi Dalam</p>
                            <p style="color: #555; margin-bottom: 8px;">✓ Handuk & Toiletries</p>
                            <p style="color: #555; margin-bottom: 8px;">✓ Parking</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
                                <h5 style="color: #222; margin-bottom: 10px;">🕐 Check-in</h5>
                                <p style="color: #666; font-size: 14px; margin-bottom: 5px;">✓ Check-in mulai 14:00</p>
                                <p style="color: #666; font-size: 14px; margin: 0;">✓ Early check-in by request</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
                                <h5 style="color: #222; margin-bottom: 10px;">🕛 Check-out</h5>
                                <p style="color: #666; font-size: 14px; margin-bottom: 5px;">✓ Check-out sebelum 12:00</p>
                                <p style="color: #666; font-size: 14px; margin: 0;">✓ Late check-out by request</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Booking Sidebar --}}
            <div class="col-lg-4">
                <div style="background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); position: sticky; top: 100px;">
                    <h4 style="color: #222; text-align: center; margin-bottom: 5px; font-family: 'Playfair Display', serif;">Book This Room</h4>
                    <p style="text-align: center; color: #8b7355; font-size: 20px; font-weight: 700; margin-bottom: 20px;">Rp {{ number_format($price, 0, ',', '.') }} <small style="font-size: 13px; color: #999; font-weight: 400;">/ malam</small></p>

                    <form action="{{ route('public.booking.form') }}" method="GET" id="bookForm">
                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                        <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
                        <input type="hidden" name="breakfast" id="bf_hidden" value="0">

                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 12px; font-weight: 600; color: #333; display: block; margin-bottom: 5px;">Check-in</label>
                            <input type="date" name="check_in" id="room_checkin" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 12px; font-weight: 600; color: #333; display: block; margin-bottom: 5px;">Jumlah Malam</label>
                            <select name="nights" id="room_nights" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333;">
                                <option value="1">1 Malam</option>
                                <option value="2">2 Malam</option>
                                <option value="3">3 Malam</option>
                                <option value="5">5 Malam</option>
                                <option value="7">7 Malam</option>
                                <option value="14">14 Malam</option>
                                <option value="30">30 Malam</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 12px; font-weight: 600; color: #333; display: block; margin-bottom: 5px;">Tamu</label>
                            <select name="adults" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; color: #333;">
                                <option value="1">1 Adult</option>
                                <option value="2" selected>2 Adults</option>
                                <option value="3">3 Adults</option>
                                <option value="4">4 Adults</option>
                            </select>
                        </div>

                        @php
                            $bfPublic = $room->price_breakfast_public ?? 0;
                            $bfSales = $room->price_breakfast_sales ?? 0;
                            $bfHigh = $room->price_breakfast_high_season ?? 0;
                            $hasBreakfast = $bfPublic > 0 || $bfSales > 0 || $bfHigh > 0;
                        @endphp
                        @if($hasBreakfast)
                        <div style="margin-bottom: 15px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: #333;">
                                <input type="checkbox" id="include_breakfast" style="width: 16px; height: 16px;">
                                <span>Include Breakfast? (+Rp {{ number_format($bfPublic, 0, ',', '.') }}/malam)</span>
                            </label>
                        </div>
                        @endif

                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 12px; font-weight: 600; color: #333; display: block; margin-bottom: 5px;">Kode Voucher (opsional)</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="voucher_code" placeholder="ENTER CODE" style="flex: 1; padding: 10px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; text-transform: uppercase;">
                                <button type="button" onclick="checkVoucher()" style="padding: 10px 15px; background: #6c757d; color: #fff; border: none; border-radius: 5px; font-size: 12px; cursor: pointer;">Apply</button>
                            </div>
                            <div id="voucherMsg" style="font-size: 11px; margin-top: 4px;"></div>
                        </div>

                        <div style="background: #f9f9f9; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                            <div class="d-flex justify-content-between" style="font-size: 13px; color: #666; margin-bottom: 4px;">
                                <span>Room</span><span id="room_subtotal">Rp {{ number_format($price, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between" id="bf_row" style="display:none; font-size: 13px; color: #666; margin-bottom: 4px;">
                                <span>Breakfast</span><span id="bf_amount">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between" id="disc_row" style="display:none; font-size: 13px; color: #28a745; margin-bottom: 4px;">
                                <span>Discount</span><span id="disc_amount">Rp 0</span>
                            </div>
                            <hr style="margin: 8px 0;">
                            <div class="d-flex justify-content-between">
                                <span style="color: #666; font-size: 13px;">Estimasi Total</span>
                                <strong style="color: #222; font-size: 18px;" id="room_total">Rp {{ number_format($price, 0, ',', '.') }}</strong>
                            </div>
                        </div>

                        <input type="hidden" name="total_price" id="total_price_hidden" value="{{ $price }}">

                        <button type="submit" data-submit-protect="true" style="width: 100%; padding: 14px; background: #8b7355; color: #fff; border: none; border-radius: 5px; font-size: 15px; font-weight: 600; cursor: pointer;">BOOK NOW →</button>
                    </form>

                    <p style="text-align: center; margin-top: 15px; color: #999; font-size: 12px;">Kamar tersedia saat ini</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Related Rooms --}}
@if($relatedRooms->count() > 0)
<div style="padding: 60px 0; background: #fff;">
    <div class="container">
        <h3 style="text-align: center; font-family: 'Playfair Display', serif; color: #222; margin-bottom: 30px;">Kamar Serupa</h3>
        <div class="row">
            @foreach($relatedRooms as $r)
            <div class="col-lg-3 col-md-6" style="margin-bottom: 20px;">
                <div style="background: #f9f9f9; border-radius: 10px; padding: 25px; text-align: center;">
                    <h5 style="color: #222; margin-bottom: 10px;">Room {{ $r->room_number }}</h5>
                    <p style="color: #8b7355; font-weight: 600;">Rp {{ number_format($r->price_public ?? $r->roomType->base_price ?? 0, 0, ',', '.') }} / malam</p>
                    <a href="{{ route('public.rooms.show', $r->id) }}" style="color: #8b7355; font-size: 13px;">View Details →</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePrice = {{ (int) $price }};
    const bfPrice = {{ (int) $bfPublic }};
    const bfCheck = document.getElementById('include_breakfast');
    const bfRow = document.getElementById('bf_row');
    const bfAmt = document.getElementById('bf_amount');
    const discRow = document.getElementById('disc_row');
    const discAmt = document.getElementById('disc_amount');
    const nightsSelect = document.getElementById('room_nights');
    const totalEl = document.getElementById('room_total');
    const roomSubEl = document.getElementById('room_subtotal');
    const bfHidden = document.getElementById('bf_hidden');
    const totalHidden = document.getElementById('total_price_hidden');

    let discountAmount = 0;

    function updateTotal() {
        const nights = parseInt(nightsSelect.value);
        const roomTotal = basePrice * nights;
        const bfTotal = bfCheck && bfCheck.checked ? bfPrice * nights : 0;
        const disc = discountAmount;
        const grand = Math.max(0, roomTotal + bfTotal - disc);

        roomSubEl.textContent = 'Rp ' + roomTotal.toLocaleString('id-ID');
        bfHidden.value = bfCheck && bfCheck.checked ? '1' : '0';

        if (bfCheck && bfPrice > 0) {
            bfRow.style.display = 'flex';
            bfAmt.textContent = '+Rp ' + bfTotal.toLocaleString('id-ID');
        } else {
            bfRow.style.display = 'none';
        }

        if (disc > 0) {
            discRow.style.display = 'flex';
            discAmt.textContent = '-Rp ' + disc.toLocaleString('id-ID');
        } else {
            discRow.style.display = 'none';
        }

        totalEl.textContent = 'Rp ' + grand.toLocaleString('id-ID');
        totalHidden.value = grand;
    }

    nightsSelect.addEventListener('change', updateTotal);
    if (bfCheck) bfCheck.addEventListener('change', updateTotal);
    updateTotal();

    window.checkVoucher = function() {
        const code = document.getElementById('voucher_code').value.trim();
        const msg = document.getElementById('voucherMsg');
        if (!code) { msg.innerHTML = ''; discountAmount = 0; updateTotal(); return; }
        const nights = parseInt(nightsSelect.value);
        const subtotal = basePrice * nights;
        const params = new URLSearchParams({ code: code, subtotal: subtotal, room_id: '{{ $room->id }}' });
        msg.innerHTML = '<span class="text-muted">Validating...</span>';
        fetch('{{ route("public.vouchers.check") }}?' + params.toString())
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    discountAmount = d.discount_amount || 0;
                    msg.innerHTML = '<span class="text-success">✓ ' + d.message + '</span>';
                } else {
                    discountAmount = 0;
                    msg.innerHTML = '<span class="text-danger">' + d.message + '</span>';
                }
                updateTotal();
            })
            .catch(() => { discountAmount = 0; msg.innerHTML = '<span class="text-danger">Gagal validasi</span>'; updateTotal(); });
    };
});
</script>
@endsection
