@extends('layouts.master')
@section('title', 'Kelola Template Checklist')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Kelola Template Checklist Serah Terima</h4>
            <div class="page-title-right">
                <a href="{{ route('shift-handovers.index') }}" class="btn btn-primary btn-sm">
                    <i class="ri-arrow-left-line"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Daftar Item Checklist</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
            <i class="ri-add-line"></i> Tambah Item
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">Urutan</th>
                        <th>Nama Item</th>
                        <th>Deskripsi</th>
                        <th style="width: 80px;">Wajib</th>
                        <th style="width: 80px;">Aktif</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="templateTableBody">
                    @forelse($templates as $template)
                    <tr data-id="{{ $template->id }}">
                        <td>
                            <input type="number" class="form-control form-control-sm sort-order-input" value="{{ $template->sort_order }}" style="width: 60px;" min="0" max="999">
                        </td>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->description ?? '-' }}</td>
                        <td class="text-center">
                            @if($template->is_required)
                                <i class="ri-check-line text-success fs-5"></i>
                            @else
                                <i class="ri-close-line text-danger fs-5"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($template->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-template-btn"
                                data-id="{{ $template->id }}"
                                data-name="{{ $template->name }}"
                                data-description="{{ $template->description }}"
                                data-is_required="{{ $template->is_required ? '1' : '0' }}"
                                data-sort_order="{{ $template->sort_order }}">
                                <i class="ri-edit-line"></i>
                            </button>
                            @if($template->is_active)
                            <button class="btn btn-sm btn-danger deactivate-template-btn"
                                data-id="{{ $template->id }}"
                                data-name="{{ $template->name }}">
                                <i class="ri-close-circle-line"></i>
                            </button>
                            @else
                            <button class="btn btn-sm btn-success activate-template-btn"
                                data-id="{{ $template->id }}">
                                <i class="ri-checkbox-circle-line"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="ri-inbox-line fs-2"></i>
                            <p class="mt-2">Belum ada template item checklist</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-secondary btn-sm" id="reorderBtn">
            <i class="ri-save-line"></i> Simpan Urutan
        </button>
    </div>
</div>

<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('handover-templates.store') }}" id="templateForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="templateModalTitle">Tambah Item Checklist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="template_id" id="templateId">
                    <div class="mb-3">
                        <label class="form-label">Nama Item <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="templateName" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" id="templateDescription" maxlength="255" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan</label>
                        <input type="number" class="form-control" name="sort_order" id="templateSortOrder" value="0" min="0" max="999">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_required" id="templateIsRequired" value="1" checked>
                        <label class="form-check-label" for="templateIsRequired">Wajib dicentang</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form id="deactivateForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="activateForm" method="POST" action="{{ route('handover-templates.reorder') }}" style="display:none;">
    @csrf
    <input type="hidden" name="items" id="reorderItems">
</form>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.edit-template-btn').on('click', function() {
        var btn = $(this);
        $('#templateModalTitle').text('Edit Item Checklist');
        $('#templateId').val(btn.data('id'));
        $('#templateName').val(btn.data('name'));
        $('#templateDescription').val(btn.data('description'));
        $('#templateSortOrder').val(btn.data('sort_order'));
        $('#templateIsRequired').prop('checked', btn.data('is_required') == 1);
        $('#templateForm').attr('action', '{{ url("admin/handover-templates") }}/' + btn.data('id'));
        $('#templateForm').append('<input type="hidden" name="_method" value="PUT">');
        $('#templateModal').modal('show');
    });

    $('#templateModal').on('hidden.bs.modal', function() {
        $('#templateModalTitle').text('Tambah Item Checklist');
        $('#templateId').val('');
        $('#templateName').val('');
        $('#templateDescription').val('');
        $('#templateSortOrder').val('0');
        $('#templateIsRequired').prop('checked', true);
        $('#templateForm').attr('action', '{{ route('handover-templates.store') }}');
        $('#templateForm').find('input[name="_method"]').remove();
    });

    $('.deactivate-template-btn').on('click', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (confirm('Nonaktifkan item "' + name + '"?')) {
            var form = $('#deactivateForm');
            form.attr('action', '{{ url("admin/handover-templates") }}/' + id);
            form.submit();
        }
    });

    $('.activate-template-btn').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '{{ url("admin/handover-templates") }}/' + id,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'PUT',
                is_active: true,
                name: $(this).closest('tr').find('td:eq(1)').text().trim()
            },
            success: function() { location.reload(); }
        });
    });

    $('#reorderBtn').on('click', function() {
        var items = [];
        $('#templateTableBody tr').each(function() {
            var id = $(this).data('id');
            var sortOrder = $(this).find('.sort-order-input').val();
            if (id) {
                items.push({ id: id, sort_order: sortOrder });
            }
        });
        $.ajax({
            url: '{{ route('handover-templates.reorder') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                items: items
            },
            success: function() {
                alert('Urutan berhasil disimpan');
                location.reload();
            }
        });
    });
});
</script>
@endpush
