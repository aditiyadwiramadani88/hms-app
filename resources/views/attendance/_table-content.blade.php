<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-nowrap align-middle mb-0">
            <thead class="table-light text-muted">
                <tr>
                    <th>Karyawan</th>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Break</th>
                    <th>Lama Kerja</th>
                    <th>Status</th>
                    <th>Telat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                <tr>
                    <td>{{ $att->employee->name ?? '-' }}</td>
                    <td>{{ $att->attendance_date->format('d/m/Y') }}</td>
                    <td>
                        {{ $att->shift->name ?? '-' }}
                        @php
                            $schedule = $att->schedule;
                            $hasSwap = $schedule && ($schedule->swapRequestsAsRequester->where('status', 'approved')->count() > 0);
                            $isOvertime = $schedule && $schedule->is_overtime;
                        @endphp
                        @if($hasSwap)
                            <br><span class="badge bg-purple-subtle text-purple fs-10 mt-1" title="Tukar Shift">Tukar Shift</span>
                        @endif
                        @if($isOvertime)
                            <br><span class="badge bg-warning-subtle text-warning fs-10 mt-1">Overtime</span>
                        @endif
                    </td>
                    <td>{{ $att->check_in_time ? $att->check_in_time->format('H:i') : '-' }}</td>
                    <td>{{ $att->check_out_time ? $att->check_out_time->format('H:i') : '-' }}</td>
                    <td>
                        @if($att->break_start_time && $att->break_end_time)
                            @php $breakMin = $att->break_start_time->diffInMinutes($att->break_end_time); @endphp
                            <span class="text-muted fs-11">{{ $att->break_start_time->format('H:i') }} - {{ $att->break_end_time->format('H:i') }}</span>
                            <br><small class="text-purple">{{ floor($breakMin/60) }}j {{ $breakMin%60 }}m</small>
                        @elseif($att->break_start_time)
                            <span class="text-warning fs-11">{{ $att->break_start_time->format('H:i') }} - ...</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($att->check_in_time && $att->check_out_time)
                            @php
                                $totalMin = $att->check_in_time->diffInMinutes($att->check_out_time);
                                $breakMin = ($att->break_start_time && $att->break_end_time) ? $att->break_start_time->diffInMinutes($att->break_end_time) : 0;
                                $workMin = $totalMin - $breakMin;
                            @endphp
                            <span class="fw-medium">{{ floor($workMin/60) }}j {{ $workMin%60 }}m</span>
                            @if($breakMin > 0)
                                <br><small class="text-muted fs-10">({{ floor($totalMin/60) }}j{{ $totalMin%60>0 ? ' '.($totalMin%60).'m' : '' }} - {{ floor($breakMin/60) }}j{{ $breakMin%60>0 ? ' '.($breakMin%60).'m' : '' }})</small>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $statusMap = ['present' => 'Hadir', 'late' => 'Terlambat', 'early_leave' => 'Pulang Awal', 'late_and_early_leave' => 'Telat & PA', 'absent' => 'Absen'];
                            $colorMap = ['present' => 'success', 'late' => 'warning', 'early_leave' => 'info', 'late_and_early_leave' => 'danger', 'absent' => 'danger'];
                        @endphp
                        <span class="badge bg-{{ $colorMap[$att->status] ?? 'secondary' }} status-badge">{{ $statusMap[$att->status] ?? $att->status }}</span>
                    </td>
                    <td>{{ $att->late_minutes > 0 ? $att->late_minutes . ' menit' : '-' }}</td>
                    <td>{{ $att->early_leave_minutes > 0 ? $att->early_leave_minutes . ' menit' : '-' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('attendance.report.export.pdf', array_merge(request()->query(), ['employee_id' => $att->employee_id])) }}" class="btn btn-soft-secondary btn-sm me-1" target="_blank" title="Print PDF">
                            <i class="ri-printer-line"></i>
                        </a>
                        <button class="btn btn-soft-primary btn-sm" onclick="showDetail({{ $att->id }})" title="Detail">
                            <i class="ri-eye-line"></i>
                        </button>
                        <button class="btn btn-soft-warning btn-sm" onclick="showEdit({{ $att->id }})" title="Edit">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="btn btn-soft-info btn-sm" onclick="fixStatus({{ $att->id }})" title="Fix Status">
                            <i class="ri-magic-line"></i>
                        </button>
                        @if($att->check_out_time)
                        <button class="btn btn-soft-danger btn-sm" onclick="resetCheckout({{ $att->id }})" title="Reset Checkout">
                            <i class="ri-refresh-line"></i>
                        </button>
                        @endif
                        <button class="btn btn-soft-danger btn-sm" onclick="deleteAttendance({{ $att->id }})" title="Hapus Data (Reset Absen)">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-muted">Tidak ada data absensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($attendances->hasPages())
<div class="card-footer">
    {{ $attendances->links() }}
</div>
@endif
