@extends('layouts.master')
@section('title')
    Dashboard OB - Cleaning Tasks
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            OB
        @endslot
        @slot('title')
            Dashboard
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0 font-size-18">Task Cleaning</h4>
                <span class="badge bg-warning fs-6">
                    <i class="ri-time-line me-1"></i>
                    <span id="pendingBadge">{{ $pendingCount }}</span> task belum dimulai
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            @if($tasks->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="ri-checkbox-multiple-blank-line display-4 text-muted"></i>
                        <h5 class="mt-3 text-muted">Tidak ada task saat ini</h5>
                        <p class="text-muted">Task cleaning akan muncul di sini ketika admin meng-assign task untuk Anda.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th>Status</th>
                                        <th>Kamar</th>
                                        <th>Lantai</th>
                                        <th>Tipe</th>
                                        <th>Progress</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tasks as $task)
                                    <tr>
                                        <td>
                                            @if($task->status === 'belum_mulai')
                                                <span class="badge bg-warning text-dark">Belum Mulai</span>
                                            @elseif($task->status === 'sedang_dikerjakan')
                                                <span class="badge bg-info">Sedang Dikerjakan</span>
                                            @else
                                                <span class="badge bg-success">Selesai</span>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">{{ $task->room->room_number ?? '-' }}</td>
                                        <td>Lantai {{ $task->room->floor ?? '-' }}</td>
                                        <td>{{ $task->room->roomType->name ?? '-' }}</td>
                                        <td>
                                            @php $progress = $task->calculateProgress(); @endphp
                                            <div class="progress" style="height: 8px; width: 100px;">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $progress }}%</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('ob.tasks.show', $task) }}" class="btn btn-primary btn-sm">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
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
                const response = await fetch('{{ route('ob.dashboard') }}');
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.table');
                const currentTable = document.querySelector('.table');
                if (newTable && currentTable) {
                    currentTable.closest('.card-body').innerHTML = newTable.closest('.card-body').innerHTML;
                }
            } catch (e) {
                console.error('Failed to refresh task list:', e);
            }
        }

        async function updateBadgeCounter() {
            try {
                const response = await fetch('{{ route('ob.badge-count') }}');
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
