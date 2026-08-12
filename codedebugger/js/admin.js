(function() {
'use strict';

            const modalOverlay = document.getElementById('modalOverlay');
            const modalTitle = document.getElementById('modalTitle');
            const modalSub = document.getElementById('modalSub');
            const modalForm = document.getElementById('modalForm');
            const modalAction = document.getElementById('modalAction');
            const modalId = document.getElementById('modalId');
            const fieldWeek = document.getElementById('fieldWeek');
            const fieldTitle = document.getElementById('fieldTitle');
            const fieldDate = document.getElementById('fieldDate');
            const fieldModule = document.getElementById('fieldModule');
            const fieldItems = document.getElementById('fieldItems');
            const fieldStatus = document.getElementById('fieldStatus');
            const fieldImage = document.getElementById('fieldImage');
            const existingImage = document.getElementById('existingImage');
            const imagePreview = document.getElementById('imagePreview');
            const fieldHint = document.getElementById('fieldHint');
            const groupWeek = document.getElementById('groupWeek');
            const groupTitle = document.getElementById('groupTitle');
            const groupDate = document.getElementById('groupDate');
            const groupModule = document.getElementById('groupModule');
            const groupStatus = document.getElementById('groupStatus');
            const groupImage = document.getElementById('groupImage');

            // ── Panel switching ──
            window.switchPanel = function(panelId) {
                document.querySelectorAll('.panel').forEach(function(el) { el.classList.remove('active'); });
                const target = document.getElementById('panel-' + panelId);
                if (target) target.classList.add('active');
                document.querySelectorAll('.sidebar .nav-item').forEach(function(btn) {
                    btn.classList.remove('active');
                    if (btn.dataset.panel === panelId) btn.classList.add('active');
                });
            };
            document.querySelectorAll('.sidebar .nav-item').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const panel = this.dataset.panel;
                    if (panel) switchPanel(panel);
                });
            });

            // ── Restore the active panel from the URL (?panel=...) ──
            (function() {
                const params = new URLSearchParams(window.location.search);
                const panel = params.get('panel');
                if (panel && document.getElementById('panel-' + panel)) {
                    switchPanel(panel);
                }
            })();

            // ── Modal open (add) ──
            window.openAddModal = function(type) {
                modalForm.reset();
                imagePreview.innerHTML = '';
                existingImage.value = '';
                modalAction.value = type === 'weekly' ? 'add_weekly' : 'add_system';
                modalId.value = '';

                const isWeekly = type === 'weekly';
                modalTitle.textContent = isWeekly ? '📅 Add Weekly Update' : '🖥️ Add System Update';
                modalSub.textContent = 'Fill in the details below.';

                groupWeek.style.display = isWeekly ? 'block' : 'none';
                groupTitle.style.display = isWeekly ? 'block' : 'none';
                groupDate.style.display = isWeekly ? 'block' : 'none';
                groupModule.style.display = isWeekly ? 'none' : 'block';
                groupStatus.style.display = isWeekly ? 'none' : 'block';
                groupImage.style.display = isWeekly ? 'none' : 'block';

                if (isWeekly) {
                    fieldItems.placeholder = 'Add one item per line. Each line becomes a bullet point.';
                    fieldHint.textContent = 'Example: "Power-Up System: Collect the star to become invincible!"';
                    fieldItems.name = 'items';
                } else {
                    fieldItems.placeholder = 'Describe the system update...';
                    fieldHint.textContent = 'Description content for the system card.';
                    fieldItems.name = 'description';
                }

                modalSaveBtn.textContent = 'Add';
                modalOverlay.classList.add('open');
            };

            // ── Edit weekly ──
            window.editWeekly = function(item) {
                modalForm.reset();
                imagePreview.innerHTML = '';
                modalAction.value = 'edit_weekly';
                modalId.value = item.id;
                modalTitle.textContent = '✏️ Edit Weekly Update';
                modalSub.textContent = 'Update the details below.';

                groupWeek.style.display = 'block';
                groupTitle.style.display = 'block';
                groupDate.style.display = 'block';
                groupModule.style.display = 'none';
                groupStatus.style.display = 'none';
                groupImage.style.display = 'none';

                fieldWeek.value = item.week;
                fieldTitle.value = item.title;
                fieldDate.value = item.date_badge || '';

                let decoded;
                try { decoded = JSON.parse(item.items); } catch (e) { decoded = null; }
                if (Array.isArray(decoded)) {
                    fieldItems.value = decoded.map(function(line) { return line.text; }).join('\n');
                } else {
                    fieldItems.value = item.items || '';
                }
                fieldItems.placeholder = 'One item per line.';
                fieldHint.textContent = 'Each line becomes a bullet point in the week panel.';
                fieldItems.name = 'items';

                modalSaveBtn.textContent = 'Update';
                modalOverlay.classList.add('open');
            };

            // ── Edit system ──
            window.editSystem = function(item) {
                modalForm.reset();
                modalAction.value = 'edit_system';
                modalId.value = item.id;
                modalTitle.textContent = '✏️ Edit System Update';
                modalSub.textContent = 'Update the details below.';

                groupWeek.style.display = 'none';
                groupTitle.style.display = 'none';
                groupDate.style.display = 'none';
                groupModule.style.display = 'block';
                groupStatus.style.display = 'block';
                groupImage.style.display = 'block';

                fieldModule.value = item.module;
                fieldItems.value = item.description || '';
                fieldStatus.value = item.status || 'Live';
                fieldItems.placeholder = 'Describe the system update...';
                fieldHint.textContent = 'Description content for the system card.';
                fieldItems.name = 'description';

                existingImage.value = item.image || '';
                imagePreview.innerHTML = '';
                if (item.image) {
                    imagePreview.innerHTML = '<img src="../' + item.image + '" style="width:80px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #2a3645;" alt="current">';
                }

                modalSaveBtn.textContent = 'Update';
                modalOverlay.classList.add('open');
            };

            // ── Submit (POST) ──
            window.submitModal = function(e) {
                e.preventDefault();
                const action = modalAction.value;
                if (action === 'add_weekly' || action === 'edit_weekly') {
                    if (!fieldTitle.value.trim()) { showToast('error', 'Please fill in the title.'); return false; }
                } else {
                    if (!fieldModule.value.trim()) { showToast('error', 'Please fill in the module.'); return false; }
                }
                if (!fieldItems.value.trim()) { showToast('error', 'Please provide a description.'); return false; }
                modalForm.submit();
                return false;
            };

            // ── Close modal ──
            window.closeModal = function() { modalOverlay.classList.remove('open'); };
            modalOverlay.addEventListener('click', function(e) { if (e.target === modalOverlay) closeModal(); });

            // ── Image preview ──
            fieldImage.addEventListener('change', function() {
                const file = this.files[0];
                imagePreview.innerHTML = '';
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        imagePreview.innerHTML = '<img src="' + ev.target.result + '" style="width:80px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #2a3645;" alt="preview">';
                    };
                    reader.readAsDataURL(file);
                }
            });

            // ── Delete (direct, no confirmation alert) ──
            window.deleteItem = function(type, id) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin.php';
                const h1 = document.createElement('input'); h1.type = 'hidden'; h1.name = 'action'; h1.value = 'delete';
                const h2 = document.createElement('input'); h2.type = 'hidden'; h2.name = 'type'; h2.value = type;
                const h3 = document.createElement('input'); h3.type = 'hidden'; h3.name = 'id'; h3.value = id;
                form.appendChild(h1); form.appendChild(h2); form.appendChild(h3);
                document.body.appendChild(form);
                form.submit();
            };

            // ── Toast ──
            window.showToast = function(type, message) {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = 'toast ' + type;
                const icons = { success: '✅', error: '❌', info: 'ℹ️' };
                toast.innerHTML = '<span>' + (icons[type] || 'ℹ️') + '</span> ' + message;
                container.appendChild(toast);
                setTimeout(function() {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(40px)';
                    toast.style.transition = 'all 0.3s ease';
                    setTimeout(function() { toast.remove(); }, 350);
                }, 3000);
            };

            // ── Clock ──
            function updateClock() {
                document.getElementById('liveTime').textContent = new Date().toLocaleString();
            }
            setInterval(updateClock, 1000);
            updateClock();

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') { closeModal(); }
            });

        })();