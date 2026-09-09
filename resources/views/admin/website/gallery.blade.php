@extends('layouts.master')
@section('title')
    Manage Gallery
@endsection
@section('css')
    <style>
        .gallery-item { position: relative; overflow: hidden; border-radius: 8px; border: 1px solid #dee2e6; }
        .gallery-item img { width: 100%; height: 180px; object-fit: cover; }
        .gallery-item .overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; padding: 8px; }
        .gallery-item .overlay h6 { margin: 0; font-size: 14px; }
        .gallery-item .overlay small { opacity: 0.8; }
        .gallery-item .actions { position: absolute; top: 8px; right: 8px; }
    </style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Admin @endslot
        @slot('li_2') Website Content @endslot
        @slot('title') Manage Gallery @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-2 align-middle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-image-2-line me-2 text-primary"></i>Gallery Photos</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadGalleryModal">
                        <i class="ri-upload-line me-1"></i> Upload Photos
                    </button>
                </div>
                <div class="card-body">
                    @if($photos->count() > 0)
                    <div class="row g-3" id="gallery-grid">
                        @foreach($photos as $photo)
                        <div class="col-lg-3 col-md-4 col-sm-6 gallery-col" data-id="{{ $photo->id }}">
                            <div class="gallery-item">
                                <img loading="lazy" src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->title }}">
                                <div class="actions">
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editPhotoModal{{ $photo->id }}">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('admin.website.gallery.destroy', $photo->id) }}" method="POST" data-ajax="true" class="d-inline" onsubmit="return confirm('Delete this photo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-submit-protect="true" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                                <div class="overlay">
                                    <h6>{{ $photo->title ?? 'Untitled' }}</h6>
                                    <small>Order: {{ $photo->sort_order }}</small>
                                </div>
                            </div>
                        </div>
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editPhotoModal{{ $photo->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.website.gallery.update', $photo->id) }}" method="POST" data-ajax="true">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Photo</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <img loading="lazy" src="{{ asset('storage/' . $photo->image_path) }}" class="img-fluid rounded mb-2">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Title</label>
                                                <input type="text" class="form-control" name="title" value="{{ $photo->title }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="2">{{ $photo->description }}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Sort Order</label>
                                                <input type="number" class="form-control" name="sort_order" value="{{ $photo->sort_order }}">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" data-submit-protect="true" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="ri-image-line fs-1 d-block mb-2"></i>
                        <p>No photos uploaded yet. Click "Upload Photos" to add.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadGalleryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.website.gallery.upload') }}" method="POST" data-ajax="true" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Photos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Photos</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/png,image/jpg,image/jpeg,image/webp" required>
                            <small class="text-muted">Max 5MB per image. PNG, JPG, WEBP.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title (applied to all)</label>
                            <input type="text" class="form-control" name="titles[]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (applied to all)</label>
                            <textarea class="form-control" name="descriptions[]" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/sortablejs/Sortable.min.js') }}"></script>
    <script>
        new Sortable(document.getElementById('gallery-grid'), {
            animation: 150,
            onEnd: function(evt) {
                var order = [];
                document.querySelectorAll('.gallery-col').forEach(function(el) {
                    order.push(el.dataset.id);
                });
                fetch('{{ route('admin.website.gallery.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    </script>
@endsection
