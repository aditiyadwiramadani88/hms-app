@extends('layouts.master')
@section('title') Tukar Shift @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Employee Schedule @endslot
        @slot('title') Pengajuan Tukar Shift @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2 align-middle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ri-swap-line me-2 text-primary"></i>Riwayat Pengajuan Tukar Shift</h5>
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="text-uppercase">Requester</th>
                                    <th class="text-uppercase">Target</th>
                                    <th class="text-uppercase">Requester Shift</th>
                                    <th class="text-uppercase">Target Shift</th>
                                    <th class="text-uppercase">Date</th>
                                    <th class="text-uppercase">Reason</th>
                                    <th class="text-uppercase">Status</th>
                                    <th class="text-uppercase">Diproses Oleh</th>
                                    <th class="text-uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr>
                                        <td>{{ $req->requester->name }}</td>
                                        <td>{{ $req->target->name }}</td>
                                        <td>
                                            @if($req->requesterSchedule && $req->requesterSchedule->shift)
                                                <span class="badge" style="background: {{ $req->requesterSchedule->shift->color }}">
                                                    {{ $req->requesterSchedule->shift->code }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($req->targetSchedule && $req->targetSchedule->shift)
                                                <span class="badge" style="background: {{ $req->targetSchedule->shift->color }}">
                                                    {{ $req->targetSchedule->shift->code }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $req->requesterSchedule?->schedule_date?->format('d M Y') }}</td>
                                        <td><small>{{ Str::limit($req->reason, 50) }}</small></td>
                                        <td>
                                            @if($req->status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($req->status === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($req->status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($req->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($req->approver)
                                                <div>{{ $req->approver->name }}</div>
                                                <small class="text-muted">{{ $req->approved_at?->format('d M Y, H:i') }}</small>
                                                @if($req->status === 'rejected' && $req->notes)
                                                    <div class="text-danger fs-12">"{{ $req->notes }}"</div>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($req->status === 'pending')
                                                <div class="d-flex gap-1">
                                                    <form method="POST" data-ajax="true" action="{{ route('shift-swaps.approve', $req) }}">
                                                        @csrf
                                                        <button class="btn btn-soft-success btn-sm"><i class="ri-check-line"></i></button>
                                                    </form>
                                                    <button class="btn btn-soft-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center py-4">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $requests->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modals --}}
    @foreach($requests as $req)
        @if($req->status === 'pending')
            <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" data-ajax="true" action="{{ route('shift-swaps.reject', $req) }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">Reject Swap Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <p>Reject swap request from <strong>{{ $req->requester->name }}</strong>?</p>
                                <div class="mb-3">
                                    <label class="form-label">Notes (optional)</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" data-submit-protect="true" class="btn btn-danger">Reject</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection
@section('script')
@endsection
