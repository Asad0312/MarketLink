<?php
$page = 'explore';
$pageTitle = page_title('Explore fresh produce');
$products = $products ?? [];
$categories = $categories ?? [];
$markets = $markets ?? [];
$filters = $filters ?? [];
$total = (int) ($total ?? count($products));
$totalPages = (int) ($totalPages ?? 1);
$currentPage = (int) ($currentPage ?? 1);
?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>Explore</span></div>
        <span class="eyebrow">This week’s marketplace</span>
        <h1>Find something fresh for your basket.</h1>
        <p>Search approved local stock by name, category, market, market day, or price. Availability is checked again when you place an order.</p>
    </div>
</section>

<section class="section-sm" style="padding-top:24px">
    <div class="container">
        <form class="filter-bar" method="get" action="<?= e(url('explore')) ?>">
            <div class="filter-search">
                <label class="sr-only" for="q">Search produce or farmer</label>
                <input id="q" name="q" type="search" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search tomatoes, bread, Green Valley…">
            </div>
            <div>
                <label class="sr-only" for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (string) ($filters['category'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="sr-only" for="market">Market</label>
                <select id="market" name="market">
                    <option value="">All markets</option>
                    <?php foreach ($markets as $market): ?>
                        <option value="<?= (int) $market['id'] ?>" <?= (string) ($filters['market'] ?? '') === (string) $market['id'] ? 'selected' : '' ?>><?= e($market['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="sr-only" for="day">Market day</label>
                <select id="day" name="day">
                    <option value="">Any day</option>
                    <?php foreach (['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '0' => 'Sunday'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= (string) ($filters['day'] ?? '') === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:7px">
                <input aria-label="Minimum price" name="min_price" type="number" min="0" step="1" value="<?= e($filters['min_price'] ?? '') ?>" placeholder="Min" style="min-width:78px">
                <input aria-label="Maximum price" name="max_price" type="number" min="0" step="1" value="<?= e($filters['max_price'] ?? '') ?>" placeholder="Max" style="min-width:78px">
            </div>
            <div>
                <label class="sr-only" for="sort">Sort products</label>
                <select id="sort" name="sort">
                    <option value="newest" <?= ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Price: low first</option>
                    <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Price: high first</option>
                    <option value="name" <?= ($filters['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Name A–Z</option>
                </select>
            </div>
            <button class="btn" type="submit"><?= icon('filter') ?> Apply</button>
        </form>

        <div class="toolbar">
            <div><strong style="color:var(--green-950)"><?= $total ?> <?= $total === 1 ? 'listing' : 'listings' ?></strong><?php if ($filters['q'] ?? ''): ?><span class="text-muted"> for “<?= e($filters['q']) ?>”</span><?php endif; ?></div>
            <?php if (array_filter($filters, fn($value) => $value !== '' && $value !== 'newest')): ?>
                <a class="btn btn-light btn-sm" href="<?= e(url('explore')) ?>">Clear filters</a>
            <?php endif; ?>
        </div>

        <?php if ($products): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php require BASE_PATH . '/views/partials/product-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="tabs mt-4" aria-label="Product pages" style="justify-content:center">
                    <?php for ($number = 1; $number <= $totalPages; $number++): ?>
                        <a class="tab-link <?= $number === $currentPage ? 'active' : '' ?>" href="<?= e(url('explore', array_merge($filters, ['page_number' => $number]))) ?>"><?= $number ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="card empty-state">
                <span class="empty-state-icon">🔎</span>
                <h3>No produce matches those filters</h3>
                <p>Try a broader search, another market day, or clear a price filter to see more weekly stock.</p>
                <a class="btn" href="<?= e(url('explore')) ?>">Clear all filters</a>
            </div>
        <?php endif; ?>
    </div>
</section>
