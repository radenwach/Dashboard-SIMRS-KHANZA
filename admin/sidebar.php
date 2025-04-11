<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="./index.php" class="brand-link">
            <img src="dist/assets/img/logo.png" alt="AdminLTE Logo" class="brand-image opacity-75">
            <span class="brand-text fw-light">DASHBOARD</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?php $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard'; ?>

                <li class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?= ($page == 'dashboard') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">KUNJUNGAN</li>
                <li class="nav-item">
                    <a href="?page=kunjungan" class="nav-link <?= ($page == 'kunjungan') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Kunjungan Rawat Jalan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=ranap" class="nav-link <?= ($page == 'ranap') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-hospital"></i>
                        <p>Kunjungan Rawat Inap</p>
                    </a>
                </li>

                <li class="nav-header">RAWAT INAP</li>
                <li class="nav-item">
                    <a href="?page=ranapmasuk" class="nav-link <?= ($page == 'ranapmasuk') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-arrow-bar-right"></i>
                        <p>Ranap Masuk</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=ranapkeluar" class="nav-link <?= ($page == 'ranapkeluar') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-arrow-bar-left"></i>
                        <p>Ranap Keluar</p>
                    </a>
                </li>

                <li class="nav-header">LABORATORIUM</li>
                <li class="nav-item">
                    <a href="?page=labralan" class="nav-link <?= ($page == 'labralan') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-droplet"></i>
                        <p>Laboratorium Ralan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=labranap" class="nav-link <?= ($page == 'labranap') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-droplet-fill"></i>
                        <p>Laboratorium Ranap</p>
                    </a>
                </li>

                <li class="nav-header">RADIOLOGI</li>
                <li class="nav-item">
                    <a href="?page=radioralan" class="nav-link <?= ($page == 'radioralan') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-camera"></i>
                        <p>Radiologi Ralan</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=radioranap" class="nav-link <?= ($page == 'radioranap') ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-camera-fill"></i>
                        <p>Radiologi Ranap</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="nav-icon bi bi-box-arrow-in-right"></i>
                        <p>Log Out</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
