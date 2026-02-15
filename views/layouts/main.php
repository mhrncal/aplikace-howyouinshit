<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard' ?> - E-shop Analytics</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            padding: 1.5rem 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar .brand {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }
        
        .sidebar .brand h4 {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0;
        }
        
        .sidebar .brand small {
            color: #94a3b8;
            font-size: 0.75rem;
        }
        
        .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left-color: var(--primary-color);
        }
        
        .nav-link.active {
            background: rgba(99,102,241,0.15);
            color: #fff;
            border-left-color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-link i {
            font-size: 1.25rem;
            width: 24px;
        }
        
        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        
        /* Top bar */
        .top-bar {
            background: #fff;
            padding: 1.25rem 2rem;
            margin: -2rem -2rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(99,102,241,0.2);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            border-color: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        
        /* Tables */
        .table {
            color: #475569;
        }
        
        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Badges */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.25rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.625rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
    
    <?php if (isset($extraStyles)): ?>
        <?= $extraStyles ?>
    <?php endif; ?>
</head>
<body>
    
    <?php if ($auth->check()): ?>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4><i class="bi bi-graph-up"></i> Analytics</h4>
            <small>E-shop Platform v2.0</small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/dashboard.php' ? 'active' : '' ?>" href="/dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/products.php' ? 'active' : '' ?>" href="/products.php">
                    <i class="bi bi-box-seam"></i>
                    <span>Produkty</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/feed-sources.php' ? 'active' : '' ?>" href="/feed-sources.php">
                    <i class="bi bi-link-45deg"></i>
                    <span>Feed zdroje</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/import-logs.php' ? 'active' : '' ?>" href="/import-logs.php">
                    <i class="bi bi-clock-history"></i>
                    <span>Import logy</span>
                </a>
            </li>
            
            <?php if ($auth->isSuperAdmin()): ?>
            <li class="nav-item mt-3">
                <div class="px-3 py-2">
                    <small class="text-uppercase" style="color: #64748b; font-size: 0.7rem; font-weight: 600;">Admin</small>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/users.php' ? 'active' : '' ?>" href="/users.php">
                    <i class="bi bi-people"></i>
                    <span>Uživatelé</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item mt-3">
                <a class="nav-link" href="/profile.php">
                    <i class="bi bi-person-circle"></i>
                    <span>Můj profil</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Odhlásit se</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title"><?= $title ?? 'Dashboard' ?></h1>
            <div class="user-info">
                <?php if ($auth->isSuperAdmin()): ?>
                    <span class="badge bg-danger">Super Admin</span>
                <?php endif; ?>
                <div class="user-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= e($auth->user()['name']) ?>
                </div>
            </div>
        </div>
        
        <!-- Flash messages -->
        <?php if ($success = getFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error = getFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page content -->
        <?php if (isset($content)): ?>
            <?= $content ?>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <!-- Non-authenticated content -->
        <?php if (isset($content)): ?>
            <?= $content ?>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
</body>
</html>
