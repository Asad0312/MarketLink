<?php $pageTitle = page_title('Customer dashboard'); $page = 'customer-dashboard'; ?>
<div class="dashboard-head reveal">
    <div><span class="eyebrow">Customer overview</span><h1>Hello, <?= e(explode(' ', trim(current_user()['name']))[0]) ?>.</h1><p>Your pickup plans and fresh market updates, all in one place.</p></div>
    <a class="btn" href="<?= e(url('explore')) ?>">Explore produce <?= icon('arrow-right') ?></a>
</div>
<div class="stat-grid">
    <article class="card stat-card"><span class="stat-label"><?= icon('clock') ?> Active orders</span><strong class="stat-value"><?= (int)$stats['active'] ?></strong><span class="stat-note">Placed, accepted, or ready</span></article>
    <article class="card stat-card"><span class="stat-label"><?= icon('check') ?> Collected</span><strong class="stat-value"><?= (int)$stats['completed'] ?></strong><span class="stat-note">Completed pickup orders</span></article>
    <article class="card stat-card"><span class="stat-label"><?= icon('heart') ?> Saved items</span><strong class="stat-value"><?= (int)$stats['favorites'] ?></strong><span class="stat-note">Farmers, products, and markets</span></article>
    <article class="card stat-card"><span class="stat-label"><?= icon('bell') ?> Unread alerts</span><strong class="stat-value"><?= (int)$stats['unread'] ?></strong><span class="stat-note">Order and account updates</span></article>
</div>
<div class="content-card card mt-3 reveal reveal-delay-1">
    <div class="content-head"><div><h2>Recent orders</h2><p>Follow each basket from placement to collection.</p></div><a class="btn btn-light btn-sm" href="<?= e(url('orders')) ?>">View all orders</a></div>
    <?php if ($recentOrders): ?>
        <div class="table-wrap"><table><thead><tr><th>Order</th><th>Farmer</th><th>Pickup</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($recentOrders as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong><br><small class="text-muted"><?= format_date($order['created_at'],'M j · g:i A') ?></small></td><td><?= e($order['stall_name']) ?></td><td><?= format_date($order['pickup_date'] ?? $order['created_at'],'M j, Y') ?><br><small class="text-muted"><?= e(date('g:i A',strtotime($order['start_time'] ?? '08:00'))) ?> – <?= e(date('g:i A',strtotime($order['end_time'] ?? '13:00'))) ?></small></td><td><strong><?= money($order['subtotal_cents']/100) ?></strong></td><td><?= status_pill($order['status']) ?></td><td><a class="btn btn-light btn-sm" href="<?= e(url('order',['id'=>$order['id']])) ?>">View</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
    <?php else: ?><div class="empty-state"><span class="empty-state-icon">🧺</span><h3>Your basket is ready for its first order</h3><p>Browse current stock and reserve produce for a published pickup window.</p><a class="btn" href="<?= e(url('explore')) ?>">Browse this week</a></div><?php endif; ?>
</div>
<div class="content-card card reveal reveal-delay-2">
    <div class="content-head"><div><h2>Market alerts</h2><p>Order confirmations and pickup updates appear here.</p></div></div>
    <?php if ($recentNotifications): ?><div class="timeline"><?php foreach ($recentNotifications as $notification): ?><div class="timeline-item"><strong><?= e($notification['title']) ?> <?= !$notification['is_read'] ? '<span class="pill pill-lime">New</span>' : '' ?></strong><span><?= e($notification['message']) ?> · <?= e(time_ago($notification['created_at'])) ?></span></div><?php endforeach; ?></div><?php else: ?><p class="text-muted">No alerts yet. We will let you know when a farmer responds.</p><?php endif; ?>
</div>
<?php if ($recommendations): ?>
<div class="content-card card mt-3 reveal reveal-delay-3"><div class="content-head"><div><h2>Popular near you</h2><p>Current listings customers are saving and ordering.</p></div><a class="btn btn-light btn-sm" href="<?= e(url('explore')) ?>">Browse all</a></div><div class="product-grid"><?php foreach ($recommendations as $product): ?><?php require BASE_PATH . '/views/partials/product-card.php'; ?><?php endforeach; ?></div></div>
<?php endif; ?>
