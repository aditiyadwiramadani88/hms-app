@extends('layouts.master')
@section('title')
    My Products
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Tenant
        @endslot
        @slot('title')
            My Products
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">My Products</h5>
                    <div class="flex-shrink-0">
                        <button type="button" id="btn-add-product" class="btn btn-primary btn-sm">
                            <i class="ri-add-line align-bottom me-1"></i> Add Product
                        </button>
                    </div>
                </div>

                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <form id="filterForm" action="{{ route('tenant.products.index') }}" method="GET">
                        <div class="row g-3">
                            <div class="col-xxl-4 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search"
                                           value="{{ request('search') }}" placeholder="Search product name...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-control" name="category" data-choices>
                                    <option value="">All Categories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-3">
                                <select class="form-control" name="is_active" data-choices>
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-xxl-2 col-sm-12">
                                <button type="submit" data-submit-protect="true" class="btn btn-primary w-100">
                                    <i class="ri-filter-3-line me-1"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body" id="products-table-container">
                    @include('tenant.products._table')
                </div>
            </div>
        </div>
    </div>

    {{-- Product Modal (Create & Edit) --}}
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="productForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="product_id" name="product_id" value="">
                    <input type="hidden" id="form_method" name="_method" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productModalLabel">Add Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Foto Produk</label>
                            <div id="photo-preview" class="mb-2" style="display: none;">
                                <img id="photo-preview-img" src="" alt="Preview" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                            <input type="file" name="photo" id="input_photo" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
                            <small class="text-muted">Max 2MB. Format: JPG, PNG, WebP</small>
                            <div class="invalid-feedback" id="error-photo"></div>
                        </div>
                        <div class="mb-3">
                            <label for="input_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="input_name" name="name" required>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>
                        <div class="mb-3">
                            <label for="input_description" class="form-label">Deskripsi Produk</label>
                            <textarea class="form-control" id="input_description" name="description" rows="2" placeholder="Keterangan/deskripsi produk (opsional)"></textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="input_category" class="form-label">Category</label>
                                    <input type="text" class="form-control" id="input_category" name="category" placeholder="e.g., Makanan, Minuman, Jasa">
                                    <div class="invalid-feedback" id="error-category"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="input_price" class="form-label">Price (Rp) *</label>
                                    <input type="number" class="form-control" id="input_price" name="price" min="0" step="1" required>
                                    <div class="invalid-feedback" id="error-price"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="input_stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="input_stock" name="stock" min="0" placeholder="Leave empty for unlimited">
                                    <small class="text-muted">Leave empty or 0 for unlimited stock</small>
                                    <div class="invalid-feedback" id="error-stock"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="input_is_active" class="form-label">Status</label>
                                    <select class="form-control" id="input_is_active" name="is_active">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-submit-product" class="btn btn-success">
                            <i class="ri-save-line align-bottom me-1"></i> <span id="btn-submit-text">Save Product</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add-ons Management Modal --}}
    <div class="modal fade" id="addonModal" tabindex="-1" aria-labelledby="addonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addonModalLabel">
                        <i class="ri-add-circle-line me-2 text-warning"></i>Add-ons: <span id="addonProductName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Add Group Form --}}
                    <div class="card border mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="ri-add-line me-1"></i>Tambah Grup Add-on</h6>
                        </div>
                        <div class="card-body">
                            <form id="addonGroupForm">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fs-12">Nama Grup *</label>
                                        <input type="text" class="form-control form-control-sm" id="addon_group_name" placeholder="e.g. Topping, Level Pedas" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fs-12">Tipe *</label>
                                        <select class="form-select form-select-sm" id="addon_group_type">
                                            <option value="single">Single (pilih 1)</option>
                                            <option value="multi">Multi (pilih banyak)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fs-12">Wajib?</label>
                                        <select class="form-select form-select-sm" id="addon_group_required">
                                            <option value="0">Opsional</option>
                                            <option value="1">Wajib</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fs-12">Max Pilihan</label>
                                        <input type="number" class="form-control form-control-sm" id="addon_group_max" placeholder="∞" min="1">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fs-12">Urutan</label>
                                        <input type="number" class="form-control form-control-sm" id="addon_group_sort" value="0" min="0">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="ri-add-line"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Groups List --}}
                    <div id="addonGroupsList">
                        <div class="text-center text-muted py-4">
                            <i class="ri-loader-4-line ri-spin fs-2 d-block mb-2"></i>
                            Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/tenant-products-ajax.js') }}"></script>
    <script>
        // === Add-on Management ===
        let currentAddonProductId = null;
        const addonModal = new bootstrap.Modal(document.getElementById('addonModal'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const ajaxHeaders = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        };

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-addon-product');
            if (!btn) return;
            currentAddonProductId = btn.dataset.id;
            document.getElementById('addonProductName').textContent = btn.dataset.name;
            addonModal.show();
            loadAddonGroups();
        });

        document.getElementById('addonGroupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                name: document.getElementById('addon_group_name').value,
                type: document.getElementById('addon_group_type').value,
                is_required: document.getElementById('addon_group_required').value === '1' ? 1 : 0,
                max_selections: document.getElementById('addon_group_max').value || null,
                sort_order: document.getElementById('addon_group_sort').value || 0,
            };

            fetch(`/admin/tenant/products/${currentAddonProductId}/addon-groups`, {
                method: 'POST',
                headers: ajaxHeaders,
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    this.reset();
                    loadAddonGroups();
                } else {
                    alert(res.message || 'Gagal menambah grup.');
                }
            })
            .catch(() => alert('Network error.'));
        });

        function loadAddonGroups() {
            fetch(`/admin/tenant/products/${currentAddonProductId}/addons`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) renderAddonGroups(res.data);
            })
            .catch(() => {
                document.getElementById('addonGroupsList').innerHTML = '<div class="text-danger text-center py-3">Gagal memuat data.</div>';
            });
        }

        function renderAddonGroups(groups) {
            const container = document.getElementById('addonGroupsList');
            if (!groups.length) {
                container.innerHTML = '<div class="text-center text-muted py-4"><i class="ri-folder-open-line fs-2 d-block mb-2"></i>Belum ada grup add-on.</div>';
                return;
            }

            let html = '';
            groups.forEach(group => {
                const typeLabel = group.type === 'single' ? 'Single' : 'Multi';
                const requiredLabel = group.is_required ? '<span class="badge bg-danger ms-1">Wajib</span>' : '<span class="badge bg-secondary ms-1">Opsional</span>';
                const globalBadge = !group.tenant_product_id ? '<span class="badge bg-info ms-1">Global</span>' : '';

                html += `
                <div class="card border mb-2" data-group-id="${group.id}">
                    <div class="card-header bg-light-subtle d-flex align-items-center py-2">
                        <div class="flex-grow-1">
                            <strong>${escapeHtml(group.name)}</strong>
                            <span class="badge bg-primary ms-1">${typeLabel}</span>
                            ${requiredLabel}${globalBadge}
                            ${group.max_selections ? '<span class="badge bg-warning text-dark ms-1">Max: ' + group.max_selections + '</span>' : ''}
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-xs btn-soft-success add-addon-item-btn" data-group-id="${group.id}" title="Tambah Item">
                                <i class="ri-add-line"></i>
                            </button>
                            <button class="btn btn-xs btn-soft-danger delete-addon-group-btn" data-group-id="${group.id}" data-name="${escapeHtml(group.name)}" title="Hapus Grup">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        ${renderAddonItems(group)}
                    </div>
                </div>`;
            });

            container.innerHTML = html;
            bindAddonEvents();
        }

        function renderAddonItems(group) {
            if (!group.active_items || !group.active_items.length) {
                return '<div class="text-muted fs-12 ps-2">Belum ada item.</div>';
            }

            let html = '<div class="table-responsive"><table class="table table-sm mb-0"><tbody>';
            group.active_items.forEach(item => {
                html += `
                <tr>
                    <td style="width: 40px;">
                        ${item.is_default ? '<i class="ri-checkbox-circle-fill text-success"></i>' : '<i class="ri-checkbox-blank-circle-line text-muted"></i>'}
                    </td>
                    <td>${escapeHtml(item.name)}</td>
                    <td class="text-end" style="width: 120px;">
                        <span class="fw-semibold text-primary">${item.price > 0 ? '+Rp ' + new Intl.NumberFormat('id-ID').format(item.price) : 'Gratis'}</span>
                    </td>
                    <td class="text-end" style="width: 80px;">
                        <button class="btn btn-xs btn-soft-primary edit-addon-item-btn" data-item-id="${item.id}" data-name="${escapeHtml(item.name)}" data-price="${item.price}" data-default="${item.is_default ? 1 : 0}" title="Edit">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="btn btn-xs btn-soft-danger delete-addon-item-btn" data-item-id="${item.id}" data-name="${escapeHtml(item.name)}" title="Hapus">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            return html;
        }

        function bindAddonEvents() {
            // Add item
            document.querySelectorAll('.add-addon-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const groupId = this.dataset.groupId;
                    const name = prompt('Nama item add-on:');
                    if (!name) return;
                    const price = prompt('Harga tambahan (Rp):', '0');
                    if (price === null) return;
                    const isDefault = confirm('Jadikan default selection?') ? 1 : 0;

                    fetch(`/admin/tenant/addon-groups/${groupId}/items`, {
                        method: 'POST',
                        headers: ajaxHeaders,
                        body: JSON.stringify({ name, price: parseFloat(price) || 0, is_default: isDefault })
                    })
                    .then(r => r.json())
                    .then(res => { if (res.success) loadAddonGroups(); else alert(res.message || 'Gagal.'); })
                    .catch(() => alert('Network error.'));
                });
            });

            // Edit item
            document.querySelectorAll('.edit-addon-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    const name = prompt('Nama item:', this.dataset.name);
                    if (!name) return;
                    const price = prompt('Harga (Rp):', this.dataset.price);
                    if (price === null) return;

                    fetch(`/admin/tenant/addon-items/${itemId}`, {
                        method: 'PUT',
                        headers: ajaxHeaders,
                        body: JSON.stringify({ name, price: parseFloat(price) || 0, is_default: parseInt(this.dataset.default) })
                    })
                    .then(r => r.json())
                    .then(res => { if (res.success) loadAddonGroups(); else alert(res.message || 'Gagal.'); })
                    .catch(() => alert('Network error.'));
                });
            });

            // Delete item
            document.querySelectorAll('.delete-addon-item-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm(`Hapus item "${this.dataset.name}"?`)) return;
                    fetch(`/admin/tenant/addon-items/${this.dataset.itemId}`, {
                        method: 'DELETE',
                        headers: ajaxHeaders,
                    })
                    .then(r => r.json())
                    .then(res => { if (res.success) loadAddonGroups(); else alert(res.message || 'Gagal.'); })
                    .catch(() => alert('Network error.'));
                });
            });

            // Delete group
            document.querySelectorAll('.delete-addon-group-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm(`Hapus grup "${this.dataset.name}" beserta semua itemnya?`)) return;
                    fetch(`/admin/tenant/addon-groups/${this.dataset.groupId}`, {
                        method: 'DELETE',
                        headers: ajaxHeaders,
                    })
                    .then(r => r.json())
                    .then(res => { if (res.success) loadAddonGroups(); else alert(res.message || 'Gagal.'); })
                    .catch(() => alert('Network error.'));
                });
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
@endpush
