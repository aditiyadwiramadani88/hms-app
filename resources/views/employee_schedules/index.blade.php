@extends('layouts.master')
@section('title') Jadwal Karyawan @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .schedule-grid { font-size: 12px; }
        .schedule-grid .table th, .schedule-grid .table td { padding: 4px 6px; white-space: nowrap; text-align: center; vertical-align: middle; }
        .schedule-grid .table th { position: sticky; top: 0; z-index: 2; background: #f8f9fa; }
        .schedule-grid .emp-name { text-align: left; min-width: 140px; font-weight: 600; position: sticky; left: 0; z-index: 3; background: #fff; }
        .schedule-grid .table th:first-child { position: sticky; left: 0; z-index: 4; background: #f8f9fa; }
        .schedule-cell { cursor: pointer; border-radius: 3px; padding: 2px 4px; display: inline-block; min-width: 32px; font-weight: 600; font-size: 11px; transition: transform 0.1s; position: relative; }
        .schedule-cell:hover { opacity: 0.8; transform: scale(1.1); }
        .schedule-cell.pending-change { outline: 2px solid #ffc107; outline-offset: 1px; animation: pulse 1s infinite; }
        .inline-shift-picker { position: absolute; top: 100%; left: 50%; transform: translateX(-50%); z-index: 100; background: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 4px; display: flex; gap: 3px; flex-wrap: wrap; min-width: 120px; }
        .inline-shift-picker .pick-opt { cursor: pointer; border-radius: 3px; padding: 3px 6px; font-weight: 600; font-size: 10px; color: #fff; transition: transform 0.1s; }
        .inline-shift-picker .pick-opt:hover { transform: scale(1.15); }
        .inline-shift-picker .pick-opt.pick-clear { background: #f8d7da; color: #dc3545; }
        .schedule-grid .total-jam { font-weight: 700; min-width: 70px; }
        .day-header { font-size: 10px; }
        .day-header.sun { color: #dc3545; }
        .day-header.sat { color: #fd7e14; }
        .paint-toolbar { background: #f0f4ff; border: 1px solid #d0d7e6; border-radius: 8px; padding: 10px 16px; }
        .paint-toolbar.active { background: #e8f5e9; border-color: #4caf50; }
        .shift-option { cursor: pointer; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 12px; border: 2px solid transparent; transition: all 0.15s; display: inline-block; }
        .shift-option:hover { transform: scale(1.05); }
        .shift-option.selected { border-color: #333; box-shadow: 0 0 0 2px rgba(0,0,0,0.2); }
        .pending-count-badge { font-size: 13px; }
        @keyframes pulse { 0%,100% { outline-color: #ffc107; } 50% { outline-color: #ff9800; } }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Employee Schedule @endslot
        @slot('title') Jadwal Bulanan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-0">
            <div class="row align-items-center gy-3">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-calendar-line me-2 text-primary"></i>Jadwal Kerja — {{ $month->format('F Y') }}</h5>
                </div>
                <div class="col-sm-auto">
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <form method="GET" class="d-flex gap-2 align-items-center">
                            <a href="{{ route('employee-schedules.index', ['month' => $month->copy()->subMonth()->format('Y-m'), 'location' => $location]) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-arrow-left-s-line"></i></a>
                            <input type="month" name="month" class="form-control form-control-sm" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()">
                            <a href="{{ route('employee-schedules.index', ['month' => $month->copy()->addMonth()->format('Y-m'), 'location' => $location]) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-arrow-right-s-line"></i></a>
                            <select name="location" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->name }}" @selected($location == $loc->name)>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </form>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkAssignModal"><i class="ri-play-list-add-line align-bottom me-1"></i> Bulk Assign</button>
                        <a href="{{ route('employee-schedules.manage-staff') }}" class="btn btn-outline-secondary btn-sm" title="Kelola karyawan yang tampil di jadwal"><i class="ri-user-settings-line"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-2">
            {{-- Paint Mode Toolbar --}}
            <div class="paint-toolbar mb-3 d-flex align-items-center gap-3 flex-wrap" id="paintToolbar">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold fs-12 text-muted">Pilih Shift:</span>
                    @foreach($shifts as $shift)
                        <span class="shift-option text-white" style="background: {{ $shift->color }}"
                              data-shift-id="{{ $shift->id }}" data-shift-code="{{ $shift->code }}" data-shift-color="{{ $shift->color }}"
                              data-bs-toggle="tooltip" data-bs-placement="bottom"
                              title="{{ $shift->name }}@if(!$shift->is_off && $shift->start_time) | {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}@if($shift->break_start_time && $shift->break_end_time) | Istirahat: {{ \Carbon\Carbon::parse($shift->break_start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->break_end_time)->format('H:i') }}@endif @endif"
                              onclick="selectPaintShift(this)">
                            {{ $shift->code }}
                        </span>
                    @endforeach
                    <span class="shift-option bg-light text-danger border" data-shift-id="clear" data-shift-code="✕" data-shift-color=""
                          onclick="selectPaintShift(this)" title="Hapus jadwal">
                        ✕
                    </span>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning pending-count-badge" id="pendingBadge" style="display:none;">
                        <i class="ri-edit-2-line me-1"></i><span id="pendingCount">0</span> perubahan
                    </span>
                    <button type="button" class="btn btn-success btn-sm" id="btnSaveAll" style="display:none;" onclick="saveAllChanges()">
                        <i class="ri-save-line me-1"></i> Simpan Semua
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancelAll" style="display:none;" onclick="cancelAllChanges()">
                        <i class="ri-close-line me-1"></i> Batal
                    </button>
                </div>
            </div>

            <div class="table-responsive schedule-grid">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 140px; text-align: left;">Nama</th>
                            @foreach($dates as $d)
                                <th class="day-header {{ strtolower($d->format('D')) }}" style="min-width: 36px;">
                                    {{ $d->format('j') }}<br><small>{{ $d->format('D') }}</small>
                                </th>
                            @endforeach
                            <th class="total-jam">Total<br>Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td class="emp-name">{{ $emp->name }}</td>
                                @php $empSchedules = $schedules->get($emp->id, collect()); @endphp
                                @foreach($dates as $d)
                                    @php
                                        $dateStr = $d->format('Y-m-d');
                                        $sched = $empSchedules->first(function ($item) use ($dateStr) {
                                            return $item->schedule_date->format('Y-m-d') === $dateStr;
                                        });
                                        $tooltip = '';
                                        if ($sched && $sched->shift) {
                                            $tooltip = $sched->shift->name;
                                            if ($sched->shift->start_time) {
                                                $tooltip .= ' | ' . \Carbon\Carbon::parse($sched->shift->start_time)->format('H:i') . '-' . \Carbon\Carbon::parse($sched->shift->end_time)->format('H:i');
                                            }
                                            if ($sched->shift->break_start_time && $sched->shift->break_end_time) {
                                                $tooltip .= ' | Istirahat: ' . \Carbon\Carbon::parse($sched->shift->break_start_time)->format('H:i') . '-' . \Carbon\Carbon::parse($sched->shift->break_end_time)->format('H:i');
                                            }
                                            if ($sched->location) $tooltip .= ' (' . $sched->location . ')';
                                            if ($sched->notes) $tooltip .= ' — ' . $sched->notes;
                                        }
                                    @endphp
                                    <td data-emp="{{ $emp->id }}" data-date="{{ $dateStr }}" data-current-shift="{{ $sched?->shift_id ?? '' }}">
                                        @if($sched && $sched->shift)
                                            <span class="schedule-cell text-white" style="background: {{ $sched->shift->color ?: '#6c757d' }}"
                                                  data-bs-toggle="tooltip" title="{{ $tooltip }}"
                                                  onclick="cellClick(this, {{ $emp->id }}, '{{ $dateStr }}')">
                                                {{ $sched->shift->code }}
                                            </span>
                                        @else
                                            <span class="schedule-cell text-muted" style="border: 1px dashed #ddd; min-width: 28px;"
                                                  onclick="cellClick(this, {{ $emp->id }}, '{{ $dateStr }}')">+</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="total-jam">{{ $totalHoursByEmployee[$emp->id] ?? 0 }} jam</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($dates) + 2 }}" class="text-center py-4">No employees found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Schedule Edit Modal (for detailed edit) --}}
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" data-ajax="true" action="{{ route('employee-schedules.store') }}">
                @csrf
                <input type="hidden" name="employee_id" id="sched_employee_id">
                <input type="hidden" name="schedule_date" id="sched_date">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="schedModalTitle">Set Jadwal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Shift</label>
                                <select name="shift_id" class="form-select" id="sched_shift_id">
                                    <option value="">— Select Shift / Libur —</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" style="background: {{ $shift->color }}20; color: #000;">
                                            {{ $shift->code }} — {{ $shift->name }}@if(!$shift->is_off && $shift->start_time) ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}@if($shift->break_start_time && $shift->break_end_time), Istirahat: {{ \Carbon\Carbon::parse($shift->break_start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->break_end_time)->format('H:i') }}@endif @if($shift->start_time_2 && $shift->end_time_2), {{ \Carbon\Carbon::parse($shift->start_time_2)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time_2)->format('H:i') }}@endif)@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location</label>
                                <select name="location" class="form-select" id="sched_location">
                                    <option value="">— Select —</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->name }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" id="sched_notes" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="is_overtime" class="form-check-input" value="1" id="sched_overtime">
                                    <label class="form-check-label" for="sched_overtime">Lembur</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jam Lembur</label>
                                <input type="number" name="overtime_hours" class="form-control" id="sched_overtime_hours" step="0.5" min="0" max="24">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteSchedule" onclick="deleteSchedule()" style="display:none;">
                                <i class="ri-delete-bin-line me-1"></i> Hapus
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">Simpan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Assign Modal --}}
    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" data-ajax="true" action="{{ route('employee-schedules.bulk-assign') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bulk Assign Jadwal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Karyawan</label>
                                <div class="row">
                                    @foreach($employees as $emp)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" name="employee_ids[]" class="form-check-input" value="{{ $emp->id }}" id="emp_{{ $emp->id }}">
                                                <label class="form-check-label" for="emp_{{ $emp->id }}">{{ $emp->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $month->copy()->startOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $month->copy()->endOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Pattern</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input type="radio" name="pattern" class="form-check-input" value="same" id="patternSame" checked>
                                        <label class="form-check-label" for="patternSame">Sama setiap hari</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" name="pattern" class="form-check-input" value="weekly" id="patternWeekly">
                                        <label class="form-check-label" for="patternWeekly">Pattern mingguan</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12" id="samePattern">
                                <label class="form-label">Shift</label>
                                <select name="shift_id" class="form-select">
                                    <option value="">— Select —</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->code }} — {{ $shift->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12" id="weeklyPattern" style="display:none;">
                                <div class="row g-2">
                                    @php $days = ['mon' => 'Senin', 'tue' => 'Selasa', 'wed' => 'Rabu', 'thu' => 'Kamis', 'fri' => 'Jumat', 'sat' => 'Sabtu', 'sun' => 'Minggu']; @endphp
                                    @foreach($days as $key => $label)
                                        <div class="col-md-3">
                                            <label class="form-label">{{ $label }}</label>
                                            <select name="shift_{{ $key }}" class="form-select form-select-sm">
                                                <option value="">—</option>
                                                @foreach($shifts as $shift)
                                                    <option value="{{ $shift->id }}">{{ $shift->code }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Location (optional)</label>
                                <select name="location" class="form-select">
                                    <option value="">— Select —</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->name }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        // ============ Paint Mode ============
        let selectedShift = null; // { id, code, color }
        let pendingChanges = {}; // key: "empId_date" => { employee_id, schedule_date, shift_id }

        function selectPaintShift(el) {
            document.querySelectorAll('.shift-option').forEach(s => s.classList.remove('selected'));
            el.classList.add('selected');

            const shiftId = el.dataset.shiftId;
            const shiftCode = el.dataset.shiftCode;
            const shiftColor = el.dataset.shiftColor;

            selectedShift = { id: shiftId, code: shiftCode, color: shiftColor };
            document.getElementById('paintToolbar').classList.add('active');
        }

        // Shift data for inline picker
        const shiftsData = @php echo json_encode($shifts->map(function($s) { return ['id' => $s->id, 'code' => $s->code, 'color' => $s->color, 'name' => $s->name]; })->values()); @endphp;

        function cellClick(cellSpan, empId, date) {
            // If a shift is selected in paint toolbar, use paint mode
            if (selectedShift) {
                applyShiftToCell(cellSpan, empId, date, selectedShift);
                return;
            }

            // Otherwise show inline shift picker
            closeAllPickers();
            const td = cellSpan.closest('td');
            const picker = document.createElement('div');
            picker.className = 'inline-shift-picker';
            picker.onclick = (e) => e.stopPropagation();

            shiftsData.forEach(shift => {
                const opt = document.createElement('span');
                opt.className = 'pick-opt';
                opt.style.background = shift.color;
                opt.textContent = shift.code;
                opt.title = shift.name;
                opt.onclick = () => {
                    applyShiftToCell(cellSpan, empId, date, { id: shift.id, code: shift.code, color: shift.color });
                    picker.remove();
                };
                picker.appendChild(opt);
            });

            // Clear option
            const clearOpt = document.createElement('span');
            clearOpt.className = 'pick-opt pick-clear';
            clearOpt.textContent = '✕';
            clearOpt.title = 'Hapus';
            clearOpt.onclick = () => {
                applyShiftToCell(cellSpan, empId, date, { id: 'clear', code: '✕', color: '' });
                picker.remove();
            };
            picker.appendChild(clearOpt);

            td.style.position = 'relative';
            td.appendChild(picker);

            // Close picker when clicking elsewhere
            setTimeout(() => {
                document.addEventListener('click', function closePicker(e) {
                    if (!picker.contains(e.target) && e.target !== cellSpan) {
                        picker.remove();
                        document.removeEventListener('click', closePicker);
                    }
                });
            }, 10);
        }

        function closeAllPickers() {
            document.querySelectorAll('.inline-shift-picker').forEach(p => p.remove());
        }

        function applyShiftToCell(cellSpan, empId, date, shift) {
            const td = cellSpan.closest('td');
            const key = empId + '_' + date;
            const currentShift = td.dataset.currentShift;

            if (shift.id === 'clear') {
                if (!currentShift && !pendingChanges[key]) return;
                pendingChanges[key] = { employee_id: empId, schedule_date: date, shift_id: null };
                cellSpan.className = 'schedule-cell text-muted pending-change';
                cellSpan.style.background = '';
                cellSpan.style.border = '1px dashed #dc3545';
                cellSpan.textContent = '✕';
            } else {
                if (currentShift == shift.id && pendingChanges[key]) {
                    delete pendingChanges[key];
                    cellSpan.classList.remove('pending-change');
                    updatePendingUI();
                    return;
                }
                pendingChanges[key] = { employee_id: empId, schedule_date: date, shift_id: shift.id };
                cellSpan.className = 'schedule-cell text-white pending-change';
                cellSpan.style.background = shift.color;
                cellSpan.style.border = 'none';
                cellSpan.textContent = shift.code;
            }

            updatePendingUI();
        }

        function updatePendingUI() {
            const count = Object.keys(pendingChanges).length;
            document.getElementById('pendingCount').textContent = count;
            document.getElementById('pendingBadge').style.display = count > 0 ? 'inline-flex' : 'none';
            document.getElementById('btnSaveAll').style.display = count > 0 ? 'inline-block' : 'none';
            document.getElementById('btnCancelAll').style.display = count > 0 ? 'inline-block' : 'none';
        }

        function cancelAllChanges() {
            if (!confirm('Batalkan semua perubahan?')) return;
            pendingChanges = {};
            updatePendingUI();
            location.reload();
        }

        function saveAllChanges() {
            const changes = Object.values(pendingChanges);
            if (changes.length === 0) return;

            document.getElementById('btnSaveAll').disabled = true;
            document.getElementById('btnSaveAll').innerHTML = '<i class="ri-loader-4-line me-1 spin"></i> Menyimpan...';

            fetch('{{ route("employee-schedules.batch-update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ changes: changes, month: '{{ $month->format("Y-m") }}' })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
                    document.getElementById('btnSaveAll').disabled = false;
                    document.getElementById('btnSaveAll').innerHTML = '<i class="ri-save-line me-1"></i> Simpan Semua';
                }
            })
            .catch(err => {
                alert('Error: ' + err.message);
                document.getElementById('btnSaveAll').disabled = false;
                document.getElementById('btnSaveAll').innerHTML = '<i class="ri-save-line me-1"></i> Simpan Semua';
            });
        }

        // ============ Detail Modal (manual edit) ============
        document.querySelectorAll('input[name="pattern"]').forEach(el => {
            el.addEventListener('change', function() {
                document.getElementById('samePattern').style.display = this.value === 'same' ? 'block' : 'none';
                document.getElementById('weeklyPattern').style.display = this.value === 'weekly' ? 'block' : 'none';
            });
        });

        let currentScheduleId = null;
        let deleteUrl = '';

        function editSchedule(employeeId, date) {
            document.getElementById('sched_employee_id').value = employeeId;
            document.getElementById('sched_date').value = date;
            document.getElementById('schedModalTitle').textContent = 'Set Jadwal — ' + date;

            fetch('{{ route("employee-schedules.get", [":id", ":date"]) }}'.replace(':id', employeeId).replace(':date', date))
                .then(r => r.json())
                .then(data => {
                    if (data && data.shift_id) {
                        document.getElementById('sched_shift_id').value = data.shift_id;
                        document.getElementById('sched_location').value = data.location || '';
                        document.getElementById('sched_notes').value = data.notes || '';
                        document.getElementById('sched_overtime').checked = data.is_overtime;
                        document.getElementById('sched_overtime_hours').value = data.overtime_hours || '';
                        document.getElementById('btnDeleteSchedule').style.display = 'inline-block';
                        currentScheduleId = data.id;
                        deleteUrl = '{{ route("employee-schedules.destroy", ":id") }}'.replace(':id', data.id);
                    } else {
                        document.getElementById('sched_shift_id').value = '';
                        document.getElementById('sched_location').value = '';
                        document.getElementById('sched_notes').value = '';
                        document.getElementById('sched_overtime').checked = false;
                        document.getElementById('sched_overtime_hours').value = '';
                        document.getElementById('btnDeleteSchedule').style.display = 'none';
                        currentScheduleId = null;
                        deleteUrl = '';
                    }
                })
                .catch(() => {
                    document.getElementById('sched_shift_id').value = '';
                    document.getElementById('sched_location').value = '';
                    document.getElementById('sched_overtime').checked = false;
                    document.getElementById('sched_overtime_hours').value = '';
                    document.getElementById('btnDeleteSchedule').style.display = 'none';
                });

            const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            modal.show();
        }

        function deleteSchedule() {
            if (!deleteUrl || !confirm('Delete this schedule entry?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = deleteUrl;
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            var tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltips.map(function(el) { return new bootstrap.Tooltip(el); });
        });
    </script>
@endsection
