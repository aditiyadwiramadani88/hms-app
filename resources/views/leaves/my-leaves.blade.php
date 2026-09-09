@extends('layouts.master')
@section('title') Cuti Saya @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Karyawan @endslot
        @slot('title') Cuti Saya @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Balance Cards --}}
    <div class="row mb-4">
        @forelse($balances as $bal)
        <div class="col-md-3 col-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">{{ $bal->leaveType->name }}</h6>
                    <div class="d-flex justify-content-center gap-3">
                        <div>
                            <div class="fs-4 fw-bold text-primary">{{ $bal->remaining }}</div>
                            <small class="text-muted">Sisa</small>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-warning">{{ $bal->used }}</div>
                            <small class="text-muted">Terpakai</small>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-secondary">{{ $bal->allocated }}</div>
                            <small class="text-muted">Jatah</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="ri-information-line me-1"></i> Belum ada data jatah cuti. Admin perlu mengatur jatah cuti terlebih dahulu.
            </div>
        </div>
        @endforelse
    </div>

    <div class="row">
        {{-- Submit Form --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-0">
                    <h5 class="card-title mb-0"><i class="ri-add-circle-line me-2 text-primary"></i>Ajukan Cuti</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('my-leaves.store') }}" method="POST" data-ajax="true" data-ajax-reload="true">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Jenis Cuti <span class="text-danger">*</span></label>
                            <select class="form-select" name="leave_type_id" required>
                                <option value="">Pilih Jenis Cuti</option>
                                @foreach($leaveTypes as $lt)
                                    <option value="{{ $lt->id }}">{{ $lt->name }} @if(!$lt->is_paid) (Tanpa Gaji) @endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="start_date" required min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="end_date" required min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alasan</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Opsional: alasan pengajuan cuti"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100" data-submit-protect="true">
                            <i class="ri-send-plane-line me-1"></i> Ajukan Cuti
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- History --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-primary"></i>Riwayat Pengajuan</h5>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" class="d-flex gap-2">
                                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach($yearOptions as $y)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle table-borderless mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaveRequests as $req)
                                <tr>
                                    <td>{{ $loop->iteration + ($leaveRequests->currentPage() - 1) * $leaveRequests->perPage() }}</td>
                                    <td class="fw-medium">{{ $req->leaveType->name }}</td>
                                    <td>
                                        {{ $req->start_date->format('d M Y') }}
                                        @if(!$req->start_date->isSameDay($req->end_date))
                                            - {{ $req->end_date->format('d M Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $req->total_days }}</td>
                                    <td>
                                        @if($req->reason)
                                            <span title="{{ $req->reason }}">{{ Str::limit($req->reason, 30) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->status === 'pending')
                                            <span class="badge bg-warning-subtle text-warning">Pending</span>
                                        @elseif($req->status === 'approved')
                                            <span class="badge bg-success-subtle text-success">Disetujui</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Ditolak</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->rejection_note)
                                            <span class="text-danger small">{{ $req->rejection_note }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada pengajuan cuti.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $leaveRequests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');

    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
            if (endDate.value && endDate.value < this.value) {
                endDate.value = this.value;
            }
        });
    }
});
</script>
@endsection
