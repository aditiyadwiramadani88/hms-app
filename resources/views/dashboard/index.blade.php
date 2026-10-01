@extends('layouts.master')
@section('title')
    Dashboard
@endsection
@section('content')
    <x-breadcrumb title="Hotel Dashboard Overview" :links="[]" />

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">System Statistics</h5>
                <a href="{{ route('bookings.calendar') }}" class="btn btn-primary">
                    <i class="ri-calendar-event-line align-bottom me-1"></i> View Operational Calendar
                </a>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Unpaid Check-outs Notification --}}
    @if(isset($unpaidDepartures) && $unpaidDepartures->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-start-width-3 border-danger" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="ri-error-warning-fill fs-24 text-danger me-3"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading fw-bold mb-1">Peringatan: Tagihan Belum Lunas!</h6>
                        <p class="mb-0 fs-13">Terdapat <strong>{{ $unpaidDepartures->count() }} tamu</strong> yang dijadwalkan Check-out hari ini namun belum melunasi pembayaran:</p>
                        <div class="mt-2">
                            @foreach($unpaidDepartures as $unpaid)
                                @php
                                    $manualExtraTotal = $unpaid->transactions->where('type', 'charge')->where('status', 'success')->where('reference_id', null)->where('is_deposit', false)->sum('amount');
                                    $posTotal = $unpaid->posOrders->sum('total_amount');
                                    $totalDeposit = $unpaid->transactions->where('type', 'charge')->where('status', 'success')->where('is_deposit', true)->sum('amount');
                                    $gTotal = $unpaid->total_price + $manualExtraTotal + $posTotal + $totalDeposit;
                                    $tPaid = $unpaid->transactions->where('type', 'payment')->where('status', 'success')->sum('amount');
                                    $rem = $gTotal - $tPaid;
                                @endphp
                                <a href="{{ route('bookings.show', $unpaid->id) }}" class="badge bg-danger-subtle text-danger p-2 me-1 mb-1 border border-danger-subtle">
                                    <i class="ri-hotel-bed-line me-1"></i> Kamar {{ $unpaid->room->room_number ?? 'N/A' }} ({{ $unpaid->guest->name ?? 'Tamu' }}) - Sisa: Rp {{ number_format($rem, 0, ',', '.') }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    {{-- First Row: Primary Stats --}}
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Rooms</p>
                        </div>
                        <div class="flex-shrink-0">
                            <h5 class="text-success fs-14 mb-0">
                                <i class="ri-checkbox-circle-line align-middle"></i> 100%
                            </h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $totalRooms ?? 0 }}</h4>
                            <span class="badge bg-primary-subtle text-primary">All Units</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded fs-3">
                                <i class="ri-hotel-bed-fill text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Occupancy Rate</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $occupancyRate ?? 0 }}%</h4>
                            <span class="badge bg-success-subtle text-success">Live Status</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="ri-pie-chart-fill text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('bookings.index', ['tab' => 'today']) }}" class="text-decoration-none">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Check-ins Today</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $todayArrivals ?? 0 }}</h4>
                            <span class="badge bg-info-subtle text-info">Arrivals</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="ri-login-circle-fill text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ url('admin/bookings?tab=checkin&checkout_date=' . now()->toDateString()) }}" class="text-decoration-none">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Check-outs Today</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $todayCheckOuts ?? 0 }}</h4>
                            <span class="badge bg-warning-subtle text-warning">Departures</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="ri-logout-box-r-fill text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card card-animate border-start border-start-width-3 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Monthly Financial Performance</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-info-subtle text-info">This Month</span>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="fs-18 fw-semibold ff-secondary mb-1">
                                Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}
                            </h4>
                            <p class="text-muted mb-0 fs-12 text-uppercase">Total Gross (Kas)</p>
                        </div>
                        <div class="col-6">
                            <h4 class="fs-18 fw-semibold ff-secondary mb-1 text-success">
                                Rp {{ number_format($realizedRevenue, 0, ',', '.') }}
                            </h4>
                            <p class="text-muted mb-0 fs-12 text-uppercase">Realized (Hak Owner)</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top border-top-dashed">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="ri-information-line fs-16 text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <p class="text-muted mb-0 fs-11">
                                    <strong>Gross</strong>: Semua uang masuk. 
                                    <strong>Realized</strong>: Uang sah (sudah checkout/POS).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-animate border-start border-start-width-3 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Wallet Balance Summary</p>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="ri-wallet-3-line fs-20 text-success"></i>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h4 class="fs-18 fw-semibold ff-secondary mb-1">
                                Rp {{ number_format($totalBalance, 0, ',', '.') }}
                            </h4>
                            <p class="text-muted mb-0 fs-12 text-uppercase">Total Cash on Hand</p>
                        </div>
                        <div class="col-6">
                            <h4 class="fs-18 fw-semibold ff-secondary mb-1 text-primary">
                                Rp {{ number_format($availableBalance, 0, ',', '.') }}
                            </h4>
                            <p class="text-muted mb-0 fs-12 text-uppercase">Ready to Withdraw</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top border-top-dashed text-center">
                        <a href="{{ route('bank-accounts.index') }}" class="btn btn-link link-primary p-0 fs-12">Manage Wallets & Finance <i class="ri-arrow-right-s-line align-middle"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 d-none">
            {{-- Original card kept hidden for logic but replaced by better UI above --}}
        </div>
        <div class="col-xl col-md-4 col-6">
            <a href="{{ route('rooms.index', ['status' => 'Available']) }}" class="text-decoration-none">
            <div class="card card-animate border-start border-start-width-3 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Available Rooms</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $availableRoomsCount ?? 0 }}</h4>
                            <span class="badge bg-success-subtle text-success">Ready to Sell</span>
                            <div class="mt-1">
                                <small class="text-muted">dari {{ $totalRooms }} total kamar</small><br>
                                <small class="text-muted">
                                    {{ ($inHouseRooms ?? 0) }} in-house · {{ ($maintenanceCount ?? 0) }} maint · {{ ($dirtyRoomsCount ?? 0) }} dirty
                                </small>
                            </div>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded fs-3">
                                <i class="ri-checkbox-multiple-line text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-xl col-md-4 col-6">
            <a href="{{ route('housekeeping.index') }}" class="text-decoration-none">
            <div class="card card-animate border-start border-start-width-3 border-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Dirty / To Clean</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $dirtyRoomsCount ?? 0 }}</h4>
                            <span class="badge bg-danger-subtle text-danger">Housekeeping</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded fs-3">
                                <i class="ri-brush-line text-danger"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-xl col-md-4 col-6">
            <a href="{{ route('rooms.index', ['status' => 'maintenance']) }}" class="text-decoration-none">
            <div class="card card-animate border-start border-start-width-3 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Maintenance / OOO</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $maintenanceCount ?? 0 }}</h4>
                            <span class="badge bg-warning-subtle text-warning">Under Repair</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded fs-3">
                                <i class="ri-tools-line text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-xl col-md-4 col-6">
            <a href="{{ route('bookings.index', ['tab' => 'checkin']) }}" class="text-decoration-none">
            <div class="card card-animate border-start border-start-width-3 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">In-House Rooms</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-2">{{ $inHouseRooms ?? 0 }}</h4>
                            <span class="badge bg-info-subtle text-info mb-2">Kamar Terisi</span>
                            @if(!empty($inHouseBySource) && $inHouseBySource->count())
                                <div class="small text-muted" style="max-height: 90px; overflow-y: auto; line-height: 1.6;">
                                    @foreach($inHouseBySource as $sourceName => $sourceCount)
                                        <div>{{ $sourceName }} &ndash; {{ $sourceCount }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded fs-3">
                                <i class="ri-hotel-bed-line text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-xl col-md-4 col-6">
            <a href="{{ route('bookings.index', ['tab' => 'checkin']) }}" class="text-decoration-none">
            <div class="card card-animate border-start border-start-width-3 border-purple" style="border-color: #6f42c1 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">In-House Guests</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $inHouseGuests ?? 0 }}</h4>
                            <span class="badge bg-info-subtle text-info">Total Tamu</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded fs-3" style="background: #6f42c120;">
                                <i class="ri-group-line" style="color:#6f42c1;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            </a>
        </div>
    </div>

    {{-- Third Row: Occupancy Overview Chart --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Occupancy Overview (Last 7 Days)</h4>
                </div>
                <div class="card-body">
                    <div id="occupancy-chart" class="apex-charts" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fourth Row: Recent Activity Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom-dashed align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">
                        <i class="ri-history-line me-2 text-primary"></i>Recent Activity
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th scope="col" style="width: 50px;">#</th>
                                    <th scope="col">User</th>
                                    <th scope="col">Action</th>
                                    <th scope="col">Description</th>
                                    <th scope="col" class="text-end">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivities ?? [] as $activity)
                                <tr>
                                    <td>
                                        <div class="avatar-xs">
                                            <span class="avatar-title bg-{{ 
                                                str_contains($activity->action, 'created') ? 'success' : 
                                                (str_contains($activity->action, 'deleted') ? 'danger' : 'info') 
                                            }}-subtle text-{{ 
                                                str_contains($activity->action, 'created') ? 'success' : 
                                                (str_contains($activity->action, 'deleted') ? 'danger' : 'info') 
                                            }} rounded-circle">
                                                <i class="{{ 
                                                    str_contains($activity->action, 'room') ? 'ri-hotel-bed-line' : 
                                                    (str_contains($activity->action, 'payment') ? 'ri-money-dollar-circle-line' : 'ri-notification-3-line') 
                                                }}"></i>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $activity->user->name ?? 'System' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ ucwords(str_replace(['.', '_'], ' ', $activity->action)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted fs-13">{{ $activity->description }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold text-dark fs-12 d-block">
                                            <i class="ri-time-line text-muted me-1"></i>{{ $activity->created_at->format('d M Y, H:i') }}
                                        </span>
                                        <small class="text-muted fs-11">{{ $activity->created_at->diffForHumans() }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="ri-history-line fs-24 d-block mb-1"></i>
                                        No recent activities.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($occupancyChartData);
            const options = {
                series: [{
                    name: 'Occupied Rooms',
                    data: chartData.map(item => item.count)
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '45%',
                        distributed: true,
                    }
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                colors: ['#405189', '#405189', '#405189', '#405189', '#405189', '#405189', '#0ab39c'],
                xaxis: { categories: chartData.map(item => item.date) },
                yaxis: { title: { text: 'Rooms' }, min: 0, forceNiceScale: true },
                tooltip: { y: { formatter: (val) => val + " Rooms" } }
            };
            const chart = new ApexCharts(document.querySelector("#occupancy-chart"), options);
            chart.render();
        });
    </script>
@endsection
