@extends('layouts.master')
@section('title')
    Checklist Templates
@endsection
@section('css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container { width: 100% !important; }
    .select2-container .select2-selection--multiple { min-height: 38px; border: 1px solid #ced4da !important; border-radius: 0.25rem !important; }
</style>
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Housekeeping
        @endslot
        @slot('title')
            Checklist Templates
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Checklist Template</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('housekeeping.checklist-templates.store') }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="mb-4">
                        @csrf
                        <div class="input-group">
                            <input type="text" class="form-control" name="name" placeholder="Nama item checklist..." required>
                            <button type="submit" data-submit-protect="true" class="btn btn-primary">
                                <i class="ri-add-line me-1"></i> Tambah
                            </button>
                        </div>
                        @error('name')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </form>

                    @if($templates->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="ri-list-check display-4"></i>
                            <p class="mt-2 mb-0">Belum ada template. Tambahkan item di atas.</p>
                        </div>
                    @else
                        <div class="list-group">
                            @foreach($templates as $template)
                            <div class="list-group-item d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="me-3 text-muted">#{{ $template->sort_order }}</span>
                                    <div>
                                        <span>{{ $template->name }}</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-soft-info btn-sm edit-template-btn"
                                            data-id="{{ $template->id }}"
                                            data-name="{{ $template->name }}"
                                            data-order="{{ $template->sort_order }}">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <form action="{{ route('housekeeping.checklist-templates.destroy', $template) }}" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-submit-protect="true" class="btn btn-soft-danger btn-sm" onclick="return confirm('Hapus item ini?')">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Checklist template menentukan item-item yang harus dicentang oleh OB saat membersihkan kamar.</p>
                    <ul class="text-muted">
                        <li>Template berlaku per hotel/cabang</li>
                        <li><strong>Baru:</strong> Anda bisa memilih item mana yang berlaku untuk setiap kamar di menu <strong>Edit Room</strong></li>
                        <li>Perubahan template hanya berlaku untuk task baru</li>
                        <li>Task yang sudah berjalan tidak terpengaruh</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Template Modal --}}
    <div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTemplateModalLabel">Edit Checklist Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTemplateForm" method="POST" data-ajax="true" data-ajax-reload="true" data-ajax-close-modal="true" data-ajax-confirm="Are you sure?">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nama Item</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_sort_order" class="form-label">Urutan (Sort Order)</label>
                            <input type="number" class="form-control" id="edit_sort_order" name="sort_order" required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" data-submit-protect="true" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-template-btn');
        const editModal = new bootstrap.Modal(document.getElementById('editTemplateModal'));
        const editForm = document.getElementById('editTemplateForm');
        const editNameInput = document.getElementById('edit_name');
        const editOrderInput = document.getElementById('edit_sort_order');

        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const order = this.getAttribute('data-order');

                editNameInput.value = name;
                editOrderInput.value = order;
                editForm.action = '{{ route("housekeeping.checklist-templates.update", ":id") }}'.replace(':id', id);

                editModal.show();
            });
        });
    });
</script>
@endsection
