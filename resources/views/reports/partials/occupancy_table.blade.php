<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">Average Occupancy</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2">{{ $report['average_occupancy_rate'] ?? 0 }}%</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">Total Bookings</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2">{{ $report['total_bookings'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">Available Room Nights</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2">{{ $report['available_room_nights'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="text-uppercase fw-medium text-muted mb-0">Occupied Nights</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-0 mt-2">{{ $report['occupied_room_nights'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-nowrap align-middle table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th class="text-center">Total Rooms</th>
                <th class="text-center">Occupied</th>
                <th class="text-center">Available</th>
                <th class="text-center">Occupancy %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['daily_occupancy'] ?? [] as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                <td class="text-center">{{ $day['total_rooms'] ?? $report['operational_rooms'] }}</td>
                <td class="text-center">{{ $day['occupied'] }}</td>
                <td class="text-center">{{ $day['available'] }}</td>
                <td class="text-center">
                    <span class="badge bg-{{ $day['occupancy_rate'] > 70 ? 'success' : ($day['occupancy_rate'] > 30 ? 'info' : 'warning') }}-subtle text-{{ $day['occupancy_rate'] > 70 ? 'success' : ($day['occupancy_rate'] > 30 ? 'info' : 'warning') }}">
                        {{ $day['occupancy_rate'] }}%
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No data available</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
