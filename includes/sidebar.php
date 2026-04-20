<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="logo-sidebar">
        <div class="logo-icon-circle">
            <i class="fa-solid fa-om"></i>
        </div>
    </div>

    <ul class="nav-links-vertical">
        <li>
            <a href="dashboard.php" class="nav-link-icon <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" title="<?= __('dashboard') ?>">
                <i class="fa-solid fa-house"></i>
                <span class="nav-label"><?= __('dashboard') ?></span>
            </a>
        </li>
        <li>
            <a href="appointments.php" class="nav-link-icon <?= ($current_page == 'appointments.php') ? 'active' : '' ?>" title="<?= __('appointments') ?>">
                <i class="fa-solid fa-calendar-alt"></i>
                <span class="nav-label"><?= __('appointments') ?></span>
            </a>
        </li>
        <li>
            <a href="customers.php" class="nav-link-icon <?= ($current_page == 'customers.php') ? 'active' : '' ?>" title="<?= __('customers') ?>">
                <i class="fa-solid fa-users"></i>
                <span class="nav-label"><?= __('customers') ?></span>
            </a>
        </li>
        <li>
            <a href="services.php" class="nav-link-icon <?= ($current_page == 'services.php') ? 'active' : '' ?>" title="<?= __('services') ?>">
                <i class="fa-solid fa-hand-holding-medical"></i>
                <span class="nav-label"><?= __('services') ?></span>
            </a>
        </li>
        <li>
            <a href="staff.php" class="nav-link-icon <?= ($current_page == 'staff.php') ? 'active' : '' ?>" title="<?= __('staff') ?>">
                <i class="fa-solid fa-user-tie"></i>
                <span class="nav-label"><?= __('staff') ?></span>
            </a>
        </li>
        <li>
            <a href="finance.php" class="nav-link-icon <?= ($current_page == 'finance.php') ? 'active' : '' ?>" title="<?= __('finance') ?>">
                <i class="fa-solid fa-wallet"></i>
                <span class="nav-label"><?= __('finance') ?></span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="nav-link-icon <?= ($current_page == 'settings.php') ? 'active' : '' ?>" title="<?= __('settings') ?>">
                <i class="fa-solid fa-cog"></i>
                <span class="nav-label"><?= __('settings') ?></span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer" style="display: flex; flex-direction: column; align-items: center; margin-top: auto; padding-bottom: 20px;">
        <a href="../logout.php" class="nav-link-icon" style="color: var(--accent-coral); margin-top: 20px;" title="<?= __('logout') ?>">
            <i class="fa-solid fa-power-off"></i>
            <span class="nav-label"><?= __('logout') ?></span>
        </a>
    </div>
</aside>
