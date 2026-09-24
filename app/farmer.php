<?php

declare(strict_types=1);

function route_role_page(string $page): void
{
    if (str_starts_with($page, 'admin-')) {
        route_admin_page($page);
        return;
    }

    $user = require_role('farmer');
    $pdo = db();

    if ($page === 'farmer-dashboard') {
        if ($user['status'] !== 'approved') {
            $dashboardPage = $page;
            render_dashboard_view('farmer/pending', compact('dashboardPage'));
            return;
        }
        $stats = [
            'orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE farmer_id={$user['id']}")->fetchColumn(),
            'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE farmer_id={$user['id']} AND status='placed'")->fetchColumn(),
            'products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE farmer_id={$user['id']} AND status='active'")->fetchColumn(),
            'booked' => (int) $pdo->query("SELECT COALESCE(SUM(subtotal_cents),0) FROM orders WHERE farmer_id={$user['id']} AND status IN ('accepted','ready','completed')")->fetchColumn(),
        ];
        $orderQuery = $pdo->prepare("SELECT o.*,u.name AS customer_name,fp.stall_name,m.name AS market_name FROM orders o JOIN users u ON u.id=o.customer_id JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id WHERE o.farmer_id=? ORDER BY CASE o.status WHEN 'placed' THEN 0 WHEN 'accepted' THEN 1 WHEN 'ready' THEN 2 ELSE 3 END,o.created_at DESC LIMIT 7");
        $orderQuery->execute([$user['id']]);
        $recentOrders = $orderQuery->fetchAll();
        $lowStock = $pdo->prepare("SELECT p.*,p.price_cents/100.0 AS price,c.name AS category_name,fp.stall_name AS farmer_name FROM products p JOIN categories c ON c.id=p.category_id JOIN farmer_profiles fp ON fp.user_id=p.farmer_id WHERE p.farmer_id=? AND p.status='active' AND p.stock_quantity<=5 ORDER BY p.stock_quantity,p.name LIMIT 6");
        $lowStock->execute([$user['id']]);
        $lowStockProducts = $lowStock->fetchAll();
        $notifications = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 6');
        $notifications->execute([$user['id']]);
        $recentNotifications = $notifications->fetchAll();
        $reviews = $pdo->prepare("SELECT r.*,u.name AS customer_name,p.name AS product_name FROM reviews r JOIN users u ON u.id=r.customer_id JOIN products p ON p.id=r.product_id WHERE r.farmer_id=? AND r.status='published' ORDER BY r.created_at DESC LIMIT 4");
        $reviews->execute([$user['id']]);
        $recentReviews = $reviews->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('farmer/dashboard', compact('stats', 'recentOrders', 'lowStockProducts', 'recentNotifications', 'recentReviews', 'dashboardPage'));
        return;
    }

    if ($user['status'] !== 'approved') {
        flash('warning', 'Your farmer account must be approved before you can manage market data.');
        redirect_to('farmer-dashboard');
    }

    if ($page === 'farmer-products') {
        $products = $pdo->prepare("SELECT p.*,p.price_cents/100.0 AS price,c.name AS category_name,(SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') average_rating FROM products p JOIN categories c ON c.id=p.category_id WHERE p.farmer_id=? ORDER BY p.updated_at DESC");
        $products->execute([$user['id']]);
        $productRows = $products->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('farmer/products', compact('productRows', 'dashboardPage'));
        return;
    }

    if ($page === 'farmer-product-form') {
        $categories = $pdo->query("SELECT id,name FROM categories WHERE status='active' ORDER BY sort_order,name")->fetchAll();
        $product = null;
        $id = get_int('id');
        if ($id > 0) {
            $query = $pdo->prepare('SELECT * FROM products WHERE id=? AND farmer_id=? LIMIT 1');
            $query->execute([$id, $user['id']]);
            $product = $query->fetch();
            if (!$product) { http_response_code(404); render_view('errors'); return; }
        }
        $dashboardPage = 'farmer-products';
        render_dashboard_view('farmer/product-form', compact('product', 'categories', 'dashboardPage'));
        return;
    }

    if ($page === 'farmer-orders') {
        $status = (string) request_value('status', '');
        $allowed = ['placed', 'accepted', 'ready', 'completed', 'declined', 'cancelled'];
        $where = 'o.farmer_id=?'; $params = [$user['id']];
        if (in_array($status, $allowed, true)) { $where .= ' AND o.status=?'; $params[] = $status; }
        $query = $pdo->prepare("SELECT o.*,u.name AS customer_name,u.phone AS customer_phone,fp.stall_name,m.name AS market_name,(SELECT SUM(quantity) FROM order_items WHERE order_id=o.id) item_count FROM orders o JOIN users u ON u.id=o.customer_id JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id WHERE $where ORDER BY CASE o.status WHEN 'placed' THEN 0 WHEN 'accepted' THEN 1 WHEN 'ready' THEN 2 ELSE 3 END,o.created_at DESC");
        $query->execute($params);
        $orders = $query->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('farmer/orders', compact('orders', 'status', 'dashboardPage'));
        return;
    }

    if ($page === 'farmer-order') {
        $id = get_int('id');
        $query = $pdo->prepare("SELECT o.*,u.name AS customer_name,u.email AS customer_email,u.phone AS customer_phone,u.address AS customer_address,fp.stall_name,m.name AS market_name,m.address AS market_address,ps.pickup_date,ps.start_time,ps.end_time FROM orders o JOIN users u ON u.id=o.customer_id JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id JOIN pickup_slots ps ON ps.id=o.pickup_slot_id WHERE o.id=? AND o.farmer_id=? LIMIT 1");
        $query->execute([$id, $user['id']]);
        $order = $query->fetch();
        if (!$order) { http_response_code(404); render_view('errors'); return; }
        $itemQuery = $pdo->prepare('SELECT oi.*,p.image FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
        $itemQuery->execute([$id]);
        $orderItems = $itemQuery->fetchAll();
        $events = $pdo->prepare('SELECT oe.*,u.name AS actor_name FROM order_events oe JOIN users u ON u.id=oe.actor_id WHERE oe.order_id=? ORDER BY oe.created_at DESC,oe.id DESC');
        $events->execute([$id]);
        $orderEvents = $events->fetchAll();
        $dashboardPage = 'farmer-orders';
        render_dashboard_view('farmer/order', compact('order', 'orderItems', 'orderEvents', 'dashboardPage'));
        return;
    }

    if ($page === 'farmer-slots') {
        $slots = $pdo->prepare("SELECT ps.*,m.name AS market_name,m.address AS market_address,(SELECT COUNT(*) FROM orders o WHERE o.pickup_slot_id=ps.id AND o.status NOT IN ('cancelled','declined')) AS actual_orders FROM pickup_slots ps JOIN markets m ON m.id=ps.market_id WHERE ps.farmer_id=? AND ps.pickup_date>=(CURDATE()-INTERVAL 1 DAY) ORDER BY ps.pickup_date,ps.start_time LIMIT 60");
        $slots->execute([$user['id']]);
        $pickupSlots = $slots->fetchAll();
        $profile = $pdo->prepare('SELECT * FROM farmer_profiles WHERE user_id=?');
        $profile->execute([$user['id']]);
        $farmerProfile = $profile->fetch();
        $dayQuery = $pdo->prepare('SELECT day_of_week FROM farmer_market_days WHERE user_id=? ORDER BY day_of_week');
        $dayQuery->execute([$user['id']]);
        $operatingDays = array_map('intval', $dayQuery->fetchAll(PDO::FETCH_COLUMN));
        $dashboardPage = $page;
        render_dashboard_view('farmer/slots', compact('pickupSlots', 'farmerProfile', 'operatingDays', 'dashboardPage'));
        return;
    }

    if ($page === 'farmer-insights') {
        $summary = $pdo->prepare("SELECT COUNT(*) total_orders,COALESCE(SUM(subtotal_cents),0) booked_value,COALESCE(SUM(CASE WHEN status='completed' THEN subtotal_cents ELSE 0 END),0) completed_value FROM orders WHERE farmer_id=? AND status NOT IN ('cancelled','declined')");
        $summary->execute([$user['id']]);
        $insightSummary = $summary->fetch();
        $best = $pdo->prepare("SELECT oi.product_name,SUM(oi.quantity) units_sold,SUM(oi.line_total_cents) order_value FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.farmer_id=? AND o.status IN ('accepted','ready','completed') GROUP BY oi.product_id,oi.product_name ORDER BY units_sold DESC,order_value DESC LIMIT 8");
        $best->execute([$user['id']]);
        $bestProducts = $best->fetchAll();
        $monthly = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%m') month,COUNT(*) orders,COALESCE(SUM(subtotal_cents),0) value FROM orders WHERE farmer_id=? AND status IN ('accepted','ready','completed') AND created_at>=(NOW()-INTERVAL 6 MONTH) GROUP BY month ORDER BY month");
        $monthly->execute([$user['id']]);
        $monthlyData = $monthly->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('farmer/insights', compact('insightSummary', 'bestProducts', 'monthlyData', 'dashboardPage'));
        return;
    }

    http_response_code(404); render_view('errors');
}
