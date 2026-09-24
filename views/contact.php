<?php $pageTitle = page_title('Contact us'); $page = 'contact'; ?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumbs"><a href="<?= e(url('home')) ?>">Home</a><span>/</span><span>Contact</span></div>
        <span class="eyebrow">We are here to help</span>
        <h1>Let’s keep the conversation growing.</h1>
        <p>Questions about an order, a market day, or joining as a farmer? Reach the MarketLink team.</p>
    </div>
</section>

<section class="section-sm">
    <div class="container detail-grid">
        <div class="card card-pad reveal">
            <span class="eyebrow">Send a message</span>
            <h2 style="margin:0 0 8px;color:var(--green-950);font-size:1.8rem">Contact the team</h2>
            <p class="text-muted mb-3">We usually reply within one working day.</p>
            <form method="post" action="<?= e(url('contact-send')) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group"><label for="contact_name">Your name</label><input id="contact_name" name="name" type="text" value="<?= e(old('name')) ?>" maxlength="80" required></div>
                    <div class="form-group"><label for="contact_email">Email address</label><input id="contact_email" name="email" type="email" value="<?= e(old('email')) ?>" maxlength="120" required></div>
                    <div class="form-group span-full"><label for="contact_subject">Subject</label><select id="contact_subject" name="subject" required><option>General question</option><option>Order support</option><option>Farmer support</option><option>Report a concern</option></select></div>
                    <div class="form-group span-full"><label for="contact_message">Message</label><textarea id="contact_message" name="message" maxlength="1500" placeholder="How can we help?" required><?= e(old('message')) ?></textarea></div>
                </div>
                <button class="btn btn-lg" type="submit">Send message <?= icon('arrow-right') ?></button>
            </form>
        </div>
        <div>
            <div class="card card-pad mb-3 reveal reveal-delay-1">
                <span class="eyebrow">Visit or call</span>
                <p class="text-muted">MarketLink Community Desk<br>23 Fresh Street, Lahore<br>Punjab, Pakistan</p>
                <div class="choice-grid">
                    <a class="btn btn-light btn-sm" href="tel:+923001234567">+92 300 123 4567</a>
                    <a class="btn btn-light btn-sm" href="mailto:hello@marketlink.local">Email us</a>
                </div>
            </div>
            <div class="card map-card reveal reveal-delay-2">
                <iframe title="MarketLink office location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.openstreetmap.org/export/embed.html?bbox=74.329%2C31.544%2C74.371%2C31.574&amp;layer=mapnik&amp;marker=31.559,74.350"></iframe>
            </div>
        </div>
    </div>
</section>
