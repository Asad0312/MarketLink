<?php $pageTitle = page_title('About MarketLink'); $page = 'about'; ?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>About us</span></div>
        <span class="eyebrow">Our story</span>
        <h1>A shorter route from local fields to familiar hands.</h1>
        <p>MarketLink turns chalkboard updates and scattered market messages into one clear, dependable weekly source of truth.</p>
    </div>
</section>

<section class="section-sm">
    <div class="container detail-grid">
        <div class="card card-pad reveal">
            <span class="eyebrow">Why we exist</span>
            <h2 style="margin:0 0 18px;color:var(--green-950);font-size:clamp(1.8rem,4vw,2.7rem);line-height:1.15;letter-spacing:-.04em">Fresh food should not come with guesswork.</h2>
            <p class="text-muted">Every season brings good produce, but traditional market days can still be unpredictable. Shoppers may travel to a market only to find a favorite item sold out, while growers lack a simple way to publish current stock and collect pre-orders in advance.</p>
            <p class="text-muted">MarketLink closes that gap. It gives growers a lightweight space to share availability and pickup details, and gives neighbors an easy way to plan a better basket before they leave home.</p>
            <div class="trust-row">
                <span><?= icon('check') ?> Clear weekly stock</span>
                <span><?= icon('check') ?> No delivery confusion</span>
                <span><?= icon('check') ?> Community first</span>
            </div>
        </div>
        <div class="card card-pad reveal reveal-delay-1" style="background:linear-gradient(145deg,var(--green-950),var(--green-800));color:#fff;overflow:hidden;position:relative">
            <span class="pill pill-lime">Our promise</span>
            <p style="margin:28px 0 10px;font-size:clamp(1.6rem,4vw,2.5rem);font-weight:800;line-height:1.15;letter-spacing:-.04em">“Make every market visit feel informed, calm, and connected.”</p>
            <p style="color:rgba(255,255,255,.65)">We connect people to local growers without pretending the app replaces the joy of meeting them in person.</p>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:34px">
                <div class="card" style="padding:16px;text-align:center;border-color:rgba(255,255,255,.1);background:rgba(255,255,255,.08);color:#fff"><strong style="font-size:1.5rem;display:block">100%</strong><small>pickup focused</small></div>
                <div class="card" style="padding:16px;text-align:center;border-color:rgba(255,255,255,.1);background:rgba(255,255,255,.08);color:#fff"><strong style="font-size:1.5rem;display:block">Local</strong><small>grower network</small></div>
                <div class="card" style="padding:16px;text-align:center;border-color:rgba(255,255,255,.1);background:rgba(255,255,255,.08);color:#fff"><strong style="font-size:1.5rem;display:block">Simple</strong><small>honest pricing</small></div>
            </div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">What we value</span><h2>Designed around real market days.</h2></div>
            <p>Every feature answers a practical question a shopper or farmer may face during the week.</p>
        </div>
        <div class="feature-grid">
            <article class="card feature-card"><span class="feature-icon"><?= icon('leaf') ?></span><h3>Local by default</h3><p>Profiles, weekly stock, pickup points, and market schedules put nearby producers first.</p></article>
            <article class="card feature-card reveal reveal-delay-1"><span class="feature-icon"><?= icon('clock') ?></span><h3>Time respected</h3><p>Useful availability and pickup windows reduce wasted trips for busy families and growers.</p></article>
            <article class="card feature-card reveal reveal-delay-2"><span class="feature-icon"><?= icon('shield') ?></span><h3>Trust through clarity</h3><p>Role-based access, order history, ratings, and admin moderation keep the marketplace dependable.</p></article>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="cta-panel reveal">
            <div><h2>Ready to meet your market?</h2><p>Browse what is fresh this week, or bring your farm to the neighborhood.</p></div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;position:relative;z-index:1">
                <a class="btn btn-lime btn-lg" href="<?= e(url('explore')) ?>">Explore produce <?= icon('arrow-right') ?></a>
                <a class="btn btn-lg" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.2)" href="<?= e(url('register', ['role' => 'farmer'])) ?>">Join as farmer</a>
            </div>
        </div>
    </div>
</section>
