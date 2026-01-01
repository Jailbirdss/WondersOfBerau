<button class="hamburger-menu" id="hamburgerBtn" aria-label="Toggle Menu">
    <span></span>
    <span></span>
    <span></span>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h2>Wonders of Berau</h2>
        <p>Admin Panel</p>
    </div>

    <nav class="sidebar-menu">
        <a href="index.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="destinasi.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'destinasi.php' ? 'active' : ''; ?>">
            <i class="fas fa-map-marked-alt"></i> Destinasi Wisata
        </a>
        <a href="akomodasi.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'akomodasi.php' ? 'active' : ''; ?>">
            <i class="fas fa-hotel"></i> Akomodasi
        </a>
        <a href="kuliner.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'kuliner.php' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i> Kuliner & UMKM
        </a>
        <a href="events.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Event
        </a>
        <a href="laporan.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i> Laporan Wisata
        </a>
        <a href="reviews.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Review Pengguna
        </a>
        <a href="analytics.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Web Analytics
        </a>
        <a href="settings.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Pengaturan Sistem
        </a>
        <a href="admin-users.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin-users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i> Kelola Admin
        </a>
        <a href="account-settings.php"
            class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'account-settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-cog"></i> Pengaturan Akun
        </a>
        <a href="../index.php" class="menu-item" target="_blank">
            <i class="fas fa-external-link-alt"></i> Lihat Website
        </a>
        <a href="logout.php" class="menu-item logout-link" data-confirm="Yakin ingin logout?">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<script>
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    (function setupConfirmModal() {
        const modalHtml = `
            <div id="customConfirmOverlay" class="confirm-overlay" aria-hidden="true">
                <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                    <div class="confirm-header">
                        <div class="confirm-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="confirm-texts">
                            <h3 id="confirmTitle">Konfirmasi</h3>
                            <p id="customConfirmMessage"></p>
                        </div>
                    </div>
                    <div class="confirm-actions">
                        <button id="customConfirmCancel" class="btn-confirm cancel">Batal</button>
                        <button id="customConfirmOk" class="btn-confirm ok">Ya, lanjut</button>
                    </div>
                </div>
            </div>
        `;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = modalHtml;
        document.body.appendChild(wrapper.firstElementChild);

        const overlay = document.getElementById('customConfirmOverlay');
        const msgEl = document.getElementById('customConfirmMessage');
        const okBtn = document.getElementById('customConfirmOk');
        const cancelBtn = document.getElementById('customConfirmCancel');

        let pendingAction = null;

        function openConfirm(message, action) {
            pendingAction = action;
            msgEl.textContent = message || 'Lanjutkan aksi ini?';
            overlay.classList.remove('closing');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirm() {
            if (!overlay.classList.contains('active')) return;
            overlay.classList.add('closing');
            setTimeout(() => {
                overlay.classList.remove('active', 'closing');
                document.body.style.overflow = '';
                pendingAction = null;
            }, 220);
        }

        okBtn.addEventListener('click', () => {
            if (pendingAction) pendingAction();
            closeConfirm();
        });
        cancelBtn.addEventListener('click', closeConfirm);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeConfirm();
        });

        function attachHandlers() {
            document.querySelectorAll('.btn-logout, .logout-link').forEach((el) => {
                if (!el.dataset.confirm) {
                    el.dataset.confirm = 'Yakin ingin logout?';
                }
            });
            document.querySelectorAll('[data-confirm]').forEach((el) => {
                if (el.dataset.confirmBound) return;
                el.dataset.confirmBound = '1';

                if (el.tagName === 'A') {
                    el.addEventListener('click', (e) => {
                        e.preventDefault();
                        const href = el.getAttribute('href');
                        openConfirm(el.dataset.confirm, () => {
                            window.location.href = href;
                        });
                    });
                } else if (el.tagName === 'FORM') {
                    el.addEventListener('submit', (e) => {
                        if (el.dataset.confirmSubmitting === '1') return;
                        e.preventDefault();
                        openConfirm(el.dataset.confirm, () => {
                            el.dataset.confirmSubmitting = '1';
                            el.submit();
                        });
                    });
                }
            });
        }

        attachHandlers();
        document.addEventListener('DOMContentLoaded', attachHandlers);
    })();

    function toggleSidebar() {
        const isActive = sidebar.classList.contains('active');

        if (isActive) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            hamburgerBtn.classList.remove('active');
            document.body.style.overflow = '';
        } else {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            hamburgerBtn.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        hamburgerBtn.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburgerBtn.addEventListener('click', toggleSidebar);

    sidebarOverlay.addEventListener('click', closeSidebar);

    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    (function () {
        const btn = document.createElement('button');
        btn.className = 'admin-back-to-top';
        btn.setAttribute('aria-label', 'Kembali ke atas');
        btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        document.body.appendChild(btn);

        const toggleVisibility = () => {
            if (window.scrollY > 180) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        };

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', toggleVisibility);
        toggleVisibility();
    })();
</script>