// ========================================
// 🚀 SHEET TRACKING SYSTEM - ENHANCED JAVASCRIPT
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

        // Show notification
        showNotification(`Tema diubah ke ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode`, 'success');
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
    // 📝 ENHANCED FORM AUTO-SUBMIT
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('.filter-form');
        if (!filterForm) return;

        const autoSubmitElements = filterForm.querySelectorAll('select, input[type="date"]');

        autoSubmitElements.forEach(element => {
            element.addEventListener('change', function() {
                // Show loading state
                if (this.id !== 'search' && this.id !== 'per_page') {
                    showLoadingOverlay();

                    // Small delay untuk smooth transition
                    setTimeout(() => {
                        filterForm.submit();
                    }, 150);
                } else if (this.id === 'per_page') {
                    filterForm.submit();
                }
            });
        });

        // Search input: submit on Enter key
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;

            // Live search dengan debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);

                if (this.value.length >= 3 || this.value.length === 0) {
                    searchTimeout = setTimeout(() => {
                        showLoadingOverlay();
                        filterForm.submit();
                    }, 800); // Wait 800ms after user stops typing
                }
            });

            // Submit on Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    showLoadingOverlay();
                    filterForm.submit();
                }
            });
        }
    });

    // ========================================
    // 📊 ENHANCED TABLE INTERACTIONS
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('.table');
        if (!table) return;

        // Add hover effect to show full text in truncated cells
        const truncatedCells = table.querySelectorAll('.text-truncate, td[title]');
        truncatedCells.forEach(cell => {
            cell.addEventListener('mouseenter', function() {
                if (this.scrollWidth > this.clientWidth || this.hasAttribute('title')) {
                    this.style.cursor = 'help';

                    // Show tooltip
                    const tooltip = document.createElement('div');
                    tooltip.className = 'custom-tooltip';
                    tooltip.textContent = this.getAttribute('title') || this.textContent;
                    document.body.appendChild(tooltip);

                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + 'px';
                    tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';

                    this._tooltip = tooltip;
                }
            });

            cell.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    this._tooltip = null;
                }
            });
        });

        // Smooth row highlight
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            // Stagger animation
            row.style.animationDelay = `${index * 30}ms`;

            row.addEventListener('click', function(e) {
                // Don't highlight if clicking a link
                if (e.target.tagName === 'A') return;

                // Remove previous highlights
                rows.forEach(r => r.classList.remove('row-highlighted'));

                // Add highlight to clicked row
                this.classList.add('row-highlighted');

                // Remove highlight after 2 seconds
                setTimeout(() => {
                    this.classList.remove('row-highlighted');
                }, 2000);
            });
        });
    });

    // ========================================
    // 🔍 ENHANCED SEARCH HIGHLIGHT
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        if (!searchInput || !searchInput.value) return;

        const searchTerm = searchInput.value.toLowerCase();
        if (searchTerm.length < 2) return;

        const table = document.querySelector('.table tbody');
        if (!table) return;

        const rows = table.querySelectorAll('tr');
        let matchCount = 0;

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach(cell => {
                const text = cell.textContent;
                if (text.toLowerCase().includes(searchTerm)) {
                    matchCount++;
                    // Highlight matching text
                    const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
                    const highlightedText = text.replace(regex, '<mark>$1</mark>');
                    if (text !== highlightedText && !cell.querySelector('code, .badge')) {
                        cell.innerHTML = highlightedText;
                    }
                }
            });
        });

        // Show match count
        if (matchCount > 0) {
            showNotification(`Ditemukan ${matchCount} hasil pencarian`, 'info');
        }
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
                showNotification('🔍 Focus pada pencarian', 'info');
            }
        }

        // Ctrl/Cmd + T: Toggle theme
        if ((e.ctrlKey || e.metaKey) && e.key === 't') {
            e.preventDefault();
            toggleTheme();
        }

        // Ctrl/Cmd + R: Reset filters (hanya jika tidak di input)
        if ((e.ctrlKey || e.metaKey) && e.key === 'r' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            window.location.href = 'index.php';
        }

        // Escape: Clear search atau close modals
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('search');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.value = '';
                searchInput.blur();
            }

            // Close any tooltips
            document.querySelectorAll('.custom-tooltip').forEach(t => t.remove());
        }
    });

    // ========================================
    // 🔄 LOADING OVERLAY
    // ========================================

    function showLoadingOverlay() {
        // Remove existing overlay
        const existing = document.querySelector('.loading-overlay');
        if (existing) return;

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="loading"></div>
                <p>Memuat data...</p>
            </div>
        `;
        document.body.appendChild(overlay);

        // Auto remove after 10 seconds (fallback)
        setTimeout(() => {
            overlay.remove();
        }, 10000);
    }

    // ========================================
    // 💾 EXPORT CONFIRMATION
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const exportBtn = document.querySelector('a[href*="export=csv"]');
        if (exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (confirm('📥 Export data ke CSV?\n\nFile akan didownload dalam format Excel-compatible.')) {
                    showNotification('📥 Mengexport data...', 'info');
                    window.location.href = this.href;
                }
            });
        }
    });

    // ========================================
    // 🎯 ENHANCED FORM VALIDATION
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                let firstInvalid = null;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = 'var(--color-danger)';
                        field.classList.add('shake');

                        if (!firstInvalid) firstInvalid = field;

                        // Reset border color on input
                        field.addEventListener('input', function() {
                            this.style.borderColor = '';
                            this.classList.remove('shake');
                        }, { once: true });
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showNotification('⚠️ Mohon isi semua field yang wajib!', 'warning');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    });

    // ========================================
    // 🔔 ENHANCED NOTIFICATION SYSTEM
    // ========================================

    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        notification.innerHTML = `
            <span class="notification-icon">${icons[type] || icons.info}</span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;

        document.body.appendChild(notification);

        // Auto fade in
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };

    // ========================================
    // 📊 SMOOTH SCROLL TO TOP
    // ========================================

    document.addEventListener('DOMContentLoaded', function() {
        // Create scroll to top button
        const scrollBtn = document.createElement('button');
        scrollBtn.className = 'scroll-to-top';
        scrollBtn.innerHTML = '↑';
        scrollBtn.title = 'Kembali ke atas';
        document.body.appendChild(scrollBtn);

        // Show/hide based on scroll position
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });

        // Scroll to top on click
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });

    // ========================================
    // 🎨 DYNAMIC STYLES
    // ========================================

    const style = document.createElement('style');
    style.textContent = `
        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            max-width: 400px;
            padding: 16px 20px;
            background: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            transform: translateX(450px);
            transition: transform var(--transition-normal);
            border-left: 4px solid var(--color-info);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-success { border-left-color: var(--color-success); }
        .notification-error { border-left-color: var(--color-danger); }
        .notification-warning { border-left-color: var(--color-warning); }
        .notification-info { border-left-color: var(--color-info); }

        .notification-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .notification-message {
            flex: 1;
            color: var(--text-primary);
            font-size: 14px;
        }

        .notification-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all var(--transition-fast);
        }

        .notification-close:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            animation: fadeIn 0.2s ease-out;
        }

        .loading-spinner {
            background: var(--bg-secondary);
            padding: 30px 40px;
            border-radius: 16px;
            box-shadow: var(--shadow-xl);
            text-align: center;
        }

        .loading-spinner .loading {
            margin: 0 auto 16px;
        }

        .loading-spinner p {
            color: var(--text-primary);
            font-size: 14px;
            margin: 0;
        }

        /* Scroll to Top Button */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.8);
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-lg);
            z-index: 999;
        }

        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .scroll-to-top:hover {
            background: var(--color-primary-hover);
            transform: scale(1.1);
            box-shadow: var(--shadow-xl);
        }

        .scroll-to-top:active {
            transform: scale(0.95);
        }

        /* Custom Tooltip */
        .custom-tooltip {
            position: fixed;
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            box-shadow: var(--shadow-lg);
            z-index: 10000;
            max-width: 300px;
            word-wrap: break-word;
            pointer-events: none;
            animation: fadeIn 0.2s ease-out;
            border: 1px solid var(--border-color);
        }

        /* Row Highlight */
        .row-highlighted {
            background: rgba(66, 153, 225, 0.15) !important;
            box-shadow: 0 0 0 2px var(--color-primary) !important;
        }

        /* Shake Animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.3s ease-in-out;
        }

        /* Mark (Highlight) */
        mark {
            background-color: rgba(255, 235, 59, 0.4);
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 500;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .notification {
                right: 10px;
                left: 10px;
                min-width: auto;
                max-width: none;
            }

            .scroll-to-top {
                width: 45px;
                height: 45px;
                bottom: 20px;
                right: 20px;
            }
        }
    `;
    document.head.appendChild(style);

    // ========================================
    // ✅ SCRIPT LOADED
    // ========================================

    console.log('🚀 Sheet Tracking System JS loaded');
    console.log('⌨️  Keyboard shortcuts:');
    console.log('   Ctrl+K: Focus search');
    console.log('   Ctrl+T: Toggle theme');
    console.log('   Ctrl+R: Reset filters');
    console.log('   Escape: Clear search');

    // Show welcome notification on first load
    if (!sessionStorage.getItem('welcomed')) {
        setTimeout(() => {
            showNotification('👋 Selamat datang! Gunakan Ctrl+K untuk pencarian cepat', 'info');
            sessionStorage.setItem('welcomed', 'true');
        }, 500);
    }

})();
