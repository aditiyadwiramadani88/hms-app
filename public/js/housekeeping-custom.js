document.addEventListener('DOMContentLoaded', function() {
    // Search Functionality
    const searchInput = document.getElementById('hkSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const roomCards = document.querySelectorAll('.hk-room-grid-card');
            
            roomCards.forEach(card => {
                const roomNumber = card.dataset.number.toLowerCase();
                if (roomNumber.includes(searchTerm)) {
                    card.parentElement.style.display = 'block';
                } else {
                    card.parentElement.style.display = 'none';
                }
            });
            
            // Check floor visibility
            updateFloorVisibility();
        });
    }

    // Floor Filter
    const floorSelect = document.getElementById('hkFloorFilter');
    if (floorSelect) {
        floorSelect.addEventListener('change', function(e) {
            const selectedFloor = e.target.value;
            const floorGroups = document.querySelectorAll('.hk-floor-group');
            
            floorGroups.forEach(group => {
                if (selectedFloor === 'all' || group.dataset.floor === selectedFloor) {
                    group.style.display = 'block';
                } else {
                    group.style.display = 'none';
                }
            });
        });
    }

    // Status Filter via buttons/cards
    const statusFilters = document.querySelectorAll('.hk-status-filter');
    if (statusFilters) {
        statusFilters.forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                const status = this.dataset.status;
                const roomCards = document.querySelectorAll('.hk-room-grid-card');
                const statusList = status.split(',').map(s => s.trim());
                
                roomCards.forEach(card => {
                    if (status === 'all' || statusList.includes(card.dataset.status)) {
                        card.parentElement.style.display = 'block';
                    } else {
                        card.parentElement.style.display = 'none';
                    }
                });
                updateFloorVisibility();
            });
        });
    }

    // View Toggle (Grid/List)
    const viewToggleBtns = document.querySelectorAll('.hk-view-toggle');
    if (viewToggleBtns) {
        viewToggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                const gridContainer = document.getElementById('hkRoomGridContainer');
                const listContainer = document.getElementById('hkRoomListContainer');
                
                if (view === 'grid') {
                    gridContainer.style.display = 'block';
                    if (listContainer) listContainer.style.display = 'none';
                } else {
                    gridContainer.style.display = 'none';
                    if (listContainer) listContainer.style.display = 'block';
                }
                
                // Update active state
                viewToggleBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
                viewToggleBtns.forEach(b => b.classList.add('btn-outline-primary'));
                this.classList.remove('btn-outline-primary');
                this.classList.add('active', 'btn-primary');
            });
        });
    }

    // Helper: Hide empty floors
    function updateFloorVisibility() {
        const floorGroups = document.querySelectorAll('.hk-floor-group');
        floorGroups.forEach(group => {
            const visibleCards = group.querySelectorAll('.col-xl-1:not([style*="display: none"])').length;
            if (visibleCards === 0) {
                group.style.display = 'none';
            } else {
                group.style.display = 'block';
            }
        });
    }

    // Language Toggle (Optional, can be handled by backend session or JS reload)
    const langSelect = document.getElementById('hkLangToggle');
    if (langSelect) {
        langSelect.addEventListener('change', function(e) {
            window.location.href = '?lang=' + e.target.value;
        });
    }

    // Enhanced Modal Status Change
    const radioInputs = document.querySelectorAll('input[name="status"]');
    radioInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Uncheck visual styling logic handled by CSS
        });
    });
});
