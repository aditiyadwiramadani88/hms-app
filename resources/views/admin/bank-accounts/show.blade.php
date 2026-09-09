@extends('layouts.master')
@section('title')
    Mutation: {{ $bankAccount->name }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Finance
        @endslot
        @slot('title')
            Account Mutation
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-3">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h5 class="fs-14 mb-1 text-muted text-uppercase">Current Balance (Total Kas)</h5>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="ri-wallet-3-line fs-18 text-primary"></i>
                        </div>
                    </div>
                    <h2 class="mb-0">Rp {{ number_format($bankAccount->balance, 0, ',', '.') }}</h2>
                </div>
            </div>

            <div class="card card-animate bg-success-subtle border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h5 class="fs-14 mb-1 text-success text-uppercase fw-bold">Ready to Withdraw (Hak Owner)</h5>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="ri-hand-coin-line fs-18 text-success"></i>
                        </div>
                    </div>
                    <h2 class="mb-0 text-success">Rp {{ number_format($bankAccount->available_balance, 0, ',', '.') }}</h2>
                    <p class="text-muted fs-11 mt-2 mb-0">Uang yang sudah sah menjadi milik hotel.</p>
                    @if($bankAccount->isCashAccount())
                        <a href="#finalCashSection" class="btn btn-sm btn-outline-success mt-2 w-100">
                            <i class="ri-history-line me-1"></i> Lihat Riwayat Final Cash
                        </a>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="ri-arrow-down-circle-line align-middle me-1"></i> Record Income
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="ri-arrow-up-circle-line align-middle me-1"></i> Record Expense
                        </button>
                        @if($bankAccount->isCashAccount() && auth()->user()->hasRole(['Admin', 'Super Admin']))
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#adjustModal">
                                <i class="ri-swap-line align-middle me-1"></i> Pindah ke Saldo Final
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Info</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 vstack gap-3">
                        <li>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="fs-13 mb-1">Account Name</h6>
                                    <p class="text-muted mb-0">{{ $bankAccount->name }}</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="fs-13 mb-1">Account Number</h6>
                                    <p class="text-muted mb-0">{{ $bankAccount->account_number ?? '-' }}</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xl-9">
            <div class="card">
                @php($activeTab = request('tab') === 'final_cash' ? 'final_cash' : 'mutasi')
                <div class="card-header border-0">
                    <ul class="nav nav-tabs-custom nav-success" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'mutasi' ? 'active' : '' }}" data-bs-toggle="tab" href="#mutationTab" role="tab">
                                <i class="ri-exchange-line me-1"></i> Mutasi Umum
                            </a>
                        </li>
                        @if($bankAccount->isCashAccount())
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'final_cash' ? 'active' : '' }}" data-bs-toggle="tab" href="#finalCashTab" role="tab">
                                <i class="ri-safe-2-line me-1"></i> Final Cash
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                <div class="tab-content">
                    {{-- Tab 1: Mutasi Umum --}}
                    <div class="tab-pane {{ $activeTab === 'mutasi' ? 'active' : '' }}" id="mutationTab" role="tabpanel">
                        <div class="card-body border-bottom">
                            <form action="{{ route('bank-accounts.show', $bankAccount->id) }}" method="GET">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" name="search" value="{{ request('search') }}" placeholder="Cari deskripsi, booking...">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" placeholder="Dari tanggal">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" placeholder="Sampai tanggal">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select" name="flow" onchange="this.form.submit()">
                                            <option value="">Semua Kategori</option>
                                            <option value="income" {{ request('flow') === 'income' ? 'selected' : '' }}>Record Income</option>
                                            <option value="expense" {{ request('flow') === 'expense' ? 'selected' : '' }}>Record Expense</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex gap-2">
                                            <button type="submit" data-submit-protect="true" class="btn btn-primary" title="Filter"><i class="ri-filter-3-line"></i> Filter</button>
                                            <a href="{{ route('bank-accounts.show', $bankAccount->id) }}" class="btn btn-soft-secondary" title="Reset"><i class="ri-refresh-line"></i></a>

                                            <div class="ms-auto d-flex gap-2">
                                                <a href="{{ route('bank-accounts.export.pdf', array_merge(['bankAccount' => $bankAccount->id], request()->all())) }}" class="btn btn-danger" title="Export PDF">
                                                    <i class="ri-file-pdf-line"></i> PDF
                                                </a>
                                                <a href="{{ route('bank-accounts.export.excel', array_merge(['bankAccount' => $bankAccount->id], request()->all())) }}" class="btn btn-success" title="Export Excel">
                                                    <i class="ri-file-excel-line"></i> Excel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Ref / Booking</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $trx)
                                        <tr>
                                            <td>{{ $trx->created_at->format('d M Y, H:i') }}</td>
                                            <td>
                                                @if($trx->booking_id)
                                                    <a href="{{ route('bookings.show', $trx->booking_id) }}" class="fw-medium">#BOOK-{{ $trx->booking_id }}</a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $trx->category->name ?? 'Uncategorized' }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-medium text-dark">{{ $trx->description }}</div>
                                                @if($trx->booking)
                                                    <div class="text-muted fs-12">
                                                        <i class="ri-hotel-bed-line me-1"></i> Room {{ $trx->booking->room?->room_number ?? 'N/A' }} | 
                                                        <i class="ri-user-line me-1"></i> {{ $trx->booking->guest->name ?? 'N/A' }}
                                                    </div>
                                                @endif
                                                <small class="text-muted">By: {{ $trx->user->name ?? 'System' }}</small>
                                            </td>
                                            <td class="text-end fw-bold {{ $trx->type === 'payment' ? 'text-success' : 'text-danger' }}">
                                                {{ $trx->type === 'payment' ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success text-uppercase">Success</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No mutation records found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $transactions->links() }}
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Final Cash --}}
                    @if($bankAccount->isCashAccount() && $finalCashMutations)
                    <div class="tab-pane {{ $activeTab === 'final_cash' ? 'active' : '' }}" id="finalCashTab" role="tabpanel">
                        <div class="card-body border-bottom">
                            <form method="GET" action="{{ route('bank-accounts.show', $bankAccount->id) }}">
                                <input type="hidden" name="tab" value="final_cash">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" name="fc_search" value="{{ request('fc_search') }}" placeholder="Cari deskripsi...">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" class="form-control" name="fc_from" value="{{ request('fc_from') }}" placeholder="Dari tanggal">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" class="form-control" name="fc_to" value="{{ request('fc_to') }}" placeholder="Sampai tanggal">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex gap-2">
                                            <button type="submit" data-submit-protect="true" class="btn btn-primary" title="Filter"><i class="ri-filter-3-line"></i> Filter</button>
                                            <a href="{{ route('bank-accounts.show', $bankAccount->id) }}" class="btn btn-soft-secondary" title="Reset"><i class="ri-refresh-line"></i></a>

                                            <div class="ms-auto d-flex gap-2">
                                                <a href="{{ route('bank-accounts.export.final-cash.pdf', array_merge(['bankAccount' => $bankAccount->id], request()->only(['fc_search', 'fc_from', 'fc_to']))) }}" class="btn btn-danger" title="Export PDF">
                                                    <i class="ri-file-pdf-line"></i> PDF
                                                </a>
                                                <a href="{{ route('bank-accounts.export.final-cash.excel', array_merge(['bankAccount' => $bankAccount->id], request()->only(['fc_search', 'fc_from', 'fc_to']))) }}" class="btn btn-success" title="Export Excel">
                                                    <i class="ri-file-excel-line"></i> Excel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Reference</th>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Balance After</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($finalCashMutations as $fcm)
                                        <tr>
                                            <td>{{ $fcm->created_at->format('d M Y, H:i') }}</td>
                                            <td>
                                                @if($fcm->type === 'in')
                                                    <span class="badge bg-success-subtle text-success">Masuk</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Keluar</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($fcm->reference_type === 'booking_checkout')
                                                    <a href="{{ route('bookings.show', $fcm->reference_id) }}" class="fw-medium">#BOOK-{{ $fcm->reference_id }}</a>
                                                @elseif($fcm->reference_type === 'owner_withdrawal')
                                                    <span class="badge bg-warning-subtle text-warning">Withdrawal</span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info">Pindah Saldo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-medium text-dark">{{ $fcm->description }}</div>
                                                <small class="text-muted">By: {{ $fcm->user->name ?? 'System' }}</small>
                                            </td>
                                            <td class="text-end fw-bold {{ $fcm->type === 'in' ? 'text-success' : 'text-danger' }}">
                                                {{ $fcm->type === 'in' ? '+' : '-' }} Rp {{ number_format($fcm->amount, 0, ',', '.') }}
                                            </td>
                                            <td class="text-end">
                                                Rp {{ number_format($fcm->balance_after, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat Final Cash.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $finalCashMutations->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Income Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success-subtle">
                    <h5 class="modal-title text-success">Record Custom Income</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bank-accounts.deposit', $bankAccount->id) }}" method="POST" data-ajax="true">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Date & Time</label>
                            <input type="datetime-local" class="form-control" name="transaction_date" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                @foreach($incomeCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="amount" placeholder="0" min="1" required>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Detail pemasukan..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-success">Save Income</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Expense Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger-subtle">
                    <h5 class="modal-title text-danger">Record Expense / Withdraw</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bank-accounts.withdraw', $bankAccount->id) }}" method="POST" data-ajax="true">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Date & Time</label>
                            <input type="datetime-local" class="form-control" name="transaction_date" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                @foreach($expenseCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount to Withdraw</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="amount" placeholder="0" min="1" max="{{ $bankAccount->available_balance }}" required>
                            </div>
                            <small class="text-muted">Max Ready to Withdraw: Rp {{ number_format($bankAccount->available_balance, 0, ',', '.') }}</small>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Detail pengeluaran..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-danger">Confirm Withdrawal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($bankAccount->isCashAccount() && auth()->user()->hasRole(['Admin', 'Super Admin']))
    <!-- Pindah Saldo Final Modal -->
    <div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title text-warning"><i class="ri-swap-line me-1"></i> Pindah ke Saldo Final</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('bank-accounts.adjust-final-cash', $bankAccount->id) }}" method="POST" data-ajax="true">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info fs-12">
                            <i class="ri-information-line me-1"></i> Pindahkan saldo dari kas mixing ke saldo final (hak owner), atau sebaliknya. Total kas tidak berubah.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Arah Pindah</label>
                            <select class="form-select" name="type" required>
                                <option value="in">Mixing → Final (Tambah Saldo Final)</option>
                                <option value="out">Final → Mixing (Kurangi Saldo Final)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nominal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="amount" placeholder="0" min="1" step="1" required>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Alasan</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Contoh: Penyesuaian saldo awal, koreksi data..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-warning">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection

@section('script')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#withdraw') {
                var withdrawModalEl = document.getElementById('withdrawModal');
                if (withdrawModalEl) {
                    new bootstrap.Modal(withdrawModalEl).show();
                }
            }
            @if(session('success'))
                Toastify({ text: "{{ session('success') }}", duration: 5000, gravity: "top", position: "right", style: { background: "linear-gradient(to right, #0ab39c, #405189)" } }).showToast();
            @endif
            @if(session('error'))
                Toastify({ text: "{{ session('error') }}", duration: 5000, gravity: "top", position: "right", style: { background: "linear-gradient(to right, #f06548, #f7b84b)" } }).showToast();
            @endif
        });
    </script>
@endsection
