@extends('layouts.master')
@section('title')
    Dashboard Housekeeping - Task Saya
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Housekeeping
        @endslot
        @slot('title')
            Task Saya
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0 font-size-18">Task Cleaning</h4>
                <span class="badge bg-warning fs-6" id="pendingBadgeHeader">
                    <i class="ri-time-line me-1"></i>
                    <span id="pendingBadge">{{ $pendingCount }}</span> task belum dimulai
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#activeTasks" role="tab">
                                <i class="ri-list-check me-1"></i> Active Tasks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#todayReport" role="tab">
                                <i class="ri-file-chart-line me-1"></i> Laporan Hari Ini
                                @if($completedTasks->isNotEmpty())
                                    <span class="badge bg-success ms-1">{{ $completedTasks->count() }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content p-0">
                    {{-- Tab 1: Active Tasks --}}
                    <div class="tab-pane active" id="activeTasks" role="tabpanel">
                        @if($tasks->isEmpty())
                            <div class="text-center py-5">
                                <i class="ri-checkbox-multiple-blank-line display-4 text-muted"></i>
                                <h5 class="mt-3 text-muted">Tidak ada task saat ini</h5>
                                <p class="text-muted">Task cleaning akan muncul di sini ketika admin meng-assign task untuk Anda.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>Status</th>
                                            <th>Kamar</th>
                                            <th>Lantai</th>
                                            <th>Tipe</th>
                                            <th>Booking Source</th>
                                            <th>Progress</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tasks as $task)
                                        @php $room = $task->room; @endphp
                                        <tr>
                                            <td>
                                                @if(!empty($task->dirty_room_unassigned))
                                                    <span class="badge bg-secondary">Belum Di-assign</span>
                                                @elseif($task->status === 'belum_mulai')
                                                    <span class="badge bg-warning text-dark">Belum Mulai</span>
                                                @elseif($task->status === 'sedang_dikerjakan')
                                                    <span class="badge bg-info">Sedang Dikerjakan</span>
                                                @elseif($task->status === 'menunggu_verifikasi')
                                                    <span class="badge bg-primary">Menunggu Verifikasi</span>
                                                @elseif($task->status === 'revisi')
                                                    <span class="badge bg-danger">Perlu Revisi</span>
                                                @else
                                                    <span class="badge bg-success">Selesai</span>
                                                @endif
                                            </td>
                                            <td class="fw-semibold">{{ $room->room_number ?? '-' }}</td>
                                            <td>Lantai {{ $room->floor ?? '-' }}</td>
                                            <td>{{ $room->roomType->name ?? '-' }}</td>
                                            <td>
                                                @php $currentBooking = $room->bookings->first(); @endphp
                                                @if($currentBooking)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $currentBooking->bookingSource->name ?? '-' }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if(!empty($task->dirty_room_unassigned))
                                                    <span class="text-muted">-</span>
                                                @else
                                                    @php $progress = $task->status === 'selesai' ? 100 : $task->calculateProgress(); @endphp
                                                    <div class="progress" style="height: 8px; width: 100px;">
                                                        <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%"></div>
                                                    </div>
                                                    <small class="text-muted">{{ $progress }}%</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if(empty($task->dirty_room_unassigned))
                                                    <a href="{{ route('housekeeping.my-tasks.show', $task) }}" class="btn btn-primary btn-sm">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted fs-11">Hubungi admin</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Tab 2: Today Report --}}
                    <div class="tab-pane" id="todayReport" role="tabpanel">
                        @if($completedTasks->isEmpty())
                            <div class="text-center py-5">
                                <i class="ri-file-chart-line display-4 text-muted"></i>
                                <h5 class="mt-3 text-muted">Belum ada task selesai hari ini</h5>
                                <p class="text-muted">Task yang sudah selesai akan muncul di sini.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>Kamar</th>
                                            <th>Lantai</th>
                                            <th>Tipe</th>
                                            <th>Sumber</th>
                                            <th>Mulai</th>
                                            <th>Selesai</th>
                                            <th>Durasi</th>
                                            <th>Checklist</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($completedTasks as $task)
                                        @php
                                            $room = $task->room;
                                            $totalItems = $task->checklistItems->count();
                                            $doneItems = $task->checklistItems->where('is_done', true)->count();
                                            $duration = $task->started_at && $task->completed_at
                                                ? $task->started_at->diffInMinutes($task->completed_at)
                                                : null;
                                            $currentBooking = $room?->bookings->first();
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $room->room_number ?? '-' }}</td>
                                            <td>Lantai {{ $room->floor ?? '-' }}</td>
                                            <td>{{ $room->roomType->name ?? '-' }}</td>
                                            <td>
                                                @if($currentBooking)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $currentBooking->bookingSource->name ?? '-' }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $task->started_at?->format('H:i') ?? '-' }}</td>
                                            <td>{{ $task->completed_at?->format('H:i') ?? '-' }}</td>
                                            <td>
                                                @if($duration !== null)
                                                    {{ floor($duration / 60) }}j {{ $duration % 60 }}m
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $doneItems }}/{{ $totalItems }}</td>
                                            <td><span class="badge bg-success">Selesai</span></td>
                                            <td>
                                                <a href="{{ route('housekeeping.my-tasks.show', $task) }}" class="btn btn-primary btn-sm">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-muted fs-12">
                                Total task selesai hari ini: <strong>{{ $completedTasks->count() }}</strong>
                                @php
                                    $totalDuration = $completedTasks->sum(fn($t) =>
                                        $t->started_at && $t->completed_at
                                            ? $t->started_at->diffInMinutes($t->completed_at)
                                            : 0
                                    );
                                @endphp
                                @if($totalDuration > 0)
                                    | Total waktu: <strong>{{ floor($totalDuration / 60) }}j {{ $totalDuration % 60 }}m</strong>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Real-time: Listen for new task assignments
        if (typeof window.Echo !== 'undefined') {
            const userId = {{ auth()->id() }};

            window.Echo.private('ob-tasks.' + userId)
                .listen('CleaningTaskAssigned', (e) => {
                    // Show notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Task Baru!',
                            text: `Kamar ${e.room_number} (Lt. ${e.floor}) perlu dibersihkan`,
                            icon: 'info',
                            toast: true,
                            position: 'top-end',
                            timer: 5000,
                            showConfirmButton: false,
                        });
                    }

                    // Reload task list
                    refreshTaskList();
                    // Update badge counter
                    updateBadgeCounter();
                });

            // Auto-reconnect on disconnect
            let reconnectInterval;
            window.Echo.connector.pusher.connection.bind('disconnected', () => {
                reconnectInterval = setInterval(() => {
                    window.Echo.connector.pusher.connect();
                }, 5000);
            });
            window.Echo.connector.pusher.connection.bind('connected', () => {
                clearInterval(reconnectInterval);
            });
        }

        async function refreshTaskList() {
            try {
                const response = await fetch('{{ route('housekeeping.my-tasks') }}');
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newActiveTab = doc.querySelector('#activeTasks');
                const currentActiveTab = document.querySelector('#activeTasks');
                if (newActiveTab && currentActiveTab) {
                    currentActiveTab.innerHTML = newActiveTab.innerHTML;
                }
                const newReportTab = doc.querySelector('#todayReport');
                const currentReportTab = document.querySelector('#todayReport');
                if (newReportTab && currentReportTab) {
                    currentReportTab.innerHTML = newReportTab.innerHTML;
                }
            } catch (e) {
                console.error('Failed to refresh task list:', e);
            }
        }

        async function updateBadgeCounter() {
            try {
                const response = await fetch('{{ route('housekeeping.my-tasks.badge-count') }}');
                const data = await response.json();
                const badge = document.getElementById('pendingBadge');
                if (badge) {
                    badge.textContent = data.count;
                }
            } catch (e) {
                console.error('Failed to update badge:', e);
            }
        }
    </script>
    @endpush
@endsection