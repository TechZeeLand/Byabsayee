<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Byabsayee') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-top">
        <a href="/dashboard" class="sidebar-logo">
            <div class="s-logo-icon">৳</div>
            <span class="s-logo-text">Byabsayee</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="/dashboard" class="nav-item <?= activePage('dashboard') ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="17" height="17"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h4a1 1 0 001-1v-3h2v3a1 1 0 001 1h4a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
            Dashboard
        </a>
        <a href="/books" class="nav-item <?= activePage('books') ?>">
            <svg viewBox="0 0 20 20" fill="currentColor" width="17" height="17"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
            My Books
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <div class="s-avatar"><?= mb_strtoupper(mb_substr(auth()['name'], 0, 1)) ?></div>
            <div class="s-user-info">
                <div class="s-user-name"><?= e(auth()['name']) ?></div>
                <div class="s-user-email"><?= e(auth()['email']) ?></div>
            </div>
        </div>
        <a href="/logout" class="nav-item nav-logout">
            <svg viewBox="0 0 20 20" fill="currentColor" width="17" height="17"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
            Log out
        </a>
    </div>
</aside>

<!-- ===================== MAIN ===================== -->
<div class="app-main">

    <!-- Mobile top bar -->
    <div class="mobile-topbar">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <span class="mobile-title"><?= e($pageTitle ?? 'Byabsayee') ?></span>
    </div>

    <div class="app-content">

        <!-- Flash messages -->
        <?php if ($msg = flash('error')): ?>
            <div class="flash flash-error"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('success')): ?>
            <div class="flash flash-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <!-- Page content injected here -->
        <?= $content ?? '' ?>

    </div>
</div>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
