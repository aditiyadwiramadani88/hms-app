<script type="application/json" id="ajax-badge-counts">{!! json_encode($counts) !!}</script>
<input type="hidden" id="ajax-sort-state" data-sort="{{ request('sort_by', 'check_in') }}" data-dir="{{ request('sort_dir', 'desc') }}">

<div class="table-responsive table-card">
    <table class="table table-nowrap align-middle table-borderless mb-0" id="bookingTable">
        <thead class="table-light text-muted">
            <tr>
                <th scope="col" style="width: 50px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                    </div>
                </th>
                @php
                    $currentSort = request('sort_by', 'check_in');
                    $currentDir = request('sort_dir', 'desc');
                    $baseParams = request()->except(['sort_by', 'sort_dir', 'page']);
                @endphp
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('id')">
                    Booking ID
                    @if($currentSort === 'id') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('guest_name')">
                    Guest Name
                    @if($currentSort === 'guest_name') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('room_number')">
                    Room
                    @if($currentSort === 'room_number') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('created_at')">
                    Tgl Booking
                    @if($currentSort === 'created_at') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('check_in')">
                    Check-in
                    @if($currentSort === 'check_in') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('check_out')">
                    Check-out
                    @if($currentSort === 'check_out') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('status')">
                    Status
                    @if($currentSort === 'status') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                @if(!auth()->user()->hasRole('Front Page Only'))
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('payment_status')">
                    Payment
                    @if($currentSort === 'payment_status') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('total_price')">
                    Total
                    @if($currentSort === 'total_price') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                @endif
                <th class="text-uppercase" style="cursor:pointer;" onclick="sortBy('source')">
                    Sumber
                    @if($currentSort === 'source') <i class="ri-arrow-{{ $currentDir === 'asc' ? 'up' : 'down' }}-s-fill"></i> @endif
                </th>
                <th class="text-uppercase">Dibuat Oleh</th>
                <th class="text-uppercase">Check In Oleh</th>
                <th class="text-uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="list form-check-all">
            @forelse($bookings ?? [] as $booking)
            <tr>
                <th scope="row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="checkAll" value="{{ $booking->id }}">
                    </div>
                </th>
                <td class="fw-medium">
                    <a href="{{ route('bookings.show', $booking->id) }}" class="text-primary">
                        #{{ $booking->booking_number ?? $booking->id }}
                    </a>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-2">
                            <div class="avatar-xs">
                                <span class="avatar-title rounded-circle bg-primary-subtle text-primary">
                                    {{ substr($booking->guest->name ?? 'G', 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <a href="{{ route('guests.show', $booking->guest_id ?? '#') }}" class="text-body">
                                {{ $booking->guest->name ?? 'N/A' }}
                            </a>
                        </div>
                    </div>
                </td>
                <td>
                    @if($booking->is_custom)
                        <span class="text-info fw-medium"><i class="ri-edit-box-line me-1"></i>{{ $booking->custom_room_name }}</span>
                    @else
                        {{ $booking->room->room_number ?? 'N/A' }}
                    @endif
                    @if($booking->stay_type === 'monthly')
                        <span class="badge bg-primary-subtle text-primary ms-1">Kos</span>
                    @elseif($booking->stay_type === 'yearly')
                        <span class="badge bg-info-subtle text-info ms-1">Tahunan</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary ms-1">Harian</span>
                    @endif
                </td>                                        
                <td>
                    <span class="text-muted">{{ $booking->created_at->format('d/m/Y') }}</span>
                    <br>
                    <small class="text-muted">{{ $booking->created_at->format('H:i') }}</small>
                </td>
                <td>{{ $booking->check_in ? $booking->check_in->format('d M Y') : 'N/A' }}</td>
                <td>
                    {{ $booking->check_out ? $booking->check_out->format('d M Y') : 'N/A' }}
                    @if($booking->status === 'checked_in' && $booking->check_out && $booking->check_out->isPast() && !$booking->check_out->isToday())
                        <br><span class="badge bg-danger text-white fs-10" title="Sudah lewat batas checkout, belum di-checkout">Overstay</span>
                    @endif
                </td>
                <td>
                    @if($booking->status === 'confirmed')
                        <span class="badge bg-success-subtle text-success">Confirmed</span>
                    @elseif($booking->status === 'pending')
                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                    @elseif($booking->status === 'checked_in')
                        <span class="badge bg-info-subtle text-info">Checked In</span>
                    @elseif($booking->status === 'checked_out')
                        <span class="badge bg-secondary-subtle text-secondary">Checked Out</span>
                    @elseif($booking->status === 'cancelled')
                        <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                    @elseif($booking->status === 'no_show')
                        <span class="badge bg-dark-subtle text-dark">No Show</span>
                    @else
                        <span class="badge bg-light text-muted">{{ ucfirst($booking->status ?? 'Unknown') }}</span>
                    @endif
                </td>
                @if(!auth()->user()->hasRole('Front Page Only'))
                <td>
                    <span class="badge bg-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'partial' ? 'warning' : 'danger') }}-subtle text-{{ $booking->payment_status === 'paid' ? 'success' : ($booking->payment_status === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($booking->payment_status ?? 'N/A') }}</span>
                </td>
                @php
                    $grandTotal = \App\Services\BookingPriceService::grandTotal($booking);
                    $totalPayments = $booking->total_payments ?? 0;
                    $remainingBalance = max(0, $grandTotal - $totalPayments);
                @endphp
                <td class="fw-medium">Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    @if($remainingBalance > 0)
                    <br><span class="badge bg-danger-subtle text-danger fs-10" title="Sisa Belum Dibayar">Sisa: Rp {{ number_format($remainingBalance, 0, ',', '.') }}</span>
                    @endif
                </td>
                @endif
                <td>
                    @php
                        $src = null;
                        if ($booking->bookingSource) {
                            $src = ['label' => $booking->bookingSource->name, 'color' => $booking->bookingSource->color];
                        } else {
                            $sourceLabels = [
                                'walk_in' => ['label' => 'Walk-in', 'color' => 'secondary'],
                                'whatsapp' => ['label' => 'WhatsApp', 'color' => 'success'],
                                'traveloka' => ['label' => 'Traveloka', 'color' => 'primary'],
                                'agoda' => ['label' => 'Agoda', 'color' => 'danger'],
                                'booking_com' => ['label' => 'Booking.com', 'color' => 'info'],
                                'tiket_com' => ['label' => 'Tiket.com', 'color' => 'warning'],
                                'airbnb' => ['label' => 'Airbnb', 'color' => 'danger'],
                                'instagram' => ['label' => 'Instagram', 'color' => 'warning'],
                                'other' => ['label' => 'Lainnya', 'color' => 'secondary'],
                            ];
                            $src = $sourceLabels[$booking->source] ?? ['label' => ucfirst($booking->source ?? 'Walk-in'), 'color' => 'secondary'];
                        }
                        $textColor = in_array($src['color'], ['light', 'white']) ? 'dark' : $src['color'];
                    @endphp
                    <span class="badge bg-secondary-subtle text-secondary">{{ $src['label'] }}</span>
                </td>
                <td>
                    <small class="text-muted">{{ $booking->user?->name ?? '-' }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $booking->status === 'checked_in' || $booking->status === 'checked_out' ? $booking->checked_in_by : '-' }}</small>
                </td>
                <td>
                    <ul class="list-inline hstack gap-2 mb-0">
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                            <a href="{{ route('bookings.show', $booking->id) }}" class="text-primary d-inline-block">
                                <i class="ri-eye-fill fs-16"></i>
                            </a>
                        </li>
                        @can('bookings.checkin')
                        @if(in_array($booking->status, ['confirmed', 'pending']) && $booking->check_in && $booking->check_in->isSameDay(now()))
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Check In">
                            <form action="{{ route('bookings.check-in', $booking->id) }}" method="POST" data-ajax="true" data-ajax-reload="true" class="d-inline-block" data-ajax-confirm="Proses Check In untuk booking ini?">
                                @csrf
                                <button type="submit" data-submit-protect="true" class="btn btn-link p-0 m-0 text-success border-0 bg-transparent">
                                    <i class="ri-login-circle-line fs-16"></i>
                                </button>
                            </form>
                        </li>
                        @endif
                        @endcan
                        @can('bookings.edit.unlimited')
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                            <a href="{{ route('bookings.edit', $booking->id) }}" class="text-info d-inline-block">
                                <i class="ri-edit-fill fs-16"></i>
                            </a>
                        </li>
                        @elsecan('bookings.edit')
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit ({{ $booking->created_at->addHours(24)->diffForHumans() }})">
                            @php $_editRemaining = now()->diffInSeconds($booking->created_at->addHours(24), false); @endphp
                            @if($_editRemaining > 0)
                            <a href="{{ route('bookings.edit', $booking->id) }}" class="text-info d-inline-block">
                                <i class="ri-edit-fill fs-16"></i>
                            </a>
                            @else
                            <span class="text-muted d-inline-block" title="Waktu edit sudah habis">
                                <i class="ri-edit-fill fs-16"></i>
                            </span>
                            @endif
                        </li>
                        @endcan
                        @if(!in_array($booking->status, ['checked_in', 'checked_out']))
                            @can('bookings.delete.unlimited')
                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $booking->id }}, '{{ $booking->booking_number ?? $booking->id }}')">
                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                </a>
                            </li>
                            @elsecan('bookings.delete')
                            @php $_deleteRemaining = now()->diffInSeconds($booking->created_at->addHours(24), false); @endphp
                            @if($_deleteRemaining > 0)
                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $booking->id }}, '{{ $booking->booking_number ?? $booking->id }}')">
                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                </a>
                            </li>
                            @endif
                            @endcan
                        @endif
                    </ul>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    <i class="ri-calendar-close-line fs-1 d-block mb-2"></i>
                    No bookings found. <a href="{{ route('bookings.create') }}" class="text-primary">Create a new booking</a> to get started.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3 p-3">
    <p class="text-muted mb-0">Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} entries</p>
    <div class="pagination-hide-info">
        {{ $bookings->links() }}
    </div>
</div>

<style>
    .pagination-hide-info nav .flex.items-center.justify-between div:first-child {
        display: none !important;
    }
    .pagination-hide-info nav p.text-muted {
        display: none !important;
    }
</style>
