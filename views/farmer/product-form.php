<?php
$pageTitle=page_title($product?'Edit product':'Add product'); $page='farmer-products'; $errors=pull_errors();
$name=old('name',$product['name']??''); $description=old('description',$product['description']??''); $category=(int)old('category_id',$product['category_id']??0); $price=old('price',isset($product['price_cents'])?$product['price_cents']/100:''); $unit=old('unit',$product['unit']??'piece'); $stock=old('stock_quantity',$product['stock_quantity']??0); $status=old('status',$product['status']??'active');
?>
<div class="dashboard-head reveal"><div><a class="text-green" style="font-size:.8rem;font-weight:750" href="<?= e(url('farmer-products')) ?>">← Back to products</a><h1 style="margin-top:7px"><?= $product ? 'Edit product listing' : 'Add a product' ?></h1><p>Accurate stock and pricing help customers plan with confidence.</p></div></div>
<?php if($errors): ?><div class="form-errors"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="narrow">
<form class="card card-pad reveal reveal-delay-1" method="post" action="<?= e(url('farmer-product-save')) ?>" enctype="multipart/form-data">
<?= csrf_field() ?><?php if($product): ?><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><?php endif; ?>
<div class="form-grid">
<div class="form-group span-2"><label for="product_name">Product name *</label><input id="product_name" name="name" type="text" value="<?= e($name) ?>" maxlength="100" placeholder="Organic Tomatoes" required autofocus></div>
<div class="form-group"><label for="product_category">Category *</label><select id="product_category" name="category_id" required><option value="">Choose category</option><?php foreach($categories as $item): ?><option value="<?= (int)$item['id'] ?>" <?= $category===(int)$item['id']?'selected':'' ?>><?= e($item['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="product_unit">Unit *</label><input id="product_unit" name="unit" type="text" value="<?= e($unit) ?>" maxlength="20" placeholder="kg, bag, dozen" required></div>
<div class="form-group"><label for="product_price">Price (Rs) *</label><div class="input-prefix"><span>Rs</span><input id="product_price" name="price" type="number" min="0.01" step="0.01" value="<?= e($price) ?>" required></div></div>
<div class="form-group"><label for="product_stock">Available quantity *</label><input id="product_stock" name="stock_quantity" type="number" min="0" step="1" value="<?= e($stock) ?>" required><p class="field-help">Set to zero when sold out. Existing order reservations are already reflected in this number.</p></div>
<div class="form-group span-2"><label for="product_description">Description</label><textarea id="product_description" name="description" maxlength="1200" placeholder="Freshness, variety, growing method, pack size, or pickup suitability."><?= e($description) ?></textarea></div>
<div class="form-group"><label for="product_status">Availability</label><select id="product_status" name="status" <?= ($product['status']??'')==='hidden'?'disabled':'' ?>><option value="active" <?= $status==='active'?'selected':'' ?>>Active listing</option><option value="unavailable" <?= $status==='unavailable'?'selected':'' ?>>Temporarily unavailable</option><option value="sold_out" <?= $status==='sold_out'?'selected':'' ?>>Sold out</option><?php if(($product['status']??'')==='hidden'): ?><option value="hidden" selected>Hidden by administrator</option><?php endif; ?></select><?php if(($product['status']??'')==='hidden'): ?><input type="hidden" name="status" value="hidden"><?php endif; ?></div>
<div class="form-group"><label for="product_image">Product image</label><input id="product_image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><p class="field-help">JPG, PNG, WEBP, or GIF up to 2 MB. Optional.</p><?php if($product && product_image_url($product['image'])): ?><img src="<?= e(product_image_url($product['image'])) ?>" alt="Current <?= e($product['name']) ?>" style="width:100px;height:70px;object-fit:cover;border-radius:10px;margin-top:8px"><?php endif; ?></div>
</div>
<button class="btn btn-lg" type="submit"><?= $product ? 'Save product changes' : 'Add to weekly stock' ?> <?= icon('check') ?></button>
<a class="btn btn-light btn-lg" href="<?= e(url('farmer-products')) ?>">Cancel</a>
</form>
</div>
