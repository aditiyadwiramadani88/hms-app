/**
 * AjaxFormHandler - Global AJAX form handler for Simpang Homestay
 */
const AjaxFormHandler = {
    init: function() {
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form[data-ajax="true"]');
            if (form) {
                e.preventDefault();
                AjaxFormHandler.handleSubmit(form);
            }
        });

        // Initialize Button Protector for all submit buttons
        Button_Protector.init();
    },

    handleSubmit: async function(form) {
        if (form.dataset.submitting === 'true') return;

        // Confirmation dialog if requested
        if (form.dataset.ajaxConfirm) {
            if (!confirm(form.dataset.ajaxConfirm)) {
                return;
            }
        }

        const submitBtn = form.querySelector('[type="submit"], [data-submit-protect="true"]');
        const url = form.action;
        const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
        const formData = new FormData(form);

        // Clear previous errors
        AjaxFormHandler.clearErrors(form);

        // Lock form and button
        form.dataset.submitting = 'true';
        if (submitBtn) Button_Protector.start(submitBtn);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(url, {
                method: method === 'GET' ? 'GET' : 'POST', // Fetch doesn't support all methods directly in body-mode, but Laravel handles _method
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: method === 'GET' ? null : formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                AjaxFormHandler.handleSuccess(form, result);
            } else if (response.status === 422) {
                AjaxFormHandler.displayErrors(form, result.errors);
                AjaxFormHandler.showToast('error', result.message || 'Validation failed');
            } else if (response.status === 419) {
                AjaxFormHandler.showToast('error', 'Session expired. Please reload the page.');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                AjaxFormHandler.showToast('error', result.message || 'An error occurred');
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            AjaxFormHandler.showToast('error', 'Network error or server unavailable');
        } finally {
            form.dataset.submitting = 'false';
            if (submitBtn) Button_Protector.stop(submitBtn);
        }
    },

    handleSuccess: function(form, result) {
        AjaxFormHandler.showToast('success', result.message);

        // Custom callback execution if exists
        if (form.dataset.ajaxSuccess && typeof window[form.dataset.ajaxSuccess] === 'function') {
            window[form.dataset.ajaxSuccess](result, form);
        }

        if (form.dataset.ajaxRedirect) {
            setTimeout(() => {
                window.location.href = form.dataset.ajaxRedirect;
            }, 1000);
        } else if (form.dataset.ajaxReload === 'true') {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else if (form.dataset.ajaxReset === 'true') {
            form.reset();
        }

        if (form.dataset.ajaxCloseModal === 'true') {
            const modal = form.closest('.modal');
            if (modal) {
                // Try to find bootstrap modal instance
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                } else {
                    // Fallback to jQuery if bootstrap instance not found (legacy)
                    if (typeof $ !== 'undefined') $(modal).modal('hide');
                }
            }
        }

        // Custom event for success
        form.dispatchEvent(new CustomEvent('ajax:success', { detail: result }));
    },

    displayErrors: function(form, errors) {
        if (!errors) return;

        Object.keys(errors).forEach(field => {
            // Support for array fields like items.0.name
            const normalizedField = field.includes('.') ? field.split('.')[0] + '[' + field.split('.').slice(1).join('][') + ']' : field;
            const input = form.querySelector(`[name="${field}"], [name="${field}[]"], [name="${normalizedField}"]`);
            
            if (input) {
                input.classList.add('is-invalid');
                const errorFeedback = document.createElement('div');
                errorFeedback.className = 'invalid-feedback';
                errorFeedback.innerText = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                
                // For input-groups, we need to append after the group
                const container = input.closest('.input-group') || input.parentNode;
                container.appendChild(errorFeedback);
            }
        });
    },

    clearErrors: function(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    },

    showToast: function(type, message) {
        if (typeof Toastify !== 'undefined') {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: type === 'success' ? "linear-gradient(to right, #0ab39c, #405189)" : "linear-gradient(to right, #f06548, #f7b84b)",
                }
            }).showToast();
            return;
        }

        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            console.warn('Toast container not found');
            alert(message);
            return;
        }

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 mb-2`;
        toastEl.role = 'alert';
        toastEl.ariaLive = 'assertive';
        toastEl.ariaAtomic = 'true';
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toastEl);
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }
};

const Button_Protector = {
    init: function() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-submit-protect="true"]');
            if (btn && btn.type !== 'submit') {
                // If it's a link or other element with protect but not submit
                // we can manually trigger protection if needed
            }
        });
    },

    start: function(btn) {
        btn.disabled = true;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...`;
        
        // Timeout protection (30s)
        btn.dataset.timeoutId = setTimeout(() => {
            Button_Protector.stop(btn);
            AjaxFormHandler.showToast('error', 'Request timed out. Please check your connection.');
        }, 30000);
    },

    stop: function(btn) {
        if (btn.dataset.timeoutId) {
            clearTimeout(parseInt(btn.dataset.timeoutId));
            delete btn.dataset.timeoutId;
        }
        btn.disabled = false;
        if (btn.dataset.originalHtml) {
            btn.innerHTML = btn.dataset.originalHtml;
            delete btn.dataset.originalHtml;
        }
    }
};

window.AjaxFormHandler = AjaxFormHandler;
window.Button_Protector = Button_Protector;

document.addEventListener('DOMContentLoaded', () => AjaxFormHandler.init());
