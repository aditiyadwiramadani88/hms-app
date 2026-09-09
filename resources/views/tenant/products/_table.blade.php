<div class="table-responsive table-card">
    <table class="table table-nowrap align-middle mb-0">
        <thead class="table-light text-muted">
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th class="text-end">Price</th>
                <th class="text-center">Stock</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if($product->photo)
                            <img src="{{ asset('storage/' . $product->photo) }}" alt="{{ $product->name }}" class="rounded" style="width: 36px; height: 36px; object-fit: cover;">
                        @else
                            <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="ri-image-line text-muted"></i>
                            </div>
                        @endif
                        <span class="fw-medium">{{ $product->name }}</span>
                    </div>
                </td>
                <td>{{ $product->category ?? '-' }}</td>
                <td class="text-end fw-semibold">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($product->stock === null)
                        <span class="badge bg-info">Unlimited</span>
                    @else
                        <span class="{{ $product->stock <= 5 ? 'text-danger fw-semibold' : '' }}">{{ $product->stock }}</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <button type="button" class="btn btn-soft-warning btn-sm btn-addon-product" data-id="{{ $product->id }}" data-name="{{ $product->name }}" title="Manage Add-ons">
                        <i class="ri-add-circle-line"></i>
                    </button>
                    <button type="button" class="btn btn-soft-primary btn-sm btn-edit-product" data-id="{{ $product->id }}">
                        <i class="ri-pencil-line"></i>
                    </button>
                    <button type="button" class="btn btn-soft-danger btn-sm btn-delete-product" data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No products found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3" id="pagination-links">
    {{ $products->withQueryString()->links() }}
</div>
