<?php

declare(strict_types=1);

function route_customer_page(string $page): void
{
    $user = require_role('customer');
    $pdo = db();

    if ($page === 'customer-dashboard') {
        $stats = [
            'active' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE customer_id={$user['id']} AND status IN ('placed','accepted','ready')")->fetchColumn(),
            'completed' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE customer_id={$user['id']} AND status='completed'")->fetchColumn(),
            'favorites' => (int) $pdo->query("SELECT COUNT(*) FROM favorites WHERE user_id={$user['id']}")->fetchColumn(),
            'unread' => (int) $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id={$user['id']} AND is_read=0")->fetchColumn(),
        ];
        $orders = $pdo->prepare("SELECT o.*,fp.stall_name,m.name AS market_name FROM orders o JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id WHERE o.customer_id=? ORDER BY o.created_at DESC LIMIT 5");
        $orders->execute([$user['id']]);
        $recentOrders = $orders->fetchAll();
        $notifications = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5');
        $notifications->execute([$user['id']]);
        $recentNotifications = $notifications->fetchAll();
        $recommendations = $pdo->query("SELECT p.id,p.name,p.description,p.price_cents/100.0 AS price,p.unit,p.stock_quantity,p.image,p.status,c.name AS category_name,f.id AS farmer_id,fp.stall_name AS farmer_name,(SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') average_rating FROM products p JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id WHERE p.status='active' AND f.status='approved' AND c.status='active' ORDER BY (SELECT COUNT(*) FROM favorites fav WHERE fav.item_type='product' AND fav.item_id=p.id) DESC,p.updated_at DESC LIMIT 4");
        $dashboardPage = $page;
        render_dashboard_view('customer/dashboard', compact('stats', 'recentOrders', 'recentNotifications', 'recommendations', 'dashboardPage'));
        return;
    }

    if (in_array($page, ['cart', 'checkout'], true)) {
        $query = $pdo->prepare(
            "SELECT ci.id,ci.quantity,p.id product_id,p.name,p.description,p.price_cents,p.price_cents/100.0 AS price,p.unit,p.stock_quantity,p.image,p.status,
                    f.id farmer_id,fp.stall_name,fm.pickup_instructions
             FROM cart_items ci JOIN products p ON p.id=ci.product_id JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id AND c.status='active'
             LEFT JOIN farmer_markets fm ON fm.farmer_id=f.id AND fm.status='active'
             WHERE ci.user_id=? ORDER BY f.id,p.name"
        );
        $query->execute([$user['id']]);
        $cartItems = $query->fetchAll();
        $farmerGroups = [];
        $farmerDetails = [];
        foreach ($cartItems as $item) {
            $farmerId = (int) $item['farmer_id'];
            $farmerGroups[$farmerId][] = $item;
            if (isset($farmerDetails[$farmerId])) continue;
            $farmerQuery = $pdo->prepare("SELECT ps.*,m.name AS market_name FROM pickup_slots ps JOIN markets m ON m.id=ps.market_id AND m.status='active' JOIN users fu ON fu.id=ps.farmer_id AND fu.status='approved' JOIN farmer_markets fm ON fm.farmer_id=ps.farmer_id AND fm.market_id=ps.market_id AND fm.status='active' WHERE ps.farmer_id=? AND ps.status='open' AND ps.cutoff_at>NOW() AND ps.booked_count<ps.capacity ORDER BY ps.pickup_date,ps.start_time LIMIT 12");
            $farmerQuery->execute([$farmerId]);
            $farmerDetails[$farmerId] = $farmerQuery->fetchAll();
        }
        $oldSlots = (array) old('slots', []);
        $subtotal = 0;
        foreach ($cartItems as $item) $subtotal += (int)$item['price_cents'] * (int)$item['quantity'];
        $errors = pull_errors();
        render_view('customer/cart', compact('cartItems', 'farmerGroups', 'farmerDetails', 'subtotal', 'errors', 'oldSlots'));
        return;
    }

    if ($page === 'orders') {
        $status = (string) request_value('status', '');
        $allowed = ['placed', 'accepted', 'ready', 'completed', 'declined', 'cancelled'];
        $where = 'o.customer_id=?';
        $params = [$user['id']];
        if (in_array($status, $allowed, true)) { $where .= ' AND o.status=?'; $params[] = $status; }
        $query = $pdo->prepare("SELECT o.*,fp.stall_name,m.name AS market_name,(SELECT SUM(quantity) FROM order_items WHERE order_id=o.id) item_count FROM orders o JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id WHERE $where ORDER BY o.created_at DESC");
        $query->execute($params);
        $orders = $query->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('customer/orders', compact('orders', 'status', 'dashboardPage'));
        return;
    }

    if ($page === 'order') {
        $id = get_int('id');
        $query = $pdo->prepare("SELECT o.*,u.name AS customer_name,u.email AS customer_email,u.phone AS customer_phone,fp.stall_name,m.name AS market_name,m.address AS market_address,ps.pickup_date,ps.start_time,ps.end_time FROM orders o JOIN users u ON u.id=o.customer_id JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id JOIN pickup_slots ps ON ps.id=o.pickup_slot_id WHERE o.id=? AND o.customer_id=? LIMIT 1");
        $query->execute([$id, $user['id']]);
        $order = $query->fetch();
        if (!$order) { http_response_code(404); render_view('errors'); return; }
        $itemQuery = $pdo->prepare('SELECT oi.*,p.image,p.status AS product_status,(SELECT r.id FROM reviews r WHERE r.order_item_id=oi.id) review_id FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? ORDER BY oi.id');
        $itemQuery->execute([$id]);
        $orderItems = $itemQuery->fetchAll();
        $eventQuery = $pdo->prepare('SELECT oe.*,u.name AS actor_name FROM order_events oe JOIN users u ON u.id=oe.actor_id WHERE oe.order_id=? ORDER BY oe.created_at DESC,oe.id DESC');
        $eventQuery->execute([$id]);
        $orderEvents = $eventQuery->fetchAll();
        $dashboardPage = 'orders';
        render_dashboard_view('customer/order', compact('order', 'orderItems', 'orderEvents', 'dashboardPage'));
        return;
    }

    if ($page === 'favorites') {
        $farmerQuery = $pdo->prepare("SELECT u.id,fp.stall_name,fp.description,(SELECT COUNT(*) FROM products p WHERE p.farmer_id=u.id AND p.status='active') product_count,(SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.farmer_id=u.id AND r.status='published') average_rating FROM favorites fav JOIN users u ON u.id=fav.item_id JOIN farmer_profiles fp ON fp.user_id=u.id WHERE fav.user_id=? AND fav.item_type='farmer' AND u.status='approved' ORDER BY fav.created_at DESC");
        $farmerQuery->execute([$user['id']]);
        $favoriteFarmers = $farmerQuery->fetchAll();
        $productQuery = $pdo->prepare("SELECT p.id,p.name,p.description,p.price_cents/100.0 AS price,p.unit,p.stock_quantity,p.image,p.status,c.name AS category_name,f.id AS farmer_id,fp.stall_name AS farmer_name,(SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') average_rating FROM favorites fav JOIN products p ON p.id=fav.item_id JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id WHERE fav.user_id=? AND fav.item_type='product' AND p.status='active' AND c.status='active' ORDER BY fav.created_at DESC");
        $productQuery->execute([$user['id']]);
        $favoriteProducts = $productQuery->fetchAll();
        $marketQuery = $pdo->prepare("SELECT m.id,m.name,m.description,m.address,m.latitude,m.longitude,m.map_provider,m.map_url,m.open_time,m.close_time,m.status,m.created_at,m.updated_at,MAX(fav.created_at) AS favorite_created_at,GROUP_CONCAT(md.day_of_week) day_values FROM favorites fav JOIN markets m ON m.id=fav.item_id LEFT JOIN market_days md ON md.market_id=m.id WHERE fav.user_id=? AND fav.item_type='market' AND m.status='active' GROUP BY m.id,m.name,m.description,m.address,m.latitude,m.longitude,m.map_provider,m.map_url,m.open_time,m.close_time,m.status,m.created_at,m.updated_at ORDER BY favorite_created_at DESC");
        $marketQuery->execute([$user['id']]);
        $favoriteMarkets = $marketQuery->fetchAll();
        foreach ($favoriteMarkets as &$market) $market['day_label'] = public_days_to_label($market['day_values']);
        unset($market);
        $dashboardPage = $page;
        render_dashboard_view('customer/favorites', compact('favoriteFarmers', 'favoriteProducts', 'favoriteMarkets', 'dashboardPage'));
        return;
    }

    if ($page === 'review-form') {
        $itemId = get_int('item');
        $query = $pdo->prepare("SELECT oi.*,o.customer_id,o.status order_status,p.name product_name,fp.stall_name FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id JOIN farmer_profiles fp ON fp.user_id=p.farmer_id WHERE oi.id=? LIMIT 1");
        $query->execute([$itemId]);
        $item = $query->fetch();
        if (!$item || (int)$item['customer_id'] !== (int)$user['id'] || $item['order_status'] !== 'completed') { http_response_code(403); render_view('errors'); return; }
        $exists = $pdo->prepare('SELECT 1 FROM reviews WHERE order_item_id=?');
        $exists->execute([$itemId]);
        if ($exists->fetchColumn()) { flash('info', 'This item was already reviewed.'); redirect_to('order', ['id' => $item['order_id']]); }
        $dashboardPage = 'orders';
        render_dashboard_view('customer/review-form', compact('item', 'dashboardPage'));
        return;
    }

    http_response_code(404);
    render_view('errors');
}

function route_profile_page(): void
{
    $user = require_auth();
    $pdo = db();
    if ($user['role'] === 'farmer') {
        $profileQuery = $pdo->prepare('SELECT * FROM farmer_profiles WHERE user_id=?');
        $profileQuery->execute([$user['id']]);
        $farmerProfile = $profileQuery->fetch();
        $marketQuery = $pdo->prepare("SELECT fm.market_id,fm.stall_label,m.name FROM farmer_markets fm JOIN markets m ON m.id=fm.market_id WHERE fm.farmer_id=? AND fm.status='active' LIMIT 1");
        $marketQuery->execute([$user['id']]);
        $selectedMarket = $marketQuery->fetch();
        $markets = $pdo->query("SELECT id,name FROM markets WHERE status='active' ORDER BY name")->fetchAll();
        $dayQuery = $pdo->prepare('SELECT day_of_week FROM farmer_market_days WHERE user_id=? ORDER BY day_of_week');
        $dayQuery->execute([$user['id']]);
        $operatingDays = array_map('intval', $dayQuery->fetchAll(PDO::FETCH_COLUMN));
        $dashboardPage = 'profile';
        render_dashboard_view('profile', compact('farmerProfile', 'markets', 'selectedMarket', 'operatingDays', 'dashboardPage'));
        return;
    }

    $dashboardPage = $user['role'] === 'customer' ? 'profile' : 'admin-dashboard';
    render_dashboard_view('profile', compact('dashboardPage'));
}
