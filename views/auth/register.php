<?php
$requestedRole = (string) request_value('role', 'customer');
$role = in_array($requestedRole, ['customer', 'farmer'], true) ? $requestedRole : 'customer';
$page = 'register';
$pageTitle = page_title($role === 'farmer' ? 'Join as a farmer' : 'Create your account');
$selectedDays = array_map('intval', (array) old('operating_days', []));
?>
<section class="auth-page">
    <div class="narrow auth-intro reveal">
        <span class="eyebrow"><?= $role === 'farmer' ? 'Grow with your community' : 'A fresher way to shop' ?></span>
        <h1><?= $role === 'farmer' ? 'Put this week’s harvest on the map.' : 'Plan your basket before market day.' ?></h1>
        <p><?= $role === 'farmer'
            ? 'Create a grower profile, publish weekly stock, and receive clear pre-orders for your pickup stall.'
            : 'Discover nearby markets, reserve seasonal produce, and keep every pickup order in one calm place.' ?></p>
        <div class="auth-benefits">
            <?php if ($role === 'farmer'): ?>
                <div class="auth-benefit"><span><?= icon('store') ?></span>Your own public farm profile</div>
                <div class="auth-benefit"><span><?= icon('package') ?></span>Simple weekly stock and pricing</div>
                <div class="auth-benefit"><span><?= icon('orders') ?></span>Clear incoming pre-orders</div>
                <div class="auth-benefit"><span><?= icon('shield') ?></span>Admin approval before publishing</div>
            <?php else: ?>
                <div class="auth-benefit"><span><?= icon('search') ?></span>Search by market, day, category, or price</div>
                <div class="auth-benefit"><span><?= icon('calendar') ?></span>Choose a valid pickup window</div>
                <div class="auth-benefit"><span><?= icon('heart') ?></span>Save favorite farmers and products</div>
                <div class="auth-benefit"><span><?= icon('message') ?></span>Rate produce after collection</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card auth-card reveal reveal-delay-1">
        <span class="eyebrow">Create account</span>
        <h2><?= $role === 'farmer' ? 'Farmer registration' : 'Customer registration' ?></h2>
        <p>Already registered? <a href="<?= e(url('login')) ?>">Sign in instead</a>.</p>

        <div class="role-selector">
            <label class="role-option"><input type="radio" name="role_choice" value="customer" <?= $role === 'customer' ? 'checked' : '' ?> onclick="location.href='<?= e(url('register', ['role' => 'customer'])) ?>'"><span>🛒 I buy produce<small>Browse and pre-order</small></span></label>
            <label class="role-option"><input type="radio" name="role_choice" value="farmer" <?= $role === 'farmer' ? 'checked' : '' ?> onclick="location.href='<?= e(url('register', ['role' => 'farmer'])) ?>'"><span>🌱 I grow produce<small>List and fulfill orders</small></span></label>
        </div>

        <form method="post" action="<?= e(url('register')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="<?= e($role) ?>">
            <div class="form-grid">
                <?php if ($role === 'farmer'): ?>
                    <div class="form-group span-2"><label for="stall_name">Stall or business name</label><input id="stall_name" name="stall_name" type="text" value="<?= e(old('stall_name')) ?>" maxlength="100" placeholder="Green Valley Farm" required></div>
                    <div class="form-group span-2"><label for="name">Contact person</label><input id="name" name="name" type="text" value="<?= e(old('name')) ?>" autocomplete="name" maxlength="80" required></div>
                <?php else: ?>
                    <div class="form-group span-2"><label for="name">Full name</label><input id="name" name="name" type="text" value="<?= e(old('name')) ?>" autocomplete="name" maxlength="80" required></div>
                <?php endif; ?>

                <div class="form-group"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="120" placeholder="you@example.com" required></div>
                <div class="form-group"><label for="phone">Contact number</label><input id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" autocomplete="tel" maxlength="25" placeholder="+92 300 1234567" required></div>
                <div class="form-group span-2"><label for="address">Address</label><textarea id="address" name="address" autocomplete="street-address" maxlength="500" placeholder="House / street / area" required><?= e(old('address')) ?></textarea></div>

                <?php if ($role === 'farmer'): ?>
                    <div class="form-group span-2"><label for="description">Short public introduction</label><textarea id="description" name="description" maxlength="800" placeholder="Tell neighbors what you grow and what makes your farm special."><?= e(old('description')) ?></textarea></div>
                    <div class="form-group span-2">
                        <label>Market operating days</label>
                        <div class="choice-grid">
                            <?php foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'] as $day => $label): ?>
                                <label class="choice"><input type="checkbox" name="operating_days[]" value="<?= $day ?>" <?= in_array($day, $selectedDays, true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group"><label for="pickup_start">Pickup window starts</label><input id="pickup_start" name="pickup_start" type="time" value="<?= e(old('pickup_start', '08:00')) ?>" required></div>
                    <div class="form-group"><label for="pickup_end">Pickup window ends</label><input id="pickup_end" name="pickup_end" type="time" value="<?= e(old('pickup_end', '13:00')) ?>" required></div>
                    <div class="form-group span-2"><label for="cutoff_time">Daily order cutoff</label><input id="cutoff_time" name="cutoff_time" type="time" value="<?= e(old('cutoff_time', '20:00')) ?>" required><p class="field-help">Customers can edit placed orders until this time on the previous day.</p></div>
                <?php endif; ?>

                <div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
                <div class="form-group"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div>
                <div class="form-group span-2">
                    <label class="check-row"><input type="checkbox" name="terms" value="1" required><span>I understand that MarketLink is pickup-only and payment is made directly at collection.</span></label>
                </div>
            </div>
            <button class="btn btn-lg btn-block mt-2" type="submit">Create <?= $role === 'farmer' ? 'farmer' : 'customer' ?> account <?= icon('arrow-right') ?></button>
        </form>
    </div>
</section>
