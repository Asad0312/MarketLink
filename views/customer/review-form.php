<?php $pageTitle = page_title('Write a review'); $page = 'orders'; ?>
<div class="dashboard-head reveal"><div><a class="text-green" style="font-size:.8rem;font-weight:750" href="<?= e(url('order',['id'=>$item['order_id']])) ?>">← Back to order</a><h1 style="margin-top:7px">Review <?= e($item['product_name']) ?></h1><p>Your review is public and helps neighbors shop with confidence.</p></div></div>
<div class="narrow">
<form class="card card-pad reveal reveal-delay-1" method="post" action="<?= e(url('review-submit')) ?>">
<?= csrf_field() ?><input type="hidden" name="order_item_id" value="<?= (int)$item['id'] ?>">
<div style="display:flex;align-items:center;gap:13px;padding:16px;border-radius:13px;background:var(--surface-soft);margin-bottom:22px"><span class="farmer-avatar"><?= e(initials($item['stall_name'])) ?></span><div><strong style="display:block;color:var(--green-950)"><?= e($item['product_name']) ?></strong><small class="text-muted">Collected from <?= e($item['stall_name']) ?></small></div></div>
<div class="form-group"><label>Your rating</label><div class="stars-input"><input id="rating5" type="radio" name="rating" value="5" required><label for="rating5" title="5 stars">★</label><input id="rating4" type="radio" name="rating" value="4"><label for="rating4" title="4 stars">★</label><input id="rating3" type="radio" name="rating" value="3"><label for="rating3" title="3 stars">★</label><input id="rating2" type="radio" name="rating" value="2"><label for="rating2" title="2 stars">★</label><input id="rating1" type="radio" name="rating" value="1"><label for="rating1" title="1 star">★</label></div></div>
<div class="form-group"><label for="review_comment">Review comment</label><textarea id="review_comment" name="comment" maxlength="800" minlength="4" required placeholder="How was the quality, freshness, packaging, and pickup experience?"></textarea><p class="field-help">Please be specific and respectful. Reviews cannot be edited by administrators except for moderation.</p></div>
<button class="btn btn-lg" type="submit">Publish review <?= icon('arrow-right') ?></button>
</form>
</div>
