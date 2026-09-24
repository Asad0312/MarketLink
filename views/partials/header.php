<?php
/** @var string $page */
$page = $page ?? 'home';
$pageTitle = $pageTitle ?? page_title();
$currentUser = current_user();
$cartCount = cart_count();
$navItems = [
    'home' => ['Home', 'home'],
    'explore' => ['Explore produce', 'explore'],
    'markets' => ['Markets & map', 'markets'],
    'farmers' => ['Meet farmers', 'farmers'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MarketLink connects local farmers, fresh seasonal produce, and community pickup markets.">
    <meta name="theme-color" content="#123d2e">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<a class="sr-only" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('home')) ?>" aria-label="MarketLink home">
            <span class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 9h14l-1 11H6L5 9Z"></path><path d="M8 9a4 4 0 0 1 8 0M9 14v3M12 13v4M15 14v3"></path>
                </svg>
            </span>
            <span><?= e(APP_NAME) ?><small>Fresh finds · Local roots</small></span>
        </a>

        <input class="nav-toggle" type="checkbox" id="nav-toggle" aria-label="Toggle navigation">
        <label class="nav-toggle-label" for="nav-toggle"><span></span></label>

        <div class="nav-wrap">
            <nav class="main-nav" aria-label="Main navigation">
                <?php foreach ($navItems as $key => [$label, $target]): ?>
                    <a class="<?= $page === $key ? 'active' : '' ?>" href="<?= e(url($target)) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="header-actions">
                <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
                    <a class="icon-btn cart-button" href="<?= e(url('cart')) ?>" aria-label="Cart with <?= $cartCount ?> items">
                        <?= icon('cart') ?><span class="cart-count"><?= $cartCount ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($currentUser): ?>
                    <a class="btn btn-light btn-sm" href="<?= e(url('dashboard')) ?>">
                        <?= icon('user') ?><span class="optional">Dashboard</span>
                    </a>
                    <form method="post" action="<?= e(url('logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="icon-btn" type="submit" aria-label="Sign out" title="Sign out"><?= icon('logout') ?></button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-light btn-sm" href="<?= e(url('login')) ?>">Sign in</a>
                    <a class="btn btn-sm" href="<?= e(url('register')) ?>"><span class="optional">Join MarketLink</span><span aria-hidden="true">↗</span></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<main id="main-content">
<?php $flashes = pull_flashes(); ?>
<?php if ($flashes): ?>
    <div class="container mt-3" role="status" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert <?= $flash['type'] === 'error' ? 'alert-error' : ($flash['type'] === 'warning' ? 'alert-warning' : ($flash['type'] === 'info' ? 'alert-info' : '')) ?>">
                <?= icon($flash['type'] === 'error' || $flash['type'] === 'warning' ? 'info' : 'check') ?>
                <span><?= e($flash['message']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
