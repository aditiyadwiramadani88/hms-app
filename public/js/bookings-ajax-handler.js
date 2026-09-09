(function () {
    'use strict';

    var currentRequest = null;

    function init() {
        bindTabLinks();
        bindFilterForm();
        bindResetButton();
        bindPagination();
        bindCheckAll();
        window.addEventListener('popstate', handlePopState);
    }

    function fetchContent(url) {
        if (currentRequest) {
            currentRequest.abort();
        }
        showLoading();
        currentRequest = new AbortController();
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: currentRequest.signal
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(function (html) {
            if (html.indexOf('<!DOCTYPE') > -1 || html.indexOf('<html') > -1) {
                window.location.href = url;
                return;
            }
            var container = document.getElementById('ajax-table-container');
            if (container) {
                container.innerHTML = html;
            }
            updateBadges();
            reinitDOM();
            reenableSubmitButton();
            window.history.pushState({ url: url }, '', url);
            hideLoading();
            currentRequest = null;
        })
        .catch(function (err) {
            if (err.name === 'AbortError') return;
            hideLoading();
            currentRequest = null;
            window.location.href = url;
        });
    }

    function showLoading() {
        var container = document.getElementById('ajax-table-container');
        if (!container) return;
        var overlay = document.getElementById('ajax-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'ajax-loading-overlay';
            overlay.className = 'ajax-loading-overlay';
            overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            container.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }

    function hideLoading() {
        var overlay = document.getElementById('ajax-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    function updateBadges() {
        var badgeScript = document.getElementById('ajax-badge-counts');
        if (!badgeScript) return;
        try {
            var counts = JSON.parse(badgeScript.textContent);
            var tabLinks = document.querySelectorAll('.nav-tabs-custom .nav-link');
            tabLinks.forEach(function (link) {
                var tab = link.getAttribute('data-tab');
                if (tab && counts[tab] !== undefined) {
                    var badge = link.querySelector('.badge');
                    if (badge) {
                        badge.textContent = counts[tab];
                    }
                }
            });
        } catch (e) {
            // ignore JSON parse errors
        }
    }

    function reinitDOM() {
        var tooltipTriggerList = document.querySelectorAll('#ajax-table-container [data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length) {
            tooltipTriggerList.forEach(function (el) {
                var existing = bootstrap.Tooltip.getInstance(el);
                if (existing) existing.dispose();
                new bootstrap.Tooltip(el);
            });
        }
    }

    function reenableSubmitButton() {
        var btn = document.querySelector('#filterForm [data-submit-protect="true"]');
        if (btn && typeof Button_Protector !== 'undefined') {
            Button_Protector.stop(btn);
        }
    }

    function bindTabLinks() {
        document.addEventListener('click', function (e) {
            var link = e.target.closest('.nav-tabs-custom .nav-link');
            if (!link) return;
            e.preventDefault();
            document.querySelectorAll('.nav-tabs-custom .nav-link').forEach(function (l) {
                l.classList.remove('active');
            });
            link.classList.add('active');
            var tabInput = document.getElementById('formTab');
            var tab = link.getAttribute('data-tab');
            if (tabInput && tab) {
                tabInput.value = tab;
            }
            var url = new URL(link.href);
            // Merge current filter form values (search, dates, etc.)
            var form = document.getElementById('filterForm');
            if (form) {
                var formData = new FormData(form);
                formData.forEach(function (value, key) {
                    if (value) url.searchParams.set(key, value);
                });
            }
            url.searchParams.set('tab', tab);
            url.searchParams.delete('page');
            // Show/hide date filter fields immediately
            if (typeof window.updateFilterVisibility === 'function') {
                window.updateFilterVisibility(tab);
            }
            fetchContent(url.toString());
        });
    }

    function bindFilterForm() {
        var form = document.getElementById('filterForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var url = new URL(window.location.href);
            url.search = '';
            var formData = new FormData(form);
            formData.forEach(function (value, key) {
                if (value) {
                    url.searchParams.set(key, value);
                }
            });
            url.searchParams.delete('page');
            fetchContent(url.toString());
        });
    }

    function bindResetButton() {
        var resetLink = document.querySelector('#filterForm a.btn-soft-secondary');
        if (!resetLink) return;
        resetLink.addEventListener('click', function (e) {
            e.preventDefault();
            var form = document.getElementById('filterForm');
            if (!form) return;
            var searchInput = form.querySelector('input[name="search"]');
            if (searchInput) searchInput.value = '';
            var startDate = form.querySelector('input[name="start_date"]');
            if (startDate) startDate.value = '';
            var endDate = form.querySelector('input[name="end_date"]');
            if (endDate) endDate.value = '';
            var tabInput = document.getElementById('formTab');
            if (tabInput) tabInput.value = 'today';
            document.querySelectorAll('.nav-tabs-custom .nav-link').forEach(function (l) {
                l.classList.toggle('active', l.getAttribute('data-tab') === 'today');
            });
            var choicesSelects = form.querySelectorAll('select[data-choices]');
            choicesSelects.forEach(function (select) {
                if (select._choicesInstance) {
                    select._choicesInstance.destroy();
                }
                select.value = '';
                var wrapper = select.closest('.choices');
                if (wrapper) {
                    var parent = wrapper.parentNode;
                    parent.insertBefore(select, wrapper);
                    parent.removeChild(wrapper);
                }
                select.style.display = '';
                var instance = new Choices(select, { searchEnabled: !select.hasAttribute('data-choices-search-false') });
                select._choicesInstance = instance;
            });
            fetchContent(resetLink.href);
        });
    }

    function bindPagination() {
        var container = document.getElementById('ajax-table-container');
        if (!container) return;
        container.addEventListener('click', function (e) {
            var link = e.target.closest('.pagination a');
            if (!link) return;
            e.preventDefault();
            fetchContent(link.href);
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    function bindCheckAll() {
        var container = document.getElementById('ajax-table-container');
        if (!container) return;
        container.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'checkAll') {
                var checkboxes = document.querySelectorAll('#bookingTable tbody input[type="checkbox"]');
                checkboxes.forEach(function (cb) {
                    cb.checked = e.target.checked;
                });
            }
        });
    }

    window.sortBy = function (column) {
        var sortState = document.getElementById('ajax-sort-state');
        if (!sortState) return;
        var currentSort = sortState.getAttribute('data-sort') || 'check_in';
        var currentDir = sortState.getAttribute('data-dir') || 'desc';
        var newDir = 'asc';
        if (currentSort === column && currentDir === 'asc') {
            newDir = 'desc';
        }
        var url = new URL(window.location.href);
        url.searchParams.set('sort_by', column);
        url.searchParams.set('sort_dir', newDir);
        url.searchParams.delete('page');
        fetchContent(url.toString());
    };

    function handlePopState() {
        fetchContent(window.location.href);
        var urlParams = new URLSearchParams(window.location.search);
        var tab = urlParams.get('tab') || 'today';
        document.querySelectorAll('.nav-tabs-custom .nav-link').forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('data-tab') === tab);
        });
        var tabInput = document.getElementById('formTab');
        if (tabInput) tabInput.value = tab;
    }

    document.addEventListener('DOMContentLoaded', init);
})();
