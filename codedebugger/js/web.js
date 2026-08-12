(function() {
        'use strict';

        var tabs = document.querySelectorAll('.week-tab');
        var panels = document.querySelectorAll('.week-panel');

        function activateWeek(weekId) {
            tabs.forEach(function(tab) {
                var isActive = tab.dataset.week === weekId;
                tab.classList.toggle('active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            panels.forEach(function(panel) {
                var isActive = panel.dataset.week === weekId;
                panel.classList.toggle('active', isActive);
            });
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                activateWeek(this.dataset.week);
            });
        });

        var tabContainer = document.querySelector('.week-tabs');
        if (tabContainer) {
            tabContainer.addEventListener('keydown', function(e) {
                var current = document.activeElement;
                if (!current.classList.contains('week-tab')) return;
                var tabsArray = Array.from(tabs);
                var idx = tabsArray.indexOf(current);
                var newIdx = idx;
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    newIdx = (idx + 1) % tabsArray.length;
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    newIdx = (idx - 1 + tabsArray.length) % tabsArray.length;
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    newIdx = 0;
                } else if (e.key === 'End') {
                    e.preventDefault();
                    newIdx = tabsArray.length - 1;
                } else {
                    return;
                }
                tabsArray[newIdx].focus();
                tabsArray[newIdx].click();
            });
        }

        var weeklyContent = document.querySelector('.weekly-content');
        if (weeklyContent) {
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    requestAnimationFrame(function() {
                        weeklyContent.scrollTop = 0;
                    });
                });
            });
        }

        var cardScrolls = document.querySelectorAll('.card-scroll');

        function updateScrollHint(el) {
            var hint = el.querySelector('.scroll-hint');
            if (!hint) return;
            var hasOverflow = el.scrollHeight > el.clientHeight + 2;
            hint.classList.toggle('visible', hasOverflow);

            var isAtBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 4;
            if (hasOverflow && !isAtBottom) {
                el.style.maskImage = 'linear-gradient(to bottom, #000 60%, transparent 100%)';
                el.style.webkitMaskImage = 'linear-gradient(to bottom, #000 60%, transparent 100%)';
            } else {
                el.style.maskImage = 'none';
                el.style.webkitMaskImage = 'none';
            }
        }

        cardScrolls.forEach(function(el) {
            var check = function() { updateScrollHint(el); };
            check();

            el.addEventListener('scroll', function() {
                updateScrollHint(this);
            });

            var ro = new ResizeObserver(function() { updateScrollHint(el); });
            ro.observe(el);

            var img = el.closest('.system-card')?.querySelector('.card-img');
            if (img) {
                img.addEventListener('load', function() { updateScrollHint(el); });
            }

            var hint = el.querySelector('.scroll-hint');
            if (hint) {
                hint.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var parent = this.closest('.card-scroll');
                    if (parent) {
                        parent.scrollTo({ top: parent.scrollHeight, behavior: 'smooth' });
                    }
                });
            }
        });

        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                cardScrolls.forEach(function(el) { updateScrollHint(el); });
            }, 200);
        });

    })();