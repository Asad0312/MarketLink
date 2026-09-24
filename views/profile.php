<?php
$user = current_user();
$errors = pull_errors();
$isFarmer = $user['role'] === 'farmer';
$pageTitle = page_title('My profile');
$page = 'profile';
?>
<div class="dashboard-head reveal"><div><span class="eyebrow">Account settings</span><h1>Your profile</h1><p>Keep contact, pickup, and business information accurate.</p></div><?= status_pill($user['status']) ?></div>
<?php if ($errors): ?><div class="form-errors"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= e(url('profile-update')) ?>">
<?= csrf_field() ?>
<div class="detail-grid">
<div class="content-card card">
<div class="content-head"><div><h2><?= $isFarmer ? 'Farm profile' : 'Personal details' ?></h2><p>Fields marked with an asterisk are required.</p></div></div>
<div class="form-grid">
<div class="form-group span-2"><label for="profile_name">Full name *</label><input id="profile_name" name="name" type="text" value="<?= e(old('name',$user['name'])) ?>" autocomplete="name" maxlength="80" required></div>
<div class="form-group"><label for="profile_email">Email address</label><input id="profile_email" type="email" value="<?= e($user['email']) ?>" disabled><p class="field-help">Email changes require support verification.</p></div>
<div class="form-group"><label for="profile_phone">Contact number *</label><input id="profile_phone" name="phone" type="tel" value="<?= e(old('phone',$user['phone'])) ?>" autocomplete="tel" maxlength="25" required></div>
<div class="form-group span-2"><label for="profile_address">Address *</label><textarea id="profile_address" name="address" autocomplete="street-address" maxlength="500" required><?= e(old('address',$user['address'])) ?></textarea><?php if (!$isFarmer): ?><p class="field-help">This pickup address is copied into new orders.</p><?php endif; ?></div>
<?php if ($isFarmer): ?>
<div class="form-group span-2"><label for="stall_name">Stall or business name *</label><input id="stall_name" name="stall_name" type="text" value="<?= e(old('stall_name',$farmerProfile['stall_name'] ?? '')) ?>" maxlength="100" required></div>
<div class="form-group span-2"><label for="profile_description">Public introduction</label><textarea id="profile_description" name="description" maxlength="800"><?= e(old('description',$farmerProfile['description'] ?? '')) ?></textarea></div>
<div class="form-group"><label for="profile_market">Primary pickup market *</label><select id="profile_market" name="market_id" required><option value="">Choose market</option><?php foreach($markets as $market): ?><option value="<?= (int)$market['id'] ?>" <?= (int)($selectedMarket['market_id'] ?? 0) === (int)$market['id'] ? 'selected' : '' ?>><?= e($market['name']) ?></option><?php endforeach; ?></select></div>
<div></div>
<div class="form-group span-2"><label>Your market operating days</label><div class="choice-grid"><?php foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',0=>'Sun'] as $day=>$label): ?><label class="choice"><input type="checkbox" name="operating_days[]" value="<?= $day ?>" <?= in_array($day,$operatingDays,true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></div>
<div class="form-group"><label for="pickup_start">Pickup starts</label><input id="pickup_start" name="pickup_start" type="time" value="<?= e(old('pickup_start',$farmerProfile['pickup_start'] ?? '08:00')) ?>" required></div>
<div class="form-group"><label for="pickup_end">Pickup ends</label><input id="pickup_end" name="pickup_end" type="time" value="<?= e(old('pickup_end',$farmerProfile['pickup_end'] ?? '13:00')) ?>" required></div>
<div class="form-group"><label for="cutoff_time">Order cutoff</label><input id="cutoff_time" name="cutoff_time" type="time" value="<?= e(old('cutoff_time',$farmerProfile['cutoff_time'] ?? '20:00')) ?>" required></div>
<?php endif; ?>
</div>
</div>
<aside>
<div class="content-card card"><div class="content-head"><div><h2>Change password</h2><p>Leave these fields blank to keep your current password.</p></div></div><div class="form-group"><label for="new_password">New password</label><input id="new_password" name="password" type="password" minlength="8" autocomplete="new-password"></div><div class="form-group"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></div><p class="field-help">Use at least 8 characters and avoid passwords used on other sites.</p></div>
<div class="card card-pad mt-3"><span class="eyebrow">Account status</span><div class="summary-line"><span>Role</span><strong><?= e(ucfirst($user['role'])) ?></strong></div><div class="summary-line"><span>Status</span><strong><?= status_pill($user['status']) ?></strong></div><div class="summary-line"><span>Member since</span><strong><?= format_date($user['created_at']) ?></strong></div><?php if ($isFarmer && $user['status']==='pending'): ?><div class="alert alert-warning mt-3">Your profile is saved. An administrator must approve the account before products and orders can be published.</div><?php endif; ?></div>
</aside>
</div>
<button class="btn btn-lg mt-3" type="submit">Save profile changes <?= icon('check') ?></button>
</form>
