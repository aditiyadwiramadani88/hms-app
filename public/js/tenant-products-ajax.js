(function () {
    'use strict';

    var modal, form, modalTitle, btnSubmitText, productIdInput, formMethodInput;
    var photoPreview, photoPreviewImg, inputPhoto;
    var tableContainer, filterForm;
    var baseUrl;

    function init() {
        modal = new bootstrap.Modal(document.getElementById('productModal'));
        form = document.getElementById('productForm');
        modalTitle = document.getElementById('productModalLabel');
        btnSubmitText = document.getElementById('btn-submit-text');
        productIdInput = document.getElementById('product_id');
        formMethodInput = document.getElementById('form_method');
        photoPreview = document.getElementById('photo-preview');
        photoPreviewImg = document.getElementById('photo-preview-img');
        inputPhoto = document.getElementById('input_photo');
        tableContainer = document.getElementById('products-table-container');
        filterForm = document.getElementById('filterForm');
        baseUrl = window.location.pathname;

        bindAddButton();
        bindEditButtons();
        bindDeleteButtons();
        bindFormSubmit();
        bindFilterForm();
        bindPagination();
    }

    // ── Toast ───────────────────────────────────────────────
    function showToast(type, message) {
        if (typeof Toastify !== 'undefined') {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: 'top',
                position: 'right',
                stopOnFocus: true,
                style: {
                    background: type === 'success'
                        ? 'linear-gradient(to right, #0ab39c, #405189)'
                        : 'linear-gradient(to right, #f06548, #f7b84b)'
                }
            }).showToast();
        } else {
            alert(message);
        }
    }

    // ── Table Refresh ───────────────────────────────────────
    function refreshTable(url) {
        if (!url) {
            url = baseUrl;
            var formData = new FormData(filterForm);
            var params = new URLSearchParams();
            formData.forEach(function (value, key) {
                if (value) params.set(key, value);
            });
            var qs = params.toString();
            if (qs) url += '?' + qs;
        }

        tableContainer.style.opacity = '0.5';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && data.html) {
                tableContainer.innerHTML = data.html;
                tableContainer.style.opacity = '1';
                bindEditButtons();
                bindDeleteButtons();
                bindPagination();
            }
        })
        .catch(function () {
            tableContainer.style.opacity = '1';
            window.location.reload();
        });
    }

    // ── Modal Helpers ───────────────────────────────────────
    function resetForm() {
        form.reset();
        clearErrors();
        productIdInput.value = '';
        formMethodInput.value = 'POST';
        photoPreview.style.display = 'none';
        photoPreviewImg.src = '';
        inputPhoto.value = '';
    }

    function clearErrors() {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.invalid-feedback').forEach(function (el) { el.textContent = ''; });
    }

    function showErrors(errors) {
        clearErrors();
        var fieldMap = {
            name: 'input_name',
            category: 'input_category',
            price: 'input_price',
            stock: 'input_stock',
            is_active: 'input_is_active',
            photo: 'input_photo'
        };
        Object.keys(errors).forEach(function (field) {
            var inputId = fieldMap[field];
            if (!inputId) return;
            var input = document.getElementById(inputId);
            if (input) {
                input.classList.add('is-invalid');
                var feedback = document.getElementById('error-' + field);
                if (feedback) {
                    feedback.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                }
            }
        });
    }

    // ── Add Product ─────────────────────────────────────────
    function bindAddButton() {
        document.getElementById('btn-add-product').addEventListener('click', function () {
            resetForm();
            modalTitle.textContent = 'Add Product';
            btnSubmitText.textContent = 'Save Product';
            form.action = baseUrl;
            modal.show();
        });
    }

    // ── Edit Product ────────────────────────────────────────
    function bindEditButtons() {
        tableContainer.querySelectorAll('.btn-edit-product').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.dataset.id;
                resetForm();
                modalTitle.textContent = 'Edit Product';
                btnSubmitText.textContent = 'Update Product';
                form.action = baseUrl + '/' + id;
                formMethodInput.value = 'PUT';
                productIdInput.value = id;

                fetch(baseUrl + '/' + id + '/edit', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success && res.data) {
                        var d = res.data;
                        document.getElementById('input_name').value = d.name || '';
                        document.getElementById('input_category').value = d.category || '';
                        document.getElementById('input_price').value = d.price || '';
                        document.getElementById('input_stock').value = d.stock !== null && d.stock !== undefined ? d.stock : '';
                        document.getElementById('input_is_active').value = d.is_active ? '1' : '0';

                        if (d.photo_url) {
                            photoPreviewImg.src = d.photo_url;
                            photoPreview.style.display = 'block';
                        }

                        modal.show();
                    } else {
                        showToast('error', res.message || 'Failed to load product');
                    }
                })
                .catch(function () {
                    showToast('error', 'Network error');
                });
            });
        });
    }

    // ── Delete Product ──────────────────────────────────────
    function bindDeleteButtons() {
        tableContainer.querySelectorAll('.btn-delete-product').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.dataset.id;
                var name = this.dataset.name;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Delete Product',
                        text: 'Are you sure you want to delete "' + name + '"?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            doDelete(id);
                        }
                    });
                } else {
                    if (confirm('Delete product "' + name + '"?')) {
                        doDelete(id);
                    }
                }
            });
        });
    }

    function doDelete(id) {
        fetch(baseUrl + '/' + id, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: '_method=DELETE'
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                showToast('success', res.message);
                refreshTable();
            } else {
                showToast('error', res.message || 'Delete failed');
            }
        })
        .catch(function () {
            showToast('error', 'Network error');
        });
    }

    // ── Form Submit (Create / Update) ───────────────────────
    function bindFormSubmit() {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors();

            var btn = document.getElementById('btn-submit-product');
            var originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';

            var url = form.action;
            var method = formMethodInput.value;
            var formData = new FormData(form);
            if (method === 'PUT') {
                formData.set('_method', 'PUT');
            } else {
                formData.delete('_method');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function (r) {
                return r.json().then(function (data) { return { status: r.status, body: data }; });
            })
            .then(function (res) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;

                if (res.status >= 200 && res.status < 300 && res.body.success) {
                    showToast('success', res.body.message);
                    modal.hide();
                    refreshTable();
                } else if (res.status === 422 && res.body.errors) {
                    showErrors(res.body.errors);
                    showToast('error', res.body.message || 'Validation failed');
                } else {
                    showToast('error', res.body.message || 'An error occurred');
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showToast('error', 'Network error');
            });
        });
    }

    // ── Filter Form ─────────────────────────────────────────
    function bindFilterForm() {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var url = new URL(window.location.origin + baseUrl);
            var formData = new FormData(filterForm);
            formData.forEach(function (value, key) {
                if (value) url.searchParams.set(key, value);
            });
            url.searchParams.delete('page');
            refreshTable(url.toString());
        });
    }

    // ── Pagination ──────────────────────────────────────────
    function bindPagination() {
        tableContainer.querySelectorAll('.pagination a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                refreshTable(this.href);
                tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    // ── Helpers ─────────────────────────────────────────────
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    document.addEventListener('DOMContentLoaded', init);
})();
