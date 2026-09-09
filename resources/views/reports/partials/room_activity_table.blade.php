<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Room</th>
                <th>Guest Name</th>
                <th>Check-In Date/Time</th>
                <th>Check-Out Date/Time</th>
                <th>Status</th>
                <th>Input By (User)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $index => $activity)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $activity->room ? $activity->room->room_number : ($activity->custom_room_name ?? 'N/A') }}</td>
                    <td>{{ $activity->guest ? $activity->guest->name : 'N/A' }}</td>
                    <td>{{ $activity->actual_check_in ? $activity->actual_check_in->format('Y-m-d H:i') : ($activity->check_in ? $activity->check_in->format('Y-m-d') : 'N/A') }}</td>
                    <td>{{ $activity->actual_check_out ? $activity->actual_check_out->format('Y-m-d H:i') : ($activity->check_out ? $activity->check_out->format('Y-m-d') : 'N/A') }}</td>
                    <td>
                        @if($activity->status == 'checked_in')
                            <span class="badge bg-primary">Checked In</span>
                        @elseif($activity->status == 'checked_out')
                            <span class="badge bg-success">Checked Out</span>
                        @elseif($activity->status == 'cancelled')
                            <span class="badge bg-danger">Cancelled</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($activity->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $activity->user ? $activity->user->name : 'System' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No room activity found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>