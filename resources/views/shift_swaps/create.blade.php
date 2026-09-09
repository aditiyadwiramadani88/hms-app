@extends('layouts.master')
@section('title') Ajukan Tukar Shift @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('my-schedule.index') }}">My Schedule</a> @endslot
        @slot('title') Ajukan Tukar Shift @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ri-swap-line me-1 text-primary"></i>Form Pengajuan Tukar Shift</h5>
                </div>
                <div class="card-body">
                    <form method="POST" data-ajax="true" action="{{ route('shift-swaps.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Jadwal Saya yang Mau Ditukar</label>
                            <select name="requester_schedule_id" class="form-select" required>
                                <option value="">— Pilih Jadwal —</option>
                                @foreach($mySchedules as $sched)
                                    <option value="{{ $sched->id }}" data-date="{{ $sched->schedule_date->format('Y-m-d') }}">
                                        {{ $sched->schedule_date->format('l, d M Y') }} — {{ $sched->shift?->name ?? 'No Shift' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tukar Dengan Karyawan</label>
                            <select name="target_employee_id" class="form-select" id="targetEmployee">
                                <option value="">— Pilih Karyawan —</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4" id="targetScheduleSection" style="display:none;">
                            <label class="form-label fw-semibold">Pilih Tanggal Jadwal Target</label>
                            <select name="target_schedule_id" class="form-select" id="targetSchedule" required disabled>
                                <option value="">— Pilih Jadwal Target —</option>
                            </select>
                            <div id="targetScheduleInfo" class="mt-2 text-muted small"></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Alasan</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Perlu antar anak sekolah pagi..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="{{ route('my-schedule.index') }}" class="btn btn-light">Batal</a>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary"><i class="ri-send-plane-line align-bottom me-1"></i> Kirim Pengajuan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.getElementById('targetEmployee').addEventListener('change', function() {
            const section = document.getElementById('targetScheduleSection');
            const select = document.getElementById('targetSchedule');
            const info = document.getElementById('targetScheduleInfo');
            select.innerHTML = '<option value="">Loading...</option>';
            select.disabled = true;
            section.style.display = 'none';

            if (!this.value) return;

            const requesterSelect = document.querySelector('select[name="requester_schedule_id"]');
            const selectedOption = requesterSelect.options[requesterSelect.selectedIndex];
            const requesterDate = selectedOption?.getAttribute('data-date');
            if (!requesterDate) {
                alert('Please select your schedule first.');
                this.value = '';
                return;
            }

            const url = "{{ route('shift-swaps.employee-schedule', ['employeeId' => '_emp_', 'date' => '_date_']) }}"
                .replace('_emp_', this.value)
                .replace('_date_', requesterDate);

            fetch(url)
                .then(r => r.json())
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
                    info.innerHTML = '<span class="text-danger">No schedule found for this date.</span>';
                    section.style.display = 'block';
                });
        });

        document.querySelector('select[name="requester_schedule_id"]').addEventListener('change', function() {
            document.getElementById('targetScheduleSection').style.display = 'none';
            document.getElementById('targetEmployee').value = '';
        });
    </script>
@endsection
