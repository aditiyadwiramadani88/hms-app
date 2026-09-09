@extends('layouts.master')
@section('title')
    Edit About Page
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/quill/quill.core.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/quill/quill.snow.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Admin @endslot
        @slot('li_2') Website Content @endslot
        @slot('title') Edit About Page @endslot
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
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0"><i class="ri-file-edit-line me-2 text-primary"></i>Edit About Page</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.website.about.update') }}" method="POST" data-ajax="true">
                        @csrf
                        <div class="mb-3">
                            <label for="title" class="form-label">Page Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $page->title ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <input type="text" class="form-control" id="meta_description" name="meta_description" value="{{ old('meta_description', $page->meta_description ?? '') }}" maxlength="500">
                            <small class="text-muted">For SEO, max 500 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <div id="editor">{{ old('content', $page->content ?? '') }}</div>
                            <input type="hidden" name="content" id="content">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Published</label>
                        </div>
                        <div class="text-end">
                            <button type="submit" data-submit-protect="true" class="btn btn-primary"><i class="ri-save-line align-bottom me-1"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/quill/quill.min.js') }}"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });
    </script>
@endsection
