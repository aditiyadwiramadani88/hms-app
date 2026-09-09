<div class="table-responsive table-card">
    <table class="table table-nowrap align-middle table-borderless mb-0" id="roomTable">
        <thead class="table-light text-muted">
            <tr>
                <th scope="col" style="width: 50px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                    </div>
                </th>
                <th class="sort text-uppercase" data-sort="room_number">Room Number</th>
                <th class="sort text-uppercase" data-sort="room_type">Room Type</th>
                <th class="sort text-uppercase" data-sort="floor">Floor</th>
                <th class="sort text-uppercase" data-sort="status">Status</th>
                <th class="sort text-uppercase">Pricing (Rp)</th>
                <th class="sort text-uppercase">Breakfast (Rp)</th>
                <th class="sort text-uppercase" data-sort="action">Actions</th>
            </tr>
        </thead>
        <tbody class="list form-check-all">
            @forelse($rooms ?? [] as $room)
            <tr>
                <th scope="row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="checkAll" value="{{ $room->id }}">
                    </div>
                </th>
                <td class="fw-medium">
                    <a href="javascript:void(0);" class="text-primary btn-edit-room" data-room-id="{{ $room->id }}">
                        {{ $room->room_number }}
                    </a>
                </td>
                <td>{{ $room->roomType->name ?? $room->room_type }}</td>
                <td>Floor {{ $room->floor ?? 'N/A' }}</td>
                <td>
                    @if(in_array($room->status, ['Available', 'available']))
                        <span class="badge bg-success-subtle text-success">Available</span>
                    @elseif($room->status === 'occupied')
                        <span class="badge bg-danger-subtle text-danger">Occupied</span>
                    @elseif($room->status === 'maintenance')
                        <span class="badge bg-warning-subtle text-warning">Maintenance</span>
                    @elseif($room->status === 'cleaning')
                        <span class="badge bg-info-subtle text-info">Cleaning</span>
                    @elseif($room->status === 'out_of_order')
                        <span class="badge bg-secondary-subtle text-secondary">Out of Order</span>
                    @else
                        <span class="badge bg-light text-muted">{{ ucfirst($room->status ?? 'Unknown') }}</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <small class="text-muted">Umum: <span class="fw-medium text-dark">{{ number_format($room->price_public, 0) }}</span></small>
                        <small class="text-muted">Sales: <span class="fw-medium text-dark">{{ number_format($room->price_sales, 0) }}</span></small>
                        <small class="text-muted">High: <span class="fw-medium text-dark">{{ number_format($room->price_high_season, 0) }}</span></small>
                        @if($room->is_kos)
                            <small class="text-muted">Kos: <span class="fw-medium text-success">{{ number_format($room->price_kos, 0) }}</span></small>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <small class="text-muted">Umum: <span class="fw-medium text-warning">{{ number_format($room->price_breakfast_public ?? 0, 0) }}</span></small>
                        <small class="text-muted">Sales: <span class="fw-medium text-warning">{{ number_format($room->price_breakfast_sales ?? 0, 0) }}</span></small>
                        <small class="text-muted">High: <span class="fw-medium text-warning">{{ number_format($room->price_breakfast_high_season ?? 0, 0) }}</span></small>
                    </div>
                </td>
                <td>
                    <ul class="list-inline hstack gap-2 mb-0">
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="History Booking">
                            <a href="javascript:void(0);" class="text-info d-inline-block btn-history-room" data-room-id="{{ $room->id }}" data-room-number="{{ $room->room_number }}">
                                <i class="ri-history-line fs-16"></i>
                            </a>
                        </li>
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                            <a href="javascript:void(0);" class="text-primary d-inline-block btn-edit-room" data-room-id="{{ $room->id }}">
                                <i class="ri-pencil-fill fs-16"></i>
                            </a>
                        </li>
                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                            <a href="javascript:void(0);" class="text-danger d-inline-block" onclick="confirmDelete({{ $room->id }}, '{{ $room->room_number }}')">
                                <i class="ri-delete-bin-5-fill fs-16"></i>
                            </a>
                        </li>
                    </ul>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="ri-hotel-bed-line fs-1 d-block mb-2"></i>
                    No rooms found. <a href="{{ route('rooms.create') }}" class="text-primary">Add a new room</a> to get started.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end mt-3">
    {{ $rooms->links() }}
</div>
