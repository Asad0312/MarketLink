<?php
$page = 'login';
$pageTitle = page_title('Sign in');
$selectedNext = safe_return_path((string) request_value('next', ''));
?>
<section class="auth-page">
    <div class="narrow auth-intro reveal">
        <span class="eyebrow">Welcome back</span>
        <h1>Your market basket is waiting.</h1>
        <p>Sign in to pre-order fresh produce, manage pickup, or keep your weekly farmer inventory up to date.</p>
        <div class="auth-benefits">
            <div class="auth-benefit"><span><?= icon('leaf') ?></span>Fresh stock from nearby growers</div>
            <div class="auth-benefit"><span><?= icon('clock') ?></span>Pickup slots that suit your week</div>
            <div class="auth-benefit"><span><?= icon('shield') ?></span>One secure account for every role</div>
        </div>
    </div>

    <div class="card auth-card reveal reveal-delay-1">
        <span class="eyebrow">Account access</span>
        <h2>Sign in to MarketLink</h2>
        <p>Use the email and password connected to your account.</p>

        <form method="post" action="<?= e(url('login')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($selectedNext) ?>">
            <div class="form-group">
                <label for="login_email">Email address</label>
                <input id="login_email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="120" placeholder="you@example.com" required autofocus>
            </div>
            <div class="form-group">
                <label for="login_password">Password</label>
                <div class="password-wrap">
                    <input id="login_password" name="password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-password-toggle="login_password" aria-label="Show password"><?= icon('eye') ?></button>
                </div>
            </div>
            <label class="check-row mb-3"><input type="checkbox" name="remember" value="1"><span>Keep me signed in on this device</span></label>
            <button class="btn btn-lg btn-block" type="submit">Sign in <?= icon('arrow-right') ?></button>
        </form>

        <div class="divider">New to the market?</div>
        <div class="choice-grid" style="display:grid;grid-template-columns:1fr 1fr">
            <a class="btn btn-light" href="<?= e(url('register', ['role' => 'customer'])) ?>">Join as customer</a>
            <a class="btn btn-light" href="<?= e(url('register', ['role' => 'farmer'])) ?>">Sell as farmer</a>
        </div>
    </div>
</section>
