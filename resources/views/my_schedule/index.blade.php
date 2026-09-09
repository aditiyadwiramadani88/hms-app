@extends('layouts.master')
@section('title') My Schedule @endsection
@section('css')
    <style>
        .today-card { border-radius: 12px; overflow: hidden; }
        .today-card .card-body { padding: 24px; }
        .shift-badge-lg { font-size: 15px; font-weight: 700; padding: 10px 20px; border-radius: 8px; display: inline-block; }
        .week-cell { padding: 12px 8px; text-align: center; border-radius: 10px; transition: transform 0.15s; min-height: 110px; }
        .week-cell:hover { transform: translateY(-2px); }
        .week-cell .day-name { font-size: 11px; text-transform: uppercase; font-weight: 600; color: #6c757d; letter-spacing: 0.5px; }
        .week-cell .shift-code { font-size: 22px; font-weight: 800; }
        .week-cell .shift-time { font-size: 10px; color: #6c757d; }
        .month-cell { padding: 6px 3px; text-align: center; font-size: 11px; border-radius: 6px; min-width: 36px; min-height: 44px; cursor: default; transition: transform 0.1s; }
        .month-cell:hover { transform: scale(1.05); }
        .month-cell.today { border: 2px solid #405189; font-weight: 700; box-shadow: 0 2px 8px rgba(64,81,137,0.3); }
        .stat-mini { text-align: center; padding: 12px; border-radius: 8px; }
        .stat-mini .stat-value { font-size: 24px; font-weight: 800; }
        .stat-mini .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .countdown-text { font-size: 13px; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboard @endslot
        @slot('title') My Schedule @endslot
    @endcomponent

    <div class="row">
        {{-- Left Column: Today + Stats --}}
        <div class="col-xl-4">
            {{-- Today's Schedule Card --}}
            <div class="card today-card border-0 shadow-sm mb-3">
                <div class="card-body" style="background: linear-gradient(135deg, {{ $todaySchedule && $todaySchedule->shift ? $todaySchedule->shift->color . '15' : '#f0f4ff' }} 0%, #fff 100%);">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm me-3">
                            <div class="avatar-title bg-primary rounded-circle fs-20">
                                <i class="ri-calendar-check-fill"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-muted mb-0 fs-12">HARI INI</p>
                            <h6 class="mb-0">{{ now()->translatedFormat('l, d F Y') }}</h6>
                        </div>
                    </div>

                    @if($todaySchedule && $todaySchedule->shift)
                        <div class="shift-badge-lg text-white mb-3" style="background: {{ $todaySchedule->shift->color }};">
                            {{ $todaySchedule->shift->name }}
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <small class="text-muted d-block">Jam Masuk</small>
                                    <strong class="fs-16">{{ \Carbon\Carbon::parse($todaySchedule->shift->start_time)->format('H:i') }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <small class="text-muted d-block">Jam Pulang</small>
                                    <strong class="fs-16">{{ \Carbon\Carbon::parse($todaySchedule->shift->end_time)->format('H:i') }}</strong>
                                </div>
                            </div>
                        </div>

                        @if($todaySchedule->shift->break_start_time && $todaySchedule->shift->break_end_time)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ri-rest-time-line text-warning"></i>
                            <span class="fs-13">Istirahat: <strong>{{ \Carbon\Carbon::parse($todaySchedule->shift->break_start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($todaySchedule->shift->break_end_time)->format('H:i') }}</strong></span>
                        </div>
                        @endif

                        @if($todaySchedule->shift->start_time_2 && $todaySchedule->shift->end_time_2)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ri-time-line text-info"></i>
                            <span class="fs-13">Sesi 2: <strong>{{ \Carbon\Carbon::parse($todaySchedule->shift->start_time_2)->format('H:i') }} - {{ \Carbon\Carbon::parse($todaySchedule->shift->end_time_2)->format('H:i') }}</strong></span>
                        </div>
                        @endif

                        @if($todaySchedule->location)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="ri-map-pin-line text-primary"></i>
                            <span class="fs-13">Lokasi: <strong>{{ $todaySchedule->location }}</strong></span>
                        </div>
                        @endif

                        @if($todaySchedule->notes)
                        <div class="alert alert-warning border-0 py-2 px-3 mt-2 mb-0 fs-12">
                            <i class="ri-sticky-note-line me-1"></i> {{ $todaySchedule->notes }}
                        </div>
                        @endif

                        {{-- Countdown / Status --}}
                        @php
                            $now = now();
                            $shiftStart = \Carbon\Carbon::parse($today . ' ' . $todaySchedule->shift->start_time);
                            $shiftEnd = \Carbon\Carbon::parse($today . ' ' . $todaySchedule->shift->end_time);
                            if ($shiftEnd <= $shiftStart) $shiftEnd->addDay();
                        @endphp
                        <div class="mt-3 pt-3 border-top">
                            @if($now->lt($shiftStart))
                                <span class="countdown-text text-warning"><i class="ri-timer-line me-1"></i> Shift dimulai dalam <strong>{{ $now->diff($shiftStart)->format('%h jam %i menit') }}</strong></span>
                            @elseif($now->between($shiftStart, $shiftEnd))
                                <span class="countdown-text text-success"><i class="ri-checkbox-circle-line me-1"></i> Sedang dalam shift — selesai dalam <strong>{{ $now->diff($shiftEnd)->format('%h jam %i menit') }}</strong></span>
                            @else
                                <span class="countdown-text text-muted"><i class="ri-check-double-line me-1"></i> Shift hari ini sudah selesai</span>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="ri-zzz-line fs-1 text-muted d-block mb-2"></i>
                            <h5 class="text-muted">Hari Libur</h5>
                            <p class="text-muted mb-0 fs-13">Tidak ada jadwal hari ini. Selamat beristirahat!</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Monthly Stats --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header"><h6 class="card-title mb-0"><i class="ri-bar-chart-box-line me-1"></i> Statistik Bulan Ini</h6></div>
                <div class="card-body">
                    @php
                        $totalDays = $schedules->count();
                        $workDays = $schedules->filter(fn($s) => $s->shift && !$s->shift->is_off)->count();
                        $offDays = $schedules->filter(fn($s) => !$s->shift || $s->shift->is_off)->count();
                        $totalHours = 0;
                        foreach ($schedules as $s) {
                            if ($s->shift && !$s->shift->is_off && $s->shift->start_time && $s->shift->end_time) {
                                $start = \Carbon\Carbon::parse($s->shift->start_time);
                                $end = \Carbon\Carbon::parse($s->shift->end_time);
                                $mins = $start->diffInMinutes($end);
                                if ($s->shift->break_start_time && $s->shift->break_end_time) {
                                    $mins -= \Carbon\Carbon::parse($s->shift->break_start_time)->diffInMinutes(\Carbon\Carbon::parse($s->shift->break_end_time));
                                }
                                $totalHours += $mins / 60;
                            }
                        }
                    @endphp
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="stat-mini bg-primary-subtle">
                                <div class="stat-value text-primary">{{ $workDays }}</div>
                                <div class="stat-label text-primary">Hari Kerja</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-mini bg-success-subtle">
                                <div class="stat-value text-success">{{ $offDays }}</div>
                                <div class="stat-label text-success">Hari Libur</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-mini bg-info-subtle">
                                <div class="stat-value text-info">{{ round($totalHours) }}</div>
                                <div class="stat-label text-info">Total Jam</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Week + Month --}}
        <div class="col-xl-8">
            {{-- Week View --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header"><h6 class="card-title mb-0"><i class="ri-calendar-line me-1"></i> Minggu Ini</h6></div>
                <div class="card-body">
                    <div class="row g-2">
                        @php
                            $dayNames = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'];
                        @endphp
                        @for($d = $weekStart->copy(); $d <= $weekEnd; $d->addDay())
                            @php
                                $dateStr = $d->format('Y-m-d');
                                $ws = $weekSchedules->get($dateStr);
                                $isToday = $dateStr === $today;
                            @endphp
                            <div class="col">
                                <div class="week-cell border {{ $isToday ? 'border-primary border-2 shadow-sm' : '' }}" style="background: {{ $ws && $ws->shift && !$ws->shift->is_off ? $ws->shift->color . '15' : '#f8f9fa' }}">
                                    <div class="day-name {{ $isToday ? 'text-primary' : '' }}">{{ $dayNames[$d->format('l')] ?? $d->format('D') }}</div>
                                    <div class="fs-12 {{ $isToday ? 'text-primary fw-bold' : 'text-muted' }}">{{ $d->format('d/m') }}</div>
                                    @if($ws && $ws->shift && !$ws->shift->is_off)
                                        <div class="shift-code" style="color: {{ $ws->shift->color }}">{{ $ws->shift->code }}</div>
                                        <div class="shift-time">{{ \Carbon\Carbon::parse($ws->shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($ws->shift->end_time)->format('H:i') }}</div>
                                        @if($ws->location)<small class="text-muted" style="font-size:9px;"><i class="ri-map-pin-line"></i>{{ $ws->location }}</small>@endif
                                    @else
                                        <div class="shift-code text-muted" style="font-size: 16px;">—</div>
                                        <div class="shift-time text-muted">Libur</div>
                                    @endif
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Month Calendar --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h6 class="card-title mb-0 flex-grow-1"><i class="ri-calendar-2-line me-1"></i> {{ $month->translatedFormat('F Y') }}</h6>
                        <form method="GET" class="d-flex gap-1 align-items-center">
                            <a href="{{ route('my-schedule.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-arrow-left-s-line"></i></a>
                            <input type="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" style="width: 130px;">
                            <a href="{{ route('my-schedule.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-arrow-right-s-line"></i></a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Shift Legend --}}
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($shifts as $shift)
                            <span class="badge fs-11 py-1 px-2" style="background: {{ $shift->color }}20; color: {{ $shift->color }}; border: 1px solid {{ $shift->color }}50;">
                                {{ $shift->code }} = {{ $shift->name }}
                            </span>
                        @endforeach
                    </div>

                    {{-- Calendar Grid --}}
                    @php
                        $startOfMonth = $month->copy()->startOfMonth();
                        $endOfMonth = $month->copy()->endOfMonth();
                        $startPadding = $startOfMonth->dayOfWeek; // 0=Sun
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" style="table-layout: fixed;">
                            <thead>
                                <tr class="text-center">
                                    <th class="py-2 text-danger" style="width:14.28%;">Min</th>
                                    <th class="py-2" style="width:14.28%;">Sen</th>
                                    <th class="py-2" style="width:14.28%;">Sel</th>
                                    <th class="py-2" style="width:14.28%;">Rab</th>
                                    <th class="py-2" style="width:14.28%;">Kam</th>
                                    <th class="py-2" style="width:14.28%;">Jum</th>
                                    <th class="py-2 text-warning" style="width:14.28%;">Sab</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentDay = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                                @endphp
                                @while($currentDay <= $endOfMonth || $currentDay->dayOfWeek != 0)
                                <tr>
                                    @for($i = 0; $i < 7; $i++)
                                        @php
                                            $dateStr = $currentDay->format('Y-m-d');
                                            $isCurrentMonth = $currentDay->month === $month->month;
                                            $isToday = $dateStr === $today;
                                            $s = $isCurrentMonth ? $schedules->get($dateStr) : null;
                                            $bgColor = $s && $s->shift && !$s->shift->is_off ? $s->shift->color . '20' : 'transparent';
                                        @endphp
                                        <td class="text-center p-1 {{ !$isCurrentMonth ? 'bg-light' : '' }} {{ $isToday ? 'border-primary border-2' : '' }}" style="height: 60px; vertical-align: top; background: {{ $isCurrentMonth ? $bgColor : '' }};">
                                            @if($isCurrentMonth)
                                                <div class="fw-{{ $isToday ? 'bold' : 'normal' }} {{ $isToday ? 'text-primary' : ($i == 0 ? 'text-danger' : ($i == 6 ? 'text-warning' : '')) }}" style="font-size: 12px;">
                                                    {{ $currentDay->format('j') }}
                                                </div>
                                                @if($s && $s->shift && !$s->shift->is_off)
                                                    <div class="badge mt-1" style="background: {{ $s->shift->color }}; font-size: 10px; padding: 3px 6px;">
                                                        {{ $s->shift->code }}
                                                    </div>
                                                @elseif($s && $s->shift && $s->shift->is_off)
                                                    <div class="text-muted mt-1" style="font-size: 9px;">OFF</div>
                                                @endif
                                            @endif
                                        </td>
                                        @php $currentDay->addDay(); @endphp
                                    @endfor
                                </tr>
                                @if($currentDay > $endOfMonth && $currentDay->dayOfWeek == 0)
                                    @break
                                @endif
                                @endwhile
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#swapModal">
                        <i class="ri-swap-line me-1"></i> Ajukan Tukar Shift
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Swap Modal --}}
    <div class="modal fade" id="swapModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" data-ajax="true" action="{{ route('shift-swaps.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-swap-line me-1 text-primary"></i>Ajukan Tukar Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jadwal Saya yang Mau Ditukar</label>
                            <select name="requester_schedule_id" class="form-select" id="modalRequesterSchedule" required>
                                <option value="">— Pilih Jadwal —</option>
                                @foreach($mySchedulesForSwap as $sched)
                                    <option value="{{ $sched->id }}" data-date="{{ $sched->schedule_date->format('Y-m-d') }}">
                                        {{ $sched->schedule_date->translatedFormat('l, d M Y') }} — {{ $sched->shift?->name ?? 'No Shift' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tukar Dengan Karyawan</label>
                            <select name="target_employee_id" class="form-select" id="modalTargetEmployee">
                                <option value="">— Pilih Karyawan —</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="modalTargetScheduleSection" style="display:none;">
                            <label class="form-label fw-semibold">Pilih Tanggal Jadwal Target</label>
                            <select name="target_schedule_id" class="form-select" id="modalTargetSchedule" required disabled>
                                <option value="">— Pilih Jadwal Target —</option>
                            </select>
                            <div id="modalTargetScheduleInfo" class="mt-2 text-muted small"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alasan</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Perlu antar anak sekolah pagi..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Kirim Pengajuan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('script')
    <script>
        document.getElementById('modalTargetEmployee').addEventListener('change', function() {
            const section = document.getElementById('modalTargetScheduleSection');
            const select = document.getElementById('modalTargetSchedule');
            const info = document.getElementById('modalTargetScheduleInfo');
            select.innerHTML = '<option value="">Loading...</option>';
            select.disabled = true;
            section.style.display = 'none';

            if (!this.value) return;

            const requesterSelect = document.getElementById('modalRequesterSchedule');
            const selectedOption = requesterSelect.options[requesterSelect.selectedIndex];
            const requesterDate = selectedOption?.getAttribute('data-date');
            if (!requesterDate) {
                alert('Pilih jadwal Anda terlebih dahulu.');
                this.value = '';
                return;
            }

            const url = "{{ route('shift-swaps.employee-schedule', ['employeeId' => '_emp_', 'date' => '_date_']) }}"
                .replace('_emp_', this.value)
                .replace('_date_', requesterDate);

            fetch(url)
                .then(r => { if (!r.ok) throw new Error('Not found'); return r.json(); })
                .then(data => {
                    if (data.error) {
                        info.innerHTML = '<span class="text-danger">' + data.error + '</span>';
                        section.style.display = 'block';
                        return;
                    }
                    select.innerHTML = `<option value="${data.id}">${data.shift?.name || 'Shift'} — ${data.location || 'No location'}</option>`;
                    select.disabled = false;
                    info.innerHTML = `<span class="text-success"><i class="ri-check-line"></i> Shift: ${data.shift?.code || '-'} (${data.shift?.start_time || '-'} - ${data.shift?.end_time || '-'})</span>`;
                    section.style.display = 'block';
                })
                .catch(() => {
                    info.innerHTML = '<span class="text-danger">Tidak ada jadwal ditemukan untuk tanggal ini.</span>';
                    section.style.display = 'block';
                });
        });

        document.getElementById('modalRequesterSchedule').addEventListener('change', function() {
            document.getElementById('modalTargetScheduleSection').style.display = 'none';
            document.getElementById('modalTargetEmployee').value = '';
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltips.map(function(el) { return new bootstrap.Tooltip(el); });
        });
    </script>
@endsection
