<?php $page = 'farmers'; $pageTitle = page_title($farmer['stall_name']); ?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><a href="<?= e(url('farmers')) ?>">Farmers</a><span>/</span><span><?= e($farmer['stall_name']) ?></span></div>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:22px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:18px">
                <span class="farmer-avatar" style="width:92px;height:92px;font-size:1.8rem;border-radius:28px"><?= e(initials($farmer['stall_name'])) ?></span>
                <div><span class="eyebrow" style="margin-bottom:7px">Approved local grower</span><h1 style="font-size:clamp(2.1rem,5vw,4rem)"><?= e($farmer['stall_name']) ?></h1><p style="margin-top:8px"><?= e($farmer['description']) ?></p></div>
            </div>
            <?php if (current_user() && current_user()['role'] === 'customer'): ?><form method="post" action="<?= e(url('favorite-toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="type" value="farmer"><input type="hidden" name="id" value="<?= (int)$farmer['id'] ?>"><button class="btn <?= $isFavorite ? 'btn-light' : '' ?>" type="submit"><?= icon('heart') ?> <?= $isFavorite ? 'Saved farmer' : 'Save farmer' ?></button></form><?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:22px"><?= star_display((float)$farmer['average_rating']) ?><span class="pill"><?= (int)$farmer['review_count'] ?> reviews</span><span class="pill pill-lime"><?= (int)$farmer['product_count'] ?> current products</span></div>
    </div>
</section>
<section class="section-sm">
    <div class="container">
        <div class="detail-grid">
            <div class="card card-pad">
                <span class="eyebrow">Find the stall</span>
                <h2 style="margin:0 0 8px;color:var(--green-950);font-size:1.6rem">Where to pick up</h2>
                <?php foreach ($farmerMarkets as $market): ?>
                    <div style="padding:16px 0;border-top:1px solid var(--line)"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><strong style="color:var(--green-950)"><?= e($market['name']) ?></strong><span class="pill pill-lime"><?= e($market['day_label']) ?></span></div><p class="text-muted mt-1" style="font-size:.83rem"><?= e($market['address']) ?> · <?= e($market['stall_label']) ?></p><p class="text-muted" style="font-size:.78rem;margin-bottom:10px"><?= e($market['pickup_instructions']) ?></p><a class="text-green" style="font-size:.8rem;font-weight:750" href="<?= e(url('market', ['id' => $market['id']])) ?>">View market map →</a></div>
                <?php endforeach; ?>
            </div>
            <div class="card card-pad" style="background:linear-gradient(145deg,var(--green-100),white)"><span class="eyebrow">Pickup promise</span><h2 style="margin:0 0 12px;color:var(--green-950);font-size:1.6rem">Clear and neighborly.</h2><p class="text-muted">Orders are prepared for the published pickup window. Payment is made directly to the farmer at collection, and each listing shows the current available quantity.</p><div class="trust-row"><span><?= icon('clock') ?> <?= e(date('g:i A',strtotime($farmer['pickup_start']))) ?> – <?= e(date('g:i A',strtotime($farmer['pickup_end']))) ?></span><span><?= icon('calendar') ?> Cutoff <?= e(date('g:i A',strtotime($farmer['cutoff_time']))) ?></span></div></div>
        </div>
    </div>
</section>
<section class="section-sm">
    <div class="container"><div class="section-head"><div><span class="eyebrow">From this farm stand</span><h2>What’s available this week.</h2></div><a class="btn btn-light btn-sm" href="<?= e(url('explore', ['q' => $farmer['stall_name']])) ?>">Search marketplace</a></div><?php if ($farmerProducts): ?><div class="product-grid"><?php foreach ($farmerProducts as $product): ?><?php require BASE_PATH . '/views/partials/product-card.php'; ?><?php endforeach; ?></div><?php else: ?><div class="card empty-state"><span class="empty-state-icon">🌱</span><h3>New stock is on the way</h3><p>This farmer has not published an active listing yet.</p></div><?php endif; ?></div>
</section>
<section class="section-sm">
    <div class="container"><div class="section-head"><div><span class="eyebrow">Community reviews</span><h2>Pickup experiences from neighbors.</h2></div></div><?php if ($farmerReviews): ?><div class="review-grid"><?php foreach ($farmerReviews as $review): ?><article class="card review-card"><div class="review-head"><span class="user-avatar"><?= e(initials($review['customer_name'])) ?></span><div><strong><?= e($review['customer_name']) ?></strong><time><?= format_date($review['created_at']) ?></time></div><span style="margin-left:auto"><?= star_display((float)$review['rating'],false) ?></span></div><p><?= e($review['comment']) ?></p><?php if ($review['response']): ?><div class="review-reply"><strong><?= e($farmer['stall_name']) ?> replied:</strong><br><?= e($review['response']) ?></div><?php endif; ?></article><?php endforeach; ?></div><?php else: ?><div class="card empty-state"><span class="empty-state-icon">💬</span><h3>No reviews yet</h3><p>Reviews appear after a customer completes a pickup order.</p></div><?php endif; ?></div>
</section>
