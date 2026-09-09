<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">
    {{-- Toast notifications will be appended here by ajax-form-handler.js --}}
</div>

{{-- Standard Bootstrap Flash Messages as Fallback --}}
@if(session('success'))
<div class="toast align-items-center text-white bg-success border-0 show position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 9999">
    <div class="d-flex">
        <div class="toast-body">
            {{ session('success') }}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>
@endif

@if(session('error'))
<div class="toast align-items-center text-white bg-danger border-0 show position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 9999">
    <div class="d-flex">
        <div class="toast-body">
            {{ session('error') }}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>
@endif

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.map(function(toastEl) {
            var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
            toast.show();
        });
    });
</script>
