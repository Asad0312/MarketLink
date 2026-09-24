<?php $page = 'cart'; $pageTitle = page_title('Your basket'); ?>
<section class="page-head" style="padding-bottom:28px"><div class="container"><div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>Basket</span></div><span class="eyebrow">Pickup pre-order</span><h1>Your market basket.</h1><p>Review quantities, then choose a pickup window for each farmer. Payment is made directly at collection.</p></div></section>
<section class="section-sm" style="padding-top:20px">
<div class="container">
<?php if ($errors): ?><div class="form-errors"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (!$cartItems): ?>
    <div class="card empty-state"><span class="empty-state-icon">🧺</span><h3>Your basket is empty</h3><p>Explore current weekly stock and add something fresh for your next market morning.</p><a class="btn" href="<?= e(url('explore')) ?>">Browse produce <?= icon('arrow-right') ?></a></div>
<?php else: ?>
    <form method="post" action="<?= e(url('cart-update')) ?>">
        <?= csrf_field() ?>
        <div class="content-card card">
            <div class="content-head"><div><h2>Basket items</h2><p><?= count($cartItems) ?> <?= count($cartItems) === 1 ? 'item' : 'items' ?> from <?= count($farmerGroups) ?> <?= count($farmerGroups) === 1 ? 'farmer' : 'farmers' ?></p></div><a class="btn btn-light btn-sm" href="<?= e(url('explore')) ?>"><?= icon('plus') ?> Add more</a></div>
            <?php foreach ($farmerGroups as $farmerId => $items): ?>
                <div style="margin:22px 0 10px;padding-top:18px;border-top:1px solid var(--line)"><div style="display:flex;align-items:center;gap:10px"><span class="farmer-avatar"><?= e(initials($items[0]['stall_name'])) ?></span><div><strong style="display:block;color:var(--green-950)"><?= e($items[0]['stall_name']) ?></strong><small class="text-muted"><?= e($items[0]['pickup_instructions'] ?: 'Pickup instructions available at checkout') ?></small></div><a class="text-green" style="margin-left:auto;font-size:.78rem;font-weight:750" href="<?= e(url('farmer',['id'=>$farmerId])) ?>">Farm profile →</a></div></div>
                <?php foreach ($items as $item): ?>
                    <div class="cart-row">
                        <div class="table-product"><span class="table-thumb"><?php $image=product_image_url($item['image']); if($image): ?><img src="<?= e($image) ?>" alt="<?= e($item['name']) ?>"><?php else: ?><?= e(product_placeholder($item['description'])) ?><?php endif; ?></span><div><strong style="color:var(--green-950)"><?= e($item['name']) ?></strong><br><small class="text-muted"><?= money($item['price']) ?> / <?= e($item['unit']) ?></small></div></div>
                        <div><label class="sr-only" for="quantity_<?= (int)$item['id'] ?>">Quantity for <?= e($item['name']) ?></label><input id="quantity_<?= (int)$item['id'] ?>" name="quantities[<?= (int)$item['id'] ?>]" type="number" min="0" max="<?= min(20,(int)$item['stock_quantity']) ?>" value="<?= (int)$item['quantity'] ?>"></div>
                        <div><strong style="color:var(--green-950)"><?= money((int)$item['price_cents']*(int)$item['quantity']/100) ?></strong></div>
                        <div><span class="pill pill-gray"><?= (int)$item['stock_quantity'] ?> left</span></div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:flex-end;margin-top:20px"><button class="btn btn-light" type="submit">Update quantities</button></div>
        </div>
    </form>

    <form method="post" action="<?= e(url('checkout')) ?>" class="mt-3">
        <?= csrf_field() ?>
        <div class="detail-grid">
            <div class="content-card card">
                <div class="content-head"><div><h2>Choose pickup windows</h2><p>Each farmer confirms and prepares their own pickup order.</p></div></div>
                <?php foreach ($farmerGroups as $farmerId => $items): ?>
                    <div class="form-group"><label for="slot_<?= (int)$farmerId ?>"><?= e($items[0]['stall_name']) ?> pickup</label><select id="slot_<?= (int)$farmerId ?>" name="slots[<?= (int)$farmerId ?>]" required><option value="">Select an available date and time</option><?php foreach (($farmerDetails[$farmerId] ?? []) as $slot): ?><option value="<?= (int)$slot['id'] ?>" <?= (string)($oldSlots[$farmerId] ?? '') === (string)$slot['id'] ? 'selected' : '' ?>><?= e(format_date($slot['pickup_date'],'D, M j')) ?> · <?= e(date('g:i A',strtotime($slot['start_time']))) ?>–<?= e(date('g:i A',strtotime($slot['end_time']))) ?> · <?= e($slot['market_name']) ?></option><?php endforeach; ?></select><?php if (!$farmerDetails[$farmerId]): ?><p class="field-help text-danger">No open pickup window is available. Remove this farmer’s items or check back later.</p><?php else: ?><p class="field-help">Changes and cancellations are allowed before <?= e(date('g:i A',strtotime($farmerDetails[$farmerId][0]['cutoff_at']))) ?>.</p><?php endif; ?></div>
                <?php endforeach; ?>
                <div class="form-group mb-0"><label for="order_note">Note for the farmer <small>(optional)</small></label><textarea id="order_note" name="note" maxlength="500" placeholder="Packing or pickup preferences"><?= e(old('note')) ?></textarea></div>
            </div>
            <aside class="card order-summary">
                <span class="eyebrow">Order summary</span><h2>Pickup total</h2>
                <div class="summary-line"><span>Produce subtotal</span><strong><?= money($subtotal/100) ?></strong></div>
                <div class="summary-line"><span>MarketLink fee</span><strong>Rs 0.00</strong></div>
                <div class="summary-line"><span>Delivery</span><strong>Pickup only</strong></div>
                <div class="summary-total"><span>Due at pickup</span><strong><?= money($subtotal/100) ?></strong></div>
                <button class="btn btn-lg btn-block mt-3" type="submit" <?= array_filter($farmerDetails, fn($slots) => !$slots) ? 'disabled' : '' ?>>Place pickup order <?= icon('arrow-right') ?></button>
                <p class="field-help text-center mt-2">No online payment. Stock and slot capacity are rechecked before your order is created.</p>
            </aside>
        </div>
    </form>
<?php endif; ?>
</div>
</section>
