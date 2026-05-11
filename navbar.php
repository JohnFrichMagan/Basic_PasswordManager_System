<?php
// ==============================================
// FILE: navbar.php
// ==============================================
// Top navbar component
// ==============================================
?>
<nav class="navbar-custom">
    <div class="container-fluid">
        <span class="navbar-brand">
            <i class="fas fa-key me-2"></i>Admin Password Manager
        </span>
        <div class="dropdown user-dropdown">
            <div class="d-flex align-items-center" data-bs-toggle="dropdown">
                <div class="user-avatar me-2">
                    <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                </div>
                <span><?php echo Security::sanitizeOutput($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <i class="fas fa-chevron-down ms-2 text-secondary"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end bg-dark text-white">
                <li><a class="dropdown-item text-white" href="settings.php"><i class="fas fa-user-cog"></i> Account Settings</a></li>
                <li><hr class="dropdown-divider bg-secondary"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>