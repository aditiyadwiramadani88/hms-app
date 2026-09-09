@extends('layouts.master')
@section('title') Pengajuan Cuti @endsection
@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') HR & Payroll @endslot
        @slot('title') Pengajuan Cuti @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-file-list-3-line me-2 text-primary"></i>Pengajuan Cuti</h5>
                        </div>
                        <div class="col-sm-auto">
                            <form method="GET" class="d-flex gap-2">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Semua Status</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                </select>
                                <select name="leave_type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">Semua Jenis</option>
                                    @foreach($leaveTypes as $lt)
                                        <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
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
                                    <th>Karyawan</th>
                                    <th>Jenis Cuti</th>
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Diproses Oleh</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                <tr>
                                    <td>{{ $loop->iteration + ($requests->currentPage() - 1) * $requests->perPage() }}</td>
                                    <td class="fw-medium">{{ $req->employee->name }}</td>
                                    <td>{{ $req->leaveType->name }}</td>
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
                                        @if($req->approver)
                                            {{ $req->approver->name }}
                                            <br><small class="text-muted">{{ $req->approved_at?->format('d M Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->isPending())
                                            <div class="d-flex gap-1">
                                                <button class="btn btn-sm btn-success" onclick="approveLeave({{ $req->id }})">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="showRejectModal({{ $req->id }})">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Tidak ada pengajuan cuti.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rejectForm" method="POST" data-ajax="true" data-ajax-reload="true">
                    @csrf @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Tolak Pengajuan Cuti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Alasan Penolakan</label>
                            <textarea class="form-control" name="rejection_note" rows="3" placeholder="Opsional: berikan alasan penolakan"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" data-submit-protect="true">Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
function approveLeave(id) {
    if (!confirm('Setujui pengajuan cuti ini?')) return;

    fetch('{{ route("admin.leave-requests.index") }}/' + id + '/approve', {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Gagal memproses.');
        }
    })
    .catch(() => alert('Terjadi kesalahan.'));
}

function showRejectModal(id) {
    document.getElementById('rejectForm').action = '{{ route("admin.leave-requests.index") }}/' + id + '/reject';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endsection
