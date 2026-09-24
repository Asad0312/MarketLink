<?php $pageTitle = page_title('My orders'); $page = 'orders'; ?>
<div class="dashboard-head reveal"><div><span class="eyebrow">Pickup history</span><h1>My orders</h1><p>Track active baskets, cancel before cutoff, and revisit past purchases.</p></div><a class="btn" href="<?= e(url('explore')) ?>"><?= icon('plus') ?> New order</a></div>
<div class="tabs">
    <a class="tab-link <?= $status===''?'active':'' ?>" href="<?= e(url('orders')) ?>">All</a>
    <?php foreach (['placed'=>'Placed','accepted'=>'Accepted','ready'=>'Ready','completed'=>'Completed','cancelled'=>'Cancelled'] as $key=>$label): ?><a class="tab-link <?= $status===$key?'active':'' ?>" href="<?= e(url('orders',['status'=>$key])) ?>"><?= e($label) ?></a><?php endforeach; ?>
</div>
<div class="content-card card reveal reveal-delay-1">
<?php if ($orders): ?>
<div class="table-wrap"><table><thead><tr><th>Order</th><th>Farmer & market</th><th>Pickup window</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong><br><small class="text-muted"><?= format_date($order['created_at'],'M j, Y · g:i A') ?></small></td><td><strong><?= e($order['stall_name']) ?></strong><br><small class="text-muted"><?= e($order['market_name']) ?></small></td><td><?= format_date($order['pickup_date'] ?? $order['created_at'],'M j, Y') ?><br><small class="text-muted"><?= e(date('g:i A',strtotime($order['start_time'] ?? '08:00'))) ?> – <?= e(date('g:i A',strtotime($order['end_time'] ?? '13:00'))) ?></small></td><td><?= (int)$order['item_count'] ?> items</td><td><strong><?= money($order['subtotal_cents']/100) ?></strong></td><td><?= status_pill($order['status']) ?></td><td><a class="btn btn-light btn-sm" href="<?= e(url('order',['id'=>$order['id']])) ?>">Details</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php else: ?><div class="empty-state"><span class="empty-state-icon">🧾</span><h3>No orders in this view</h3><p>Your placed and collected pickup orders will appear here.</p><a class="btn" href="<?= e(url('explore')) ?>">Find something fresh</a></div><?php endif; ?>
</div>
