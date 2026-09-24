<?php
$errorCode = http_response_code() ?: 404;
$pageTitle = page_title('Error ' . $errorCode);
$page = 'error';
$copy = match ($errorCode) {
    403 => ['That area is not in your basket.', 'Your account does not have permission to view this page.'],
    404 => ['We could not find that page.', 'The link may be old, or the item may no longer be available.'],
    419 => ['Your session took a break.', 'Refresh the page and submit the form again.'],
    default => ['Something went off the garden path.', 'Please return to the homepage and try again.'],
};
?>
<section class="not-found">
    <div class="container">
        <strong><?= e($errorCode) ?></strong>
        <h1><?= e($copy[0]) ?></h1>
        <p class="text-muted"><?= e($copy[1]) ?></p>
        <div style="display:flex;justify-content:center;gap:10px;margin-top:22px;flex-wrap:wrap">
            <a class="btn" href="<?= e(url('home')) ?>">Back to home</a>
            <a class="btn btn-light" href="<?= e(url('explore')) ?>">Explore produce</a>
        </div>
    </div>
</section>
