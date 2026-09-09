@extends('layouts.master')
@section('title')
    Hotel Wallets & Saldo
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Finance
        @endslot
        @slot('title')
            Wallets & Bank Accounts
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate bg-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">Total Combined Balance (Kas)</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-white">Rp {{ number_format($totalBalance, 0, ',', '.') }}</h4>
                            <span class="badge bg-white text-primary">All Accounts</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-white-50 rounded fs-3">
                                <i class="ri-bank-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate bg-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">Ready to Withdraw (Hak Owner)</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-white">Rp {{ number_format($accounts->sum('available_balance'), 0, ',', '.') }}</h4>
                            <span class="badge bg-white text-success">Realized Revenue</span>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-white-50 rounded fs-3">
                                <i class="ri-hand-coin-line text-white"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12">
            <div class="card bg-info-subtle border-0">
                <div class="card-body">
                    <h6 class="text-info fw-bold text-uppercase fs-12 mb-2">Informasi Saldo</h6>
                    <ul class="text-info fs-13 mb-0 ps-3">
                        <li><strong>Total Combined Balance:</strong> Seluruh uang yang ada di kasir/rekening (termasuk titipan tamu yang belum checkout).</li>
                        <li><strong>Ready to Withdraw:</strong> Uang yang sudah sah menjadi milik hotel (dari POS atau tamu yang sudah checkout).</li>
                        <li><em>Selisih diantara keduanya adalah uang jaminan/pembayaran tamu yang masih menginap.</em></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Account List</h4>
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-soft-info btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                            <i class="ri-add-line align-middle me-1"></i> Add New Account
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($accounts as $account)
                        <div class="col-xl-4 col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-grow-1">
                                            <h5 class="fs-15 mb-1">
                                                {{ $account->name }}
                                                @if($account->isCashAccount())
                                                    <span class="badge bg-success-subtle text-success fs-10 ms-1">Kas</span>
                                                @endif
                                            </h5>
                                            <p class="text-muted mb-0">{{ $account->account_number ?? 'Internal Wallet' }}</p>
                                        </div>
                                        <div class="flex-shrink-0 dropdown">
                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-2-fill fs-18 text-muted"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item edit-account-btn" 
                                                       data-id="{{ $account->id }}" 
                                                       data-name="{{ $account->name }}"
                                                       data-number="{{ $account->account_number }}"
                                                       data-holder="{{ $account->account_holder }}">
                                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                    </a>
                                                </li>
                                                @if($account->transactions_count == 0)
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item" onclick="confirmDelete({{ $account->id }}, '{{ $account->name }}')">
                                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                                    </a>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <h3 class="mb-1">Rp {{ number_format($account->balance, 0, ',', '.') }}</h3>
                                        <p class="text-muted fs-12 mb-0">Available: <span class="text-success fw-medium">Rp {{ number_format($account->available_balance, 0, ',', '.') }}</span></p>
                                        @if($account->isCashAccount())
                                        <p class="mb-0 mt-1">
                                            <span class="badge bg-warning-subtle text-warning"><i class="ri-safe-2-line me-1"></i>Final Cash: Rp {{ number_format($account->available_balance, 0, ',', '.') }}</span>
                                        </p>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fs-12">{{ $account->transactions_count }} Total Transactions</span>
                                        <div>
                                            @if($account->available_balance > 0)
                                            <a href="{{ route('bank-accounts.show', $account->id) }}#withdraw" class="btn btn-sm btn-soft-danger px-2">
                                                <i class="ri-hand-coin-line align-middle"></i> Withdraw
                                            </a>
                                            @endif
                                            <a href="{{ route('bank-accounts.show', $account->id) }}" class="btn btn-sm btn-soft-primary px-3">
                                                Mutation <i class="ri-arrow-right-line align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <form action="{{ route('bank-accounts.index') }}" method="GET" class="d-flex align-items-center gap-2 mb-3">
                <label class="form-label mb-0">Rincian Bulan</label>
                <input type="month" name="month" class="form-control form-control-sm" style="width: 150px;" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rincian Total Gross (Kas) - {{ $month->translatedFormat('F Y') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="border-bottom">
                                    <th>Metode Bayar</th>
                                    <th class="text-center">Jumlah Transaksi</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashReceivedByMethod as $row)
                                <tr>
                                    <td class="text-capitalize">{{ $row->payment_method ?? '-' }}</td>
                                    <td class="text-center">{{ $row->transaction_count }}</td>
                                    <td class="text-end">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada transaksi</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-top fw-semibold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $cashReceivedByMethod->sum('transaction_count') }}</td>
                                    <td class="text-end">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Rincian Realized (Hak Owner) - {{ $month->translatedFormat('F Y') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="border-bottom">
                                    <th>Metode Bayar</th>
                                    <th class="text-center">Jumlah Transaksi</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($realizedByMethod as $row)
                                <tr>
                                    <td class="text-capitalize">{{ $row->payment_method ?? '-' }}</td>
                                    <td class="text-center">{{ $row->transaction_count }}</td>
                                    <td class="text-end">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada transaksi</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-top fw-semibold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $realizedByMethod->sum('transaction_count') }}</td>
                                    <td class="text-end">Rp {{ number_format($realizedRevenue, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Bank Account / Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bank-accounts.store') }}" method="POST" data-ajax="true">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. Tunai Resepsionis, BCA 123, Mandiri" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" name="account_number" placeholder="Optional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Holder</label>
                            <input type="text" class="form-control" name="account_holder" placeholder="Optional">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Initial Balance</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="initial_balance" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Account / Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editAccountForm" method="POST" data-ajax="true">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" name="account_number" id="edit_number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Holder</label>
                            <input type="text" class="form-control" name="account_holder" id="edit_holder">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade flip" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px"></lord-icon>
                    <div class="mt-4 text-center">
                        <h4>Delete Account?</h4>
                        <p class="text-muted fs-15 mb-4">Are you sure you want to delete <b id="deleteAccountName"></b>?</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal"><i class="ri-close-line me-1 align-middle"></i> Cancel</button>
                            <form id="deleteAccountForm" method="POST" data-ajax="true" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-submit-protect="true" class="btn btn-danger">Yes, Delete It</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Account Logic
            document.querySelectorAll('.edit-account-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    document.getElementById('edit_name').value = this.dataset.name;
                    document.getElementById('edit_number').value = this.dataset.number;
                    document.getElementById('edit_holder').value = this.dataset.holder;
                    document.getElementById('editAccountForm').action = '/bank-accounts/' + id;
                    
                    var myModal = new bootstrap.Modal(document.getElementById('editAccountModal'));
                    myModal.show();
                });
            });
        });

        function confirmDelete(id, name) {
            document.getElementById('deleteAccountName').textContent = name;
            document.getElementById('deleteAccountForm').action = '/bank-accounts/' + id;
            new bootstrap.Modal(document.getElementById('deleteAccountModal')).show();
        }
    </script>
@endsection
