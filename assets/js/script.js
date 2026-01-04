// ========================================
// 🚀 SHEET TRACKING SYSTEM - JAVASCRIPT
// ========================================

(function() {
    'use strict';

    // ========================================
    // 🌙 THEME MANAGEMENT
    // ========================================

    const THEME_KEY = 'sheet_tracker_theme';

    function getTheme() {
        return localStorage.getItem(THEME_KEY) || 'dark';
    }

    function setTheme(theme) {
        localStorage.setItem(THEME_KEY, theme);
        applyTheme(theme);
    }

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
            document.body.classList.remove('light-theme');
        } else {
            document.body.classList.add('light-theme');
            document.body.classList.remove('dark-theme');
        }

        // Update toggle button text
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.innerHTML = theme === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode';
        }
    }

    function toggleTheme() {
        const currentTheme = getTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    }

    // Apply saved theme on load
    document.addEventListener('DOMContentLoaded', function() {
        applyTheme(getTheme());

        // Theme toggle button
        const toggleBtn = document.getElementById('theme-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleTheme);
        }
    });

    // ========================================
    // 📝 FORM AUTO-SUBMIT ON FILTER CHANGE
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('.filter-form');
        if (!filterForm) return;

        const autoSubmitElements = filterForm.querySelectorAll('select, input[type="date"]');

        autoSubmitElements.forEach(element => {
            element.addEventListener('change', function() {
                // Auto-submit form when select/date changes (kecuali search input)
                if (this.id !== 'search') {
                    filterForm.submit();
                }
            });
        });

        // Search input: submit on Enter key
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterForm.submit();
                }
            });
        }
    });

    // ========================================
    // 📊 TABLE ENHANCEMENTS
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('.table');
        if (!table) return;

        // Add hover effect to show full text in truncated cells
        const truncatedCells = table.querySelectorAll('.text-truncate');
        truncatedCells.forEach(cell => {
            cell.addEventListener('mouseenter', function() {
                if (this.scrollWidth > this.clientWidth) {
                    this.style.cursor = 'help';
                }
            });
        });

        // Make table rows clickable to show details (opsional)
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                // Highlight selected row
                rows.forEach(r => r.style.backgroundColor = '');
                this.style.backgroundColor = 'var(--bg-tertiary)';

                // Could show modal with full details here
                // showDetailsModal(this);
            });
        });
    });

    // ========================================
    // 🔍 REAL-TIME SEARCH HIGHLIGHT
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        if (!searchInput || !searchInput.value) return;

        const searchTerm = searchInput.value.toLowerCase();
        const table = document.querySelector('.table tbody');
        if (!table) return;

        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach(cell => {
                const text = cell.textContent;
                if (text.toLowerCase().includes(searchTerm)) {
                    // Highlight matching text
                    const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
                    const highlightedText = text.replace(regex, '<mark>$1</mark>');
                    if (text !== highlightedText) {
                        cell.innerHTML = highlightedText;
                    }
                }
            });
        });
    });

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // ========================================
    // ⌨️ KEYBOARD SHORTCUTS
    // ========================================

    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K: Focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Ctrl/Cmd + T: Toggle theme
        if ((e.ctrlKey || e.metaKey) && e.key === 't') {
            e.preventDefault();
            toggleTheme();
        }

        // Escape: Clear search
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('search');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.value = '';
            }
        }
    });

    // ========================================
    // 📱 MOBILE MENU (if needed)
    // ========================================

    // Add mobile-friendly enhancements
    if (window.innerWidth <= 768) {
        document.addEventListener('DOMContentLoaded', function() {
            // Make cards stack nicely on mobile
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.style.marginBottom = '16px';
            });
        });
    }

    // ========================================
    // 🔄 AUTO-REFRESH (opsional)
    // ========================================

    // Uncomment untuk auto-refresh setiap 60 detik
    /*
    let autoRefreshInterval = null;

    function enableAutoRefresh(intervalSeconds = 60) {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }

        autoRefreshInterval = setInterval(() => {
            console.log('Auto-refreshing...');
            window.location.reload();
        }, intervalSeconds * 1000);

        console.log(`Auto-refresh enabled (every ${intervalSeconds}s)`);
    }

    function disableAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            console.log('Auto-refresh disabled');
        }
    }

    // Enable auto-refresh on dashboard page
    if (window.location.pathname.includes('index.php')) {
        // enableAutoRefresh(60); // Refresh every 60 seconds
    }
    */

    // ========================================
    // 💾 EXPORT CONFIRMATION
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const exportBtn = document.querySelector('a[href*="export=csv"]');
        if (exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                const confirmed = confirm('Apakah Anda yakin ingin mengexport data ke CSV?');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        }
    });

    // ========================================
    // 🎯 FORM VALIDATION
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = 'var(--color-danger)';

                        // Reset border color on input
                        field.addEventListener('input', function() {
                            this.style.borderColor = '';
                        });
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Mohon isi semua field yang wajib diisi!');
                }
            });
        });
    });

    // ========================================
    // 📊 TOOLTIP (for truncated text)
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const truncatedElements = document.querySelectorAll('[title]');

        truncatedElements.forEach(element => {
            if (element.scrollWidth > element.clientWidth) {
                element.style.cursor = 'help';
            }
        });
    });

    // ========================================
    // 🔔 NOTIFICATION HELPER
    // ========================================

    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.animation = 'fadeIn 0.3s ease-out';

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        notification.innerHTML = `
            <span class="alert-icon">${icons[type] || icons.info}</span>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    };

    // ========================================
    // 🎨 ADD FADEOUT ANIMATION
    // ========================================

    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        mark {
            background-color: rgba(255, 235, 59, 0.5);
            padding: 2px 4px;
            border-radius: 2px;
        }
    `;
    document.head.appendChild(style);

    // ========================================
    // ✅ SCRIPT LOADED
    // ========================================

    console.log('📊 Sheet Tracking System JS loaded');
    console.log('💡 Keyboard shortcuts:');
    console.log('   Ctrl+K: Focus search');
    console.log('   Ctrl+T: Toggle theme');
    console.log('   Escape: Clear search');

})();
