<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' — FinanceFlow' : 'FinanceFlow'; ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo isset($basePath) ? $basePath : ''; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <span class="brand-text">FinanceFlow</span>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/transactions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
            <i class="bi bi-arrow-left-right"></i>
            <span>Transactions</span>
        </a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/budget.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
            <i class="bi bi-pie-chart-fill"></i>
            <span>Budget</span>
        </a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/savings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'savings.php' ? 'active' : ''; ?>">
            <i class="bi bi-piggy-bank-fill"></i>
            <span>Savings</span>
        </a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/cost_cutting.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cost_cutting.php' ? 'active' : ''; ?>">
            <i class="bi bi-scissors"></i>
            <span>Cost Cutting</span>
        </a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/streaks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'streaks.php' ? 'active' : ''; ?>">
            <i class="bi bi-fire"></i>
            <span>Streaks</span>
        </a>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Reports</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="user-role">Personal Account</div>
            </div>
        </div>
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>pages/logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Main Content Wrapper -->
<div class="main-wrapper" id="mainWrapper">
    <!-- Top Bar -->
    <div class="topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-right">
            <div class="date-display">
                <i class="bi bi-calendar3"></i>
                <span><?php echo date('D, d M Y'); ?></span>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
<?php endif; ?>
