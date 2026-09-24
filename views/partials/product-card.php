<?php
/** @var array $product */
$imageUrl = product_image_url($product['image'] ?? null);
$favorite = (int) ($product['is_favorite'] ?? 0) > 0 || is_favorite('product', (int) $product['id']);
$stock = (int) ($product['stock_quantity'] ?? 0);
$average = (float) ($product['average_rating'] ?? 0);
?>
<article class="card card-hover product-card">
    <div class="product-image">
        <?php if ($imageUrl): ?>
            <img src="<?= e($imageUrl) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <?php else: ?>
            <span class="product-placeholder" role="img" aria-label="<?= e($product['name']) ?>"><?= e(product_placeholder($product['category'] ?? '')) ?></span>
        <?php endif; ?>
        <?php if ($stock < 1): ?>
            <span class="pill pill-red product-badge">Sold out</span>
        <?php elseif ($stock <= 5): ?>
            <span class="pill pill-orange product-badge">Only <?= $stock ?> left</span>
        <?php else: ?>
            <span class="pill pill-lime product-badge">Fresh pick</span>
        <?php endif; ?>
        <?php if (current_user() && current_user()['role'] === 'customer'): ?>
            <form method="post" action="<?= e(url('favorite-toggle')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="product">
                <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                <button class="favorite-button <?= $favorite ? 'active' : '' ?>" type="submit" aria-label="<?= $favorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                    <?= icon('heart') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
    <div class="product-body">
        <div class="product-meta">
            <span><?= e(ucfirst($product['category'] ?? 'Produce')) ?></span>
            <?php if ($average > 0): ?><?= star_display($average, true) ?><?php endif; ?>
        </div>
        <h3><a href="<?= e(url('product', ['id' => $product['id']])) ?>"><?= e($product['name']) ?></a></h3>
        <p class="product-description"><?= e($product['description'] ?? 'Freshly listed by a local grower.') ?></p>
        <div class="product-footer">
            <span class="price"><?= money($product['price'] ?? 0) ?> <small>/ <?= e($product['unit'] ?? 'item') ?></small></span>
            <?php if (current_user() && current_user()['role'] === 'customer'): ?>
                <?php if ($stock > 0): ?>
                    <form method="post" action="<?= e(url('cart-add')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button class="btn btn-sm" type="submit"><?= icon('plus') ?> Add</button>
                    </form>
                <?php else: ?>
                    <span class="pill pill-gray">Unavailable</span>
                <?php endif; ?>
            <?php else: ?>
                <a class="btn btn-sm btn-light" href="<?= e(url('product', ['id' => $product['id']])) ?>">View</a>
            <?php endif; ?>
        </div>
    </div>
</article>
