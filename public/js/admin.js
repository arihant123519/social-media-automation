
document.addEventListener('DOMContentLoaded', function () {

    const minimizeToggles = document.querySelectorAll('[data-toggle="minimize"]');
    minimizeToggles.forEach(btn => {
        btn.addEventListener('click', function () {
            if (window.innerWidth >= 992) {
                document.body.classList.toggle('sidebar-icon-only');
            }
        });
    });

    const offcanvasToggles = document.querySelectorAll('[data-toggle="offcanvas"]');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    function setSidebar(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('active', open);
        document.body.classList.toggle('sidebar-open', open);
    }

    offcanvasToggles.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            setSidebar(!sidebar.classList.contains('active'));
        });
    });

    if (backdrop) backdrop.addEventListener('click', () => setSidebar(false));

    // Tapping a sidebar link on mobile closes the drawer
    sidebar?.querySelectorAll('.nav-link[href]:not([data-bs-toggle])').forEach(link => {
        link.addEventListener('click', () => { if (window.innerWidth < 992) setSidebar(false); });
    });

    document.addEventListener('click', function (e) {
        if (window.innerWidth < 992 && sidebar) {
            const isInsideSidebar = sidebar.contains(e.target);
            const isToggleBtn = e.target.closest('[data-toggle="offcanvas"]');
            if (!isInsideSidebar && !isToggleBtn && sidebar.classList.contains('active')) {
                setSidebar(false);
            }
        }
    });

    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        // Skip fragment-only toggles (e.g. href="#ai-menu") — a "#..." href resolves
        // to the CURRENT path, which would mark every collapsible item active.
        if (href && href !== '#' && href.charAt(0) !== '#') {
            const linkPath = new URL(link.href, window.location.origin).pathname;
            if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
                link.closest('.nav-item')?.classList.add('active');
                // Open parent collapse if inside sub-menu
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    parentCollapse.classList.add('show');
                    const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"], [href="#${parentCollapse.id}"]`);
                    if (parentToggle) parentToggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });

    const autoAlerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    autoAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

    const popoverEls = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverEls.forEach(el => new bootstrap.Popover(el));

    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    document.querySelectorAll('[data-ajax-form]').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            const originalText = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Loading...'; }

            try {
                const res = await fetch(form.action, {
                    method: form.method || 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
                    body: new FormData(form),
                });
                const data = await res.json();
                if (data.redirect) { window.location.href = data.redirect; return; }
                if (data.message) {
                    showToast(data.success ? 'success' : 'danger', data.message);
                }
            } catch (err) {
                showToast('danger', 'Something went wrong. Please try again.');
            } finally {
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            }
        });
    });

    window.showToast = function(type, message) {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        const id = 'toast-' + Date.now();
        const iconMap = { success: 'mdi-check-circle', danger: 'mdi-alert-circle', warning: 'mdi-alert', info: 'mdi-information' };
        const icon = iconMap[type] || 'mdi-information';

        const toastEl = document.createElement('div');
        toastEl.id = id;
        toastEl.className = `toast align-items-center text-bg-${type} border-0 mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="mdi ${icon} fs-5"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;

        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };

    function createToastContainer() {
        const el = document.createElement('div');
        el.id = 'toast-container';
        el.className = 'toast-container position-fixed top-0 end-0 p-3';
        el.style.zIndex = '9999';
        document.body.appendChild(el);
        return el;
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && sidebar) {
            sidebar.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });

});