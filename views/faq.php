<?php $pageTitle = page_title('Help and FAQ'); $page = 'faq'; ?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>Help center</span></div>
        <span class="eyebrow">Quick answers</span>
        <h1>How can we help?</h1>
        <p>Everything you need to know about finding produce, placing pickup orders, and selling through MarketLink.</p>
    </div>
</section>

<section class="section-sm">
    <div class="narrow">
        <div class="faq-list">
            <?php
            $faqs = [
                ['Does MarketLink deliver my order?', 'No. MarketLink is pickup-only. You choose an available farmer pickup slot and settle payment directly with the farmer when you collect your basket.'],
                ['When can I place or change an order?', 'You can place a pre-order while stock is available. A customer may edit or cancel an order until the farmer accepts it or the configured cutoff time passes.'],
                ['How are farmers approved?', 'Farmer registrations enter a pending state. An administrator reviews the account, then approves or suspends it before products can be listed.'],
                ['Can I pay online?', 'No online payment gateway is included in this version. Payment is settled safely in person at pickup, as required by the project scope.'],
                ['How do maps and directions work?', 'Market and pickup locations use OpenStreetMap, so no paid map key is required. Select a market to see its map and an external directions link.'],
                ['Can I review a farmer or product?', 'Yes. After an order is completed, a customer can rate the farmer and individual products once. Other customers can view those ratings before ordering.'],
                ['What happens if produce sells out?', 'Available stock is checked when an item is added to the cart and again before an order is placed. A farmer can also mark an item unavailable at any time.'],
                ['How do I report inappropriate content?', 'Use the Contact page and select “Report a concern.” Administrators can review and hide flagged product listings and reviews from the admin moderation panel.'],
            ];
            foreach ($faqs as $index => [$question, $answer]): ?>
                <details class="faq-item" <?= $index === 0 ? 'open' : '' ?>>
                    <summary><?= e($question) ?></summary>
                    <p><?= e($answer) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
        <div class="card card-pad mt-4 text-center">
            <h2 style="margin:0 0 7px;color:var(--green-950);font-size:1.35rem">Still need a hand?</h2>
            <p class="text-muted">Our community desk is ready to point you in the right direction.</p>
            <a class="btn" href="<?= e(url('contact')) ?>">Contact support <?= icon('arrow-right') ?></a>
        </div>
    </div>
</section>
