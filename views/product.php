<?php
$page = 'explore';
$pageTitle = page_title($product['name']);
$imageUrl = product_image_url($product['image']);
$stock = (int) $product['stock_quantity'];
?>
<section class="page-head" style="padding-bottom:25px">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><a href="<?= e(url('explore')) ?>">Produce</a><span>/</span><span><?= e($product['name']) ?></span></div>
    </div>
</section>
<section class="section-sm" style="padding-top:8px">
    <div class="container">
        <div class="detail-grid">
            <div class="detail-image reveal">
                <?php if ($imageUrl): ?><img src="<?= e($imageUrl) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><span role="img" aria-label="<?= e($product['name']) ?>"><?= e(product_placeholder($product['category_name'])) ?></span><?php endif; ?>
            </div>
            <div class="card detail-copy reveal reveal-delay-1">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span class="pill pill-lime"><?= e($product['category_name']) ?></span>
                    <?php if (current_user() && current_user()['role'] === 'customer'): ?>
                        <form method="post" action="<?= e(url('favorite-toggle')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="type" value="product"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                            <button class="favorite-button <?= $isFavorite ? 'active' : '' ?>" style="position:static" type="submit" aria-label="Toggle favorite"><?= icon('heart') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
                <h1><?= e($product['name']) ?></h1>
                <p class="text-muted"><?= e($product['description']) ?></p>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <?= star_display((float) $product['average_rating']) ?>
                    <span class="text-muted"><?= (int) $product['review_count'] ?> customer <?= (int) $product['review_count'] === 1 ? 'review' : 'reviews' ?></span>
                </div>
                <div class="detail-price"><?= money($product['price']) ?> <small style="font-size:.8rem;color:var(--muted);font-weight:600">/ <?= e($product['unit']) ?></small></div>
                <ul class="spec-list">
                    <li><strong><?= e(ucfirst($product['unit'])) ?></strong><span>Sold by <?= e($product['unit']) ?></span></li>
                    <li><strong><?= $stock > 0 ? e($stock) . ' available' : 'Sold out' ?></strong><span>Current weekly stock</span></li>
                    <li><strong><?= e($product['stall_name']) ?></strong><span>Local farmer</span></li>
                    <li><strong>Pickup only</strong><span>Pay at collection</span></li>
                </ul>
                <?php if (current_user() && current_user()['role'] === 'customer'): ?>
                    <?php if ($stock > 0): ?>
                        <form method="post" action="<?= e(url('cart-add')) ?>" class="mt-3">
                            <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <div style="display:flex;gap:10px;align-items:end">
                                <div class="form-group" style="width:120px;margin:0"><label for="detail_quantity">Quantity</label><input id="detail_quantity" name="quantity" type="number" min="1" max="<?= min($stock, 20) ?>" value="1" required></div>
                                <button class="btn btn-lg" style="flex:1" type="submit"><?= icon('cart') ?> Add to basket</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">This item is currently sold out. Save it as a favorite to check back after the next harvest.</div>
                    <?php endif; ?>
                <?php elseif (current_user()): ?>
                    <a class="btn btn-light btn-block" href="<?= e(url('farmer-product-form')) ?>"><?= icon('plus') ?> List a product</a>
                <?php else: ?>
                    <a class="btn btn-lg btn-block" href="<?= e(url('login', ['next' => rawurlencode($_SERVER['REQUEST_URI'] ?? url('product', ['id' => $product['id']]))])) ?>">Sign in to pre-order <?= icon('arrow-right') ?></a>
                <?php endif; ?>
                <a class="mt-3" style="display:flex;align-items:center;gap:8px;font-size:.84rem;font-weight:700" href="<?= e(url('farmer', ['id' => $product['farmer_id']])) ?>"><?= icon('store') ?> Visit <?= e($product['stall_name']) ?>’s farm stand →</a>
            </div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Community notes</span><h2>What neighbors are saying.</h2></div><p>Only customers who completed a pickup order can leave a review.</p></div>
        <?php if ($productReviews): ?>
            <div class="review-grid">
                <?php foreach ($productReviews as $review): ?>
                    <article class="card review-card">
                        <div class="review-head"><span class="user-avatar"><?= e(initials($review['customer_name'])) ?></span><div><strong><?= e($review['customer_name']) ?></strong><time><?= format_date($review['created_at']) ?></time></div><span style="margin-left:auto"><?= star_display((float) $review['rating'], false) ?></span></div>
                        <p><?= e($review['comment']) ?></p>
                        <?php if ($review['response']): ?><div class="review-reply"><strong><?= e($product['stall_name']) ?> replied:</strong><br><?= e($review['response']) ?></div><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card empty-state"><span class="empty-state-icon">💬</span><h3>Be the first to review this pick</h3><p>After collecting your order, your feedback helps neighbors choose with confidence.</p></div>
        <?php endif; ?>
    </div>
</section>

<?php if ($relatedProducts): ?>
<section class="section-sm">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Keep browsing</span><h2>More from this category.</h2></div><a class="btn btn-light btn-sm" href="<?= e(url('explore', ['category' => $product['category_id']])) ?>">See all <?= icon('arrow-right') ?></a></div>
        <div class="product-grid"><?php foreach ($relatedProducts as $productItem): ?><?php $product = $productItem; require BASE_PATH . '/views/partials/product-card.php'; ?><?php endforeach; ?></div>
    </div>
</section>
<?php endif; ?>
