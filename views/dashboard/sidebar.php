<?php
$dashboardPage = $dashboardPage ?? '';
$user = current_user();
$unread = 0;
if ($user) {
    $unreadStatement = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $unreadStatement->execute([$user['id']]);
    $unread = (int) $unreadStatement->fetchColumn();
}
$menus = [
    'customer' => [
        ['label' => 'Shopping', 'items' => [
            ['customer-dashboard', 'Overview', 'dashboard'],
            ['orders', 'My orders', 'orders'],
            ['favorites', 'Favorites', 'heart'],
            ['profile', 'My profile', 'user'],
        ]],
    ],
    'farmer' => [
        ['label' => 'Farm management', 'items' => [
            ['farmer-dashboard', 'Overview', 'dashboard'],
            ['farmer-products', 'My products', 'package'],
            ['farmer-orders', 'Pre-orders', 'orders'],
            ['farmer-slots', 'Pickup slots', 'calendar'],
            ['farmer-insights', 'Insights', 'chart'],
            ['profile', 'Farm profile', 'user'],
        ]],
    ],
    'admin' => [
        ['label' => 'Platform control', 'items' => [
            ['admin-dashboard', 'Overview', 'dashboard'],
            ['admin-users', 'Users', 'users'],
            ['admin-markets', 'Markets', 'map'],
            ['admin-categories', 'Categories', 'package'],
            ['admin-moderation', 'Moderation', 'shield'],
            ['admin-reports', 'Reports', 'chart'],
            ['admin-announcements', 'Announcements', 'bell'],
        ]],
    ],
];
?>
<aside class="card dashboard-nav" aria-label="Dashboard navigation">
    <div class="dashboard-user">
        <span class="user-avatar"><?= e(initials($user['name'])) ?></span>
        <div><strong><?= e($user['name']) ?></strong><span><?= e($user['email']) ?></span></div>
    </div>
    <nav class="side-nav">
        <?php foreach ($menus[$user['role']] ?? [] as $section): ?>
            <div class="side-label"><?= e($section['label']) ?></div>
            <?php foreach ($section['items'] as [$pageKey, $label, $iconName]): ?>
                <a class="<?= $dashboardPage === $pageKey ? 'active' : '' ?>" href="<?= e(url($pageKey)) ?>">
                    <?= icon($iconName) ?><span><?= e($label) ?></span><?php if ($pageKey === 'orders' || $pageKey === 'farmer-orders'): ?><?php if ($unread): ?><span class="cart-count" style="position:static;margin-left:auto"><?= $unread ?></span><?php endif; ?><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
</aside>
