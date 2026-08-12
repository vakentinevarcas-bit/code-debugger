 (function() {
            'use strict';
            window.switchPanel = function(panelId) {
                document.querySelectorAll('.panel').forEach(function(el) {
                    el.classList.remove('active');
                });
                const target = document.getElementById('panel-' + panelId);
                if (target) target.classList.add('active');
                document.querySelectorAll('.sidebar .nav-item').forEach(function(btn) {
                    btn.classList.remove('active');
                    if (btn.dataset.panel === panelId) {
                        btn.classList.add('active');
                    }
                });
            };
            document.querySelectorAll('.sidebar .nav-item').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const panel = this.dataset.panel;
                    if (panel) switchPanel(panel);
                });
            });
            window.toggleSetting = function(el) {
                el.classList.toggle('active');
                const label = el.closest('.setting-row')?.querySelector('.setting-label')?.textContent?.trim() ||
                    'Setting';
                showToast('info', label + ' toggled');
            };
            window.showToast = function(type, message) {
                const container = document.getElementById('toastContainer');
                if (!container) return;
                const toast = document.createElement('div');
                toast.className = 'toast ' + type;
                const icons = { success: '✅', error: '❌', info: 'ℹ️' };
                toast.innerHTML = '<span>' + (icons[type] || 'ℹ️') + '</span> ' + message;
                container.appendChild(toast);
                setTimeout(function() {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(40px)';
                    toast.style.transition = 'all 0.3s ease';
                    setTimeout(function() {
                        toast.remove();
                    }, 350);
                }, 3000);
            };
            window.closeModal = function() {
                document.getElementById('modalOverlay').classList.remove('open');
            };
            window.openModal = function() {
                document.getElementById('modalOverlay').classList.add('open');
            };
            document.getElementById('modalOverlay').addEventListener('click', function(e) {
                if (e.target === this) closeModal();
            });
            window.closeAlert = function() {
                document.getElementById('alertOverlay').classList.remove('open');
            };
            window.openAlert = function() {
                document.getElementById('alertOverlay').classList.add('open');
            };
            document.getElementById('alertOverlay').addEventListener('click', function(e) {
                if (e.target === this) closeAlert();
            });
            function updateClock() {
                const now = new Date();
                document.getElementById('liveTime').textContent = now.toLocaleString();
            }
            setInterval(updateClock, 1000);
            updateClock();
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                    closeAlert();
                }
            });
        })();