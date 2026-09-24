<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

require BASE_PATH . '/config/app.php';
require BASE_PATH . '/includes/database.php';
require BASE_PATH . '/includes/helpers.php';
require BASE_PATH . '/app/actions.php';
require BASE_PATH . '/app/public.php';
require BASE_PATH . '/app/customer.php';
require BASE_PATH . '/app/farmer.php';
require BASE_PATH . '/app/admin.php';
require BASE_PATH . '/app/router.php';

date_default_timezone_set(APP_TIMEZONE);
db();

$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';

try {
    route_request($page);
} catch (Throwable $exception) {
    error_log('[MarketLink] ' . $exception->getMessage());
    http_response_code(500);
    if (APP_DEBUG) {
        echo '<pre style="white-space:pre-wrap;padding:24px;font:14px/1.6 ui-monospace,monospace">';
        echo e(get_class($exception) . ': ' . $exception->getMessage() . "\n\n" . $exception->getTraceAsString());
        echo '</pre>';
    } else {
        require BASE_PATH . '/views/partials/header.php';
        require BASE_PATH . '/views/errors.php';
        require BASE_PATH . '/views/partials/footer.php';
    }
}
