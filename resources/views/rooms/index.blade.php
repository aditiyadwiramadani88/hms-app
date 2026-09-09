@if(request()->ajax())
    @include('rooms._table-content')
    @php return; @endphp
@endif
@extends('layouts.master')
@section('title')
    Rooms Management
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .ajax-loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); display: none; align-items: center; justify-content: center; z-index: 10; border-radius: 4px; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Rooms
        @endslot
        @slot('title')
            Rooms Management
        @endslot
    @endcomponent

    {{-- Flash Messages --}}
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
            <div class="card" id="roomList">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0"><i class="ri-hotel-bed-fill me-2 text-primary"></i>Rooms List</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('rooms.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Add New Room
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form action="{{ route('rooms.index') }}" method="GET" id="roomFilterForm">
                        <div class="row g-3">
                            <div class="col-xxl-3 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search" placeholder="Cari nomor kamar..." value="{{ request('search') }}">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    @foreach($statuses as $status)
                                    <option value="{{ $status->name }}" {{ request('status') === $status->name ? 'selected' : '' }}>{{ trim($status->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-select" name="floor">
                                    <option value="">All Floors</option>
                                    @foreach($floors as $fl)
                                        <option value="{{ $fl }}" {{ request('floor') == $fl ? 'selected' : '' }}>Floor {{ $fl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <select class="form-select" name="room_type">
                                    <option value="">All Room Types</option>
                                    @foreach($roomTypes ?? [] as $type)
                                        <option value="{{ $type->id }}" {{ request('room_type') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-3 col-sm-8">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="ri-filter-3-line me-1 align-bottom"></i> Filter
                                    </button>
                                    <a href="{{ route('rooms.index') }}" class="btn btn-soft-secondary w-100" id="roomResetBtn">
                                        <i class="ri-refresh-line me-1 align-bottom"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Rooms Table --}}
                <div class="card-body">
                    <div id="rooms-table-container" style="position: relative;">
                        @include('rooms._table-content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal fade flip" id="deleteRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                        colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px">
                    </lord-icon>
                    <div class="mt-4 text-center">
                        <h4>Delete Room <span id="deleteRoomNumber"></span>?</h4>
                        <p class="text-muted fs-15 mb-4">Deleting this room will remove all associated data. This action cannot be undone.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal">
                                <i class="ri-close-line me-1 align-middle"></i> Cancel
                            </button>
                            <form id="deleteRoomForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-submit-protect="true" class="btn btn-danger">Yes, Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Room Modal (XL) --}}
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-pencil-line me-2"></i>Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editRoomModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- History Room Modal --}}
    <div class="modal fade" id="historyRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-history-line me-2"></i>Booking History — Room <span id="historyRoomNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="historyRoomModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    function confirmDelete(roomId, roomNumber) {
        document.getElementById('deleteRoomNumber').textContent = roomNumber;
        document.getElementById('deleteRoomForm').action = '{{ route("rooms.destroy", ":id") }}'.replace(':id', roomId);
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteRoomModal'));
        deleteModal.show();
    }

    // === AJAX Table Handler ===
    var roomsAjax = (function() {
        var container = document.getElementById('rooms-table-container');
        var form = document.getElementById('roomFilterForm');
        var currentRequest = null;

        function fetchContent(url) {
            if (currentRequest) currentRequest.abort();
            showLoading();
            currentRequest = new AbortController();
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: currentRequest.signal })
                .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
                .then(function(html) {
                    if (html.indexOf('<!DOCTYPE') > -1 || html.indexOf('<html') > -1) { window.location.href = url; return; }
                    container.innerHTML = html;
                    hideLoading();
                    reinitTooltips();
                    history.pushState(null, '', url);
                    currentRequest = null;
                })
                .catch(function(err) {
                    if (err.name === 'AbortError') return;
                    hideLoading();
                    currentRequest = null;
                    window.location.href = url;
                });
        }

        function showLoading() {
            var overlay = container.querySelector('.ajax-loading-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'ajax-loading-overlay';
                overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
                container.appendChild(overlay);
            }
            overlay.style.display = 'flex';
        }

        function hideLoading() {
            var overlay = container.querySelector('.ajax-loading-overlay');
            if (overlay) overlay.style.display = 'none';
        }

        function reinitTooltips() {
            container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                var existing = bootstrap.Tooltip.getInstance(el);
                if (existing) existing.dispose();
                new bootstrap.Tooltip(el);
            });
        }

        function reload() { fetchContent(window.location.href); }

        // Filter form submit
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var url = new URL(form.action);
                url.search = '';
                new FormData(form).forEach(function(v, k) { if (v) url.searchParams.set(k, v); });
                url.searchParams.delete('page');
                fetchContent(url.toString());
            });
        }

        // Reset button
        var resetBtn = document.getElementById('roomResetBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.querySelectorAll('input[type="text"]').forEach(function(i) { i.value = ''; });
                form.querySelectorAll('select').forEach(function(s) { s.value = ''; });
                fetchContent(resetBtn.href);
            });
        }

        // Pagination (event delegation)
        container.addEventListener('click', function(e) {
            var link = e.target.closest('.pagination a');
            if (link) { e.preventDefault(); fetchContent(link.href); container.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });

        // Popstate
        window.addEventListener('popstate', function() { fetchContent(window.location.href); });

        return { reload: reload, fetch: fetchContent };
    })();

    // === Edit Room Modal ===
    document.getElementById('rooms-table-container').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-edit-room');
        if (!btn) return;
        e.preventDefault();
        var roomId = btn.dataset.roomId;
        var modalBody = document.getElementById('editRoomModalBody');
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        var modal = new bootstrap.Modal(document.getElementById('editRoomModal'));
        modal.show();

        fetch('/admin/rooms/' + roomId + '/edit', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                // Find the edit form specifically (has action containing /rooms/ and method PUT)
                var formEl = doc.querySelector('form[action*="/rooms/"]');
                if (!formEl) {
                    // Fallback: find form with _method PUT
                    var allForms = doc.querySelectorAll('form');
                    for (var i = 0; i < allForms.length; i++) {
                        var methodInput = allForms[i].querySelector('input[name="_method"][value="PUT"]');
                        if (methodInput) { formEl = allForms[i]; break; }
                    }
                }
                if (formEl) {
                    // Also grab any script tags related to the form (for kos pricing tiers JS)
                    var scripts = doc.querySelectorAll('script:not([src])');
                    var scriptContent = '';
                    scripts.forEach(function(s) {
                        if (s.textContent.indexOf('tier') > -1 || s.textContent.indexOf('Tier') > -1 || s.textContent.indexOf('kos') > -1 || s.textContent.indexOf('checklist') > -1 || s.textContent.indexOf('Checklist') > -1 || s.textContent.indexOf('selectAll') > -1) {
                            scriptContent += s.textContent + '\n';
                        }
                    });

                    modalBody.innerHTML = formEl.outerHTML;

                    // Execute inline scripts
                    if (scriptContent) {
                        try { new Function(scriptContent)(); } catch(e) { console.warn('Edit modal script error:', e); }
                    }

                    // Manually bind checklist select/unselect buttons
                    var selectAllBtn = modalBody.querySelector('#selectAllChecklist');
                    if (selectAllBtn) {
                        selectAllBtn.addEventListener('click', function() {
                            modalBody.querySelectorAll('.checklist-checkbox').forEach(function(cb) { cb.checked = true; });
                        });
                    }
                    var unselectAllBtn = modalBody.querySelector('#unselectAllChecklist');
                    if (unselectAllBtn) {
                        unselectAllBtn.addEventListener('click', function() {
                            modalBody.querySelectorAll('.checklist-checkbox').forEach(function(cb) { cb.checked = false; });
                        });
                    }

                    // Bind kos toggle
                    var kosToggle = modalBody.querySelector('#is_kos');
                    var kosSection = modalBody.querySelector('#kos_section');
                    if (kosToggle && kosSection) {
                        kosToggle.addEventListener('change', function() {
                            kosSection.style.display = this.checked ? 'block' : 'none';
                        });
                    }

                    // Rebind the form for AJAX submission
                    var newForm = modalBody.querySelector('form');
                    if (newForm) {
                        newForm.addEventListener('submit', function(ev) {
                            ev.preventDefault();
                            var submitBtn = newForm.querySelector('button[type="submit"]');
                            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin me-1"></i> Saving...'; }
                            var formData = new FormData(newForm);
                            fetch(newForm.action, {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                                body: formData
                            }).then(function(r) { return r.json(); })
                            .then(function(res) {
                                if (res.success) {
                                    bootstrap.Modal.getInstance(document.getElementById('editRoomModal')).hide();
                                    roomsAjax.reload();
                                    if (typeof Toastify !== 'undefined') {
                                        Toastify({ text: res.message || 'Room updated!', duration: 3000, gravity: 'top', position: 'right', style: { background: 'linear-gradient(to right, #0ab39c, #405189)' } }).showToast();
                                    }
                                } else {
                                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="ri-save-line me-1"></i> Update Room'; }
                                    alert(res.message || 'Update failed');
                                }
                            }).catch(function() {
                                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="ri-save-line me-1"></i> Update Room'; }
                                alert('Network error');
                            });
                        });
                    }
                } else {
                    modalBody.innerHTML = '<div class="text-center py-4 text-danger">Form edit tidak ditemukan.</div>';
                }
            })
            .catch(function() {
                modalBody.innerHTML = '<div class="text-center py-4 text-danger">Gagal memuat form edit.</div>';
            });
    });

    // === History Room Modal ===
    document.getElementById('rooms-table-container').addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-history-room');
        if (!btn) return;
        e.preventDefault();
        var roomId = btn.dataset.roomId;
        var roomNumber = btn.dataset.roomNumber;
        document.getElementById('historyRoomNumber').textContent = roomNumber;
        var modalBody = document.getElementById('historyRoomModalBody');
        modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        var modal = new bootstrap.Modal(document.getElementById('historyRoomModal'));
        modal.show();

        fetch('/admin/bookings?room_id=' + roomId + '&tab=all', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                if (html.indexOf('<!DOCTYPE') > -1 || html.indexOf('<html') > -1) {
                    // Full page returned — extract table
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var table = doc.querySelector('.table-responsive');
                    if (table) {
                        modalBody.innerHTML = table.outerHTML;
                    } else {
                        modalBody.innerHTML = '<p class="text-muted text-center py-3">Tidak ada data booking ditemukan.</p>';
                    }
                } else {
                    // Partial returned (AJAX response from bookings index)
                    modalBody.innerHTML = html;
                }
            })
            .catch(function() {
                modalBody.innerHTML = '<div class="text-center py-4 text-danger">Gagal memuat history.</div>';
            });
    });
    </script>
@endsection
