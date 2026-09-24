<?php

declare(strict_types=1);

function render_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('View not found: ' . $view);
    }
    require BASE_PATH . '/views/partials/header.php';
    require $viewFile;
    require BASE_PATH . '/views/partials/footer.php';
}

function render_dashboard_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = BASE_PATH . '/views/' . $view . '.php';
    if (!is_file($viewFile)) {
        throw new RuntimeException('Dashboard view not found: ' . $view);
    }
    require BASE_PATH . '/views/partials/header.php';
    echo '<section class="dashboard-page"><div class="container"><div class="dashboard-layout">';
    require BASE_PATH . '/views/dashboard/sidebar.php';
    echo '<div class="dashboard-main">';
    require $viewFile;
    echo '</div></div></div></section>';
    require BASE_PATH . '/views/partials/footer.php';
}

function route_request(string $page): void
{
    $page = trim($page);

    if (is_post()) {
        handle_post_action($page);
    }

    switch ($page) {
        case '':
        case 'home':
            route_public_page('home');
            return;

        case 'explore':
        case 'products':
            route_public_page('products');
            return;
        case 'product':
            route_public_page('product');
            return;
        case 'markets':
            route_public_page('markets');
            return;
        case 'market':
            route_public_page('market');
            return;
        case 'farmers':
            route_public_page('farmers');
            return;
        case 'farmer':
            route_public_page('farmer');
            return;
        case 'about':
            route_public_page('about');
            return;
        case 'contact':
            route_public_page('contact');
            return;
        case 'faq':
            route_public_page('faq');
            return;
        case 'login':
            if (is_logged_in()) redirect_to('dashboard');
            render_view('auth/login');
            return;
        case 'register':
            if (is_logged_in()) redirect_to('dashboard');
            render_view('auth/register');
            return;

        case 'dashboard':
            $user = require_auth();
            redirect_to(match ($user['role']) {
                'admin' => 'admin-dashboard',
                'farmer' => 'farmer-dashboard',
                default => 'customer-dashboard',
            });
            return;

        case 'customer-dashboard':
        case 'cart':
        case 'checkout':
        case 'orders':
        case 'order':
        case 'favorites':
        case 'review-form':
            route_customer_page($page);
            return;

        case 'profile':
            route_profile_page();
            return;

        case 'farmer-dashboard':
        case 'farmer-products':
        case 'farmer-product-form':
        case 'farmer-orders':
        case 'farmer-order':
        case 'farmer-slots':
        case 'farmer-insights':
        case 'admin-dashboard':
        case 'admin-users':
        case 'admin-markets':
        case 'admin-market-form':
        case 'admin-categories':
        case 'admin-moderation':
        case 'admin-reports':
        case 'admin-announcements':
            route_role_page($page);
            return;

        default:
            http_response_code(404);
            render_view('errors');
    }
}
