const navItems = document.querySelectorAll('.nav-item');
        const pages = document.querySelectorAll('.page');

        navItems.forEach(item => {
            item.addEventListener('click', () => {
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
                
                pages.forEach(page => page.classList.remove('active'));
                
                const targetId = item.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');

                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('open');
                    document.getElementById('sidebarOverlay').classList.remove('active');
                }
            });
        });

        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });

        const ctx = document.getElementById('myChart').getContext('2d');
        
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)'); 
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');   

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Desktop',
                    data: [0, 12, 38, 30, 32], 
                    borderColor: '#10b981', 
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4, 
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 10, font: { size: 14 }, color: '#111827' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, max: 40,
                        ticks: { stepSize: 10, color: '#6b7280', font: { size: 14 } },
                        grid: { color: '#e5e7eb', drawBorder: false }, border: { display: false }
                    },
                    x: {
                        ticks: { color: '#6b7280', font: { size: 14 } },
                        grid: { display: false }, border: { display: true, color: '#e5e7eb' }
                    }
                }
            }
        });

        function toggleDropdown(id) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== id) menu.classList.remove('show');
            });
            document.getElementById(id).classList.toggle('show');
        }

        window.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-dropdown-wrapper')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        let filterState = { course: 'All', ay: 'All' };

        function filterTable(type, value) {
            filterState[type] = value;

            if (type === 'course') {
                document.getElementById('course-label').textContent = value === 'All' ? 'COURSE' : value;
            } else if (type === 'ay') {
                document.getElementById('ay-label').textContent = value === 'All' ? 'A.Y' : value;
            }

            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show'));

            const rows = document.querySelectorAll('.student-row');
            rows.forEach(row => {
                const rowCourse = row.getAttribute('data-course');
                const rowAY = row.getAttribute('data-ay');

                const matchCourse = (filterState.course === 'All' || rowCourse === filterState.course);
                const matchAY = (filterState.ay === 'All' || rowAY === filterState.ay);

                if (matchCourse && matchAY) {
                    row.style.display = ''; 
                } else {
                    row.style.display = 'none'; 
                }
            });
        }

        function filterCardsByYear(type, year) {
            const labelId = type === 'theses' ? 'theses-year-label' : 'feas-year-label';
            document.getElementById(labelId).textContent = year === 'All' ? 'YEAR PUBLISHED' : year;
            
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show'));

            const container = document.getElementById(`${type}-container`);
            const cards = container.querySelectorAll('.item-card');

            cards.forEach(card => {
                const cardYear = card.getAttribute('data-year');
                if (year === 'All' || cardYear === year) {
                    if (!card.classList.contains('hidden-card')) {
                         card.style.display = 'flex';
                    } else {
                         card.style.display = '';
                    }
                } else {
                    card.style.display = 'none';
                }
            });
        }

        let currentAddType = '';
        let thesesCount = 4; 
        let feasibilityCount = 4;
        let currentSeeMoreState = { theses: false, feasibility: false };

        function openModal(type) {
            currentAddType = type;
            let modalTitle = `Add New ${type.charAt(0).toUpperCase() + type.slice(1)}`;
            if (type === 'theses') modalTitle = 'Add New Thesis';
            
            document.getElementById('modal-title').textContent = modalTitle;
            document.getElementById('item-title').value = ''; 
            document.getElementById('item-date').value = '';  
            document.getElementById('item-doc').value = '';
            document.getElementById('addModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            if(modalId === 'addModal') currentAddType = '';
        }

        function saveItem(event) {
            event.preventDefault(); 

            const title = document.getElementById('item-title').value.trim();
            const date = document.getElementById('item-date').value.trim();
            const fileInput = document.getElementById('item-doc');
            const fileName = fileInput.files.length > 0 ? fileInput.files[0].name : null;

            if (!title || !date) return;

            if (currentAddType === 'theses' || currentAddType === 'feasibility') {
                if (currentAddType === 'theses') {
                    thesesCount++;
                    document.getElementById('dash-theses-count').innerText = thesesCount;
                } else {
                    feasibilityCount++;
                    document.getElementById('dash-feasibility-count').innerText = feasibilityCount;
                }

                const container = document.getElementById(`${currentAddType}-container`);
                const newCard = document.createElement('div');
                newCard.className = 'item-card';
                newCard.setAttribute('data-year', date);
                
                let docHtml = '';
                if(fileName) {
                    docHtml = `
                        <li>
                            <div class="doc-link" onclick="viewFile('${fileName}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                ${fileName}
                            </div>
                        </li>`;
                }

                newCard.innerHTML = `
                    <button class="btn-view-icon" onclick="viewCardDetails('${currentAddType}', 'new')" title="View Details">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                    <div>
                        <div class="item-card-title">"${title.toUpperCase()}"</div>
                        <div class="item-card-date">${date}</div>
                        <ul class="docs-list">${docHtml}</ul>
                    </div>
                `;
                container.prepend(newCard);

                if (currentSeeMoreState[currentAddType]) {
                    const seeMoreBtn = container.closest('.category-section').querySelector('.see-more');
                    toggleSeeMore(seeMoreBtn); 
                }
            } 
            closeModal('addModal');
        }

        function viewFile(fileName) {
            const page = document.getElementById('documentPage');
            page.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <h2 style="color: var(--text-dark); margin-bottom: 10px;">${fileName}</h2>
                <p>Document preview is simulated for demonstration.</p>
            `;
            document.getElementById('view-modal-title').textContent = `Viewing: ${fileName}`;
            document.getElementById('viewModal').classList.add('active');
        }

        function viewCardDetails(type, index) {
            const container = document.getElementById(`${type}-container`);
            let card;
            
            if (index === 'new') {
                card = container.firstElementChild;
            } else {
                card = container.querySelectorAll('.item-card')[index];
            }

            if (card) {
                const title = card.querySelector('.item-card-title').innerText;
                const date = card.querySelector('.item-card-date').innerText;
                
                document.getElementById('view-modal-title').textContent = `${type === 'theses' ? 'Thesis' : 'Feasibility'} Details`;
                
                const docs = card.querySelectorAll('.doc-link');
                let docSummary = `<p style="margin-bottom: 10px; font-weight: bold; color: var(--text-dark);">Year: ${date}</p>`;
                if (docs.length > 0) {
                    docSummary += `<p style="margin-bottom: 5px; font-weight: bold; color: var(--text-dark);">Attached Files:</p><ul style="list-style-type: none; padding: 0;">`;
                    docs.forEach(doc => {
                        docSummary += `<li style="margin-bottom: 5px; color: #0ea5e9; text-decoration: underline; cursor: pointer;" onclick="viewFile('${doc.innerText.trim()}')">${doc.innerText.trim()}</li>`;
                    });
                    docSummary += `</ul>`;
                } else {
                    docSummary += `<p>No documents attached.</p>`;
                }

                const page = document.getElementById('documentPage');
                page.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <h2 style="color: var(--text-dark); margin-bottom: 15px;">${title}</h2>
                    <div style="color: var(--text-muted); text-align: left; width: 80%;">
                        ${docSummary}
                    </div>
                `;
                
                document.getElementById('viewModal').classList.add('active');
            }
        }

        function filterCards(type) {
            const input = document.getElementById(`search-${type}`).value.toLowerCase();
            const container = document.getElementById(`${type}-container`);
            const cards = container.getElementsByClassName('item-card');

            for (let i = 0; i < cards.length; i++) {
                const title = cards[i].querySelector('.item-card-title').innerText.toLowerCase();
                if (title.includes(input)) {
                    if (!cards[i].classList.contains('hidden-card')) {
                        cards[i].style.display = ""; 
                    } else {
                        cards[i].style.display = "";
                    }
                } else {
                    cards[i].style.display = "none"; 
                }
            }
        }

        function toggleSeeMore(btn) {
            const container = btn.closest('.category-section').querySelector('.cards-row');
            const hiddenCards = container.querySelectorAll('.hidden-card');
            const type = container.id.split('-')[0]; 

            if (currentSeeMoreState[type]) {
                hiddenCards.forEach(card => card.style.display = 'none');
                btn.innerHTML = 'see more >';
                currentSeeMoreState[type] = false;
            } else {
                hiddenCards.forEach(card => card.style.display = 'flex');
                btn.innerHTML = 'see less <';
                currentSeeMoreState[type] = true;
            }
        }

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });