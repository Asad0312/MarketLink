<?php

declare(strict_types=1);

function route_admin_page(string $page): void
{
    $user = require_role('admin');
    $pdo = db();

    if ($page === 'admin-dashboard') {
        $metrics = [
            'farmers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='farmer' AND status='approved'")->fetchColumn(),
            'customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='active'")->fetchColumn(),
            'markets' => (int) $pdo->query("SELECT COUNT(*) FROM markets WHERE status='active'")->fetchColumn(),
            'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'pending_farmers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='farmer' AND status='pending'")->fetchColumn(),
            'revenue' => (int) $pdo->query("SELECT COALESCE(SUM(subtotal_cents),0) FROM orders WHERE status='completed'")->fetchColumn(),
            'messages' => (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn(),
            'moderation' => (int) $pdo->query("SELECT (SELECT COUNT(*) FROM products WHERE status='hidden') + (SELECT COUNT(*) FROM reviews WHERE status IN ('hidden','removed'))")->fetchColumn(),
        ];
        $recentUsers = $pdo->query("SELECT u.*,fp.stall_name,(SELECT COUNT(*) FROM orders o WHERE o.customer_id=u.id OR o.farmer_id=u.id) order_count FROM users u LEFT JOIN farmer_profiles fp ON fp.user_id=u.id ORDER BY u.created_at DESC LIMIT 7")->fetchAll();
        $recentOrders = $pdo->query("SELECT o.*,u.name customer_name,fp.stall_name,m.name market_name FROM orders o JOIN users u ON u.id=o.customer_id JOIN farmer_profiles fp ON fp.user_id=o.farmer_id JOIN markets m ON m.id=o.pickup_market_id ORDER BY o.created_at DESC LIMIT 7")->fetchAll();
        $topFarmers = $pdo->query("SELECT u.id,fp.stall_name,COUNT(o.id) order_count,COALESCE(SUM(o.subtotal_cents),0) order_value FROM users u JOIN farmer_profiles fp ON fp.user_id=u.id LEFT JOIN orders o ON o.farmer_id=u.id AND o.status IN ('accepted','ready','completed') WHERE u.role='farmer' GROUP BY u.id,fp.stall_name ORDER BY order_count DESC LIMIT 5")->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('admin/dashboard', compact('metrics', 'recentUsers', 'recentOrders', 'topFarmers', 'dashboardPage'));
        return;
    }

    if ($page === 'admin-users') {
        $role = (string) request_value('role', '');
        $status = (string) request_value('status', '');
        $q = mb_substr((string) request_value('q', ''), 0, 100);
        $where = ['1=1']; $params = [];
        if (in_array($role, ['customer', 'farmer', 'admin'], true)) { $where[] = 'u.role=?'; $params[] = $role; }
        if (in_array($status, ['active', 'pending', 'approved', 'suspended', 'deactivated', 'rejected'], true)) { $where[] = 'u.status=?'; $params[] = $status; }
        if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ? OR fp.stall_name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
        $query = $pdo->prepare("SELECT u.*,fp.stall_name,(SELECT COUNT(*) FROM orders o WHERE o.customer_id=u.id OR o.farmer_id=u.id) order_count FROM users u LEFT JOIN farmer_profiles fp ON fp.user_id=u.id WHERE " . implode(' AND ', $where) . ' ORDER BY u.created_at DESC');
        $query->execute($params);
        $users = $query->fetchAll();
        $markets = $pdo->query("SELECT id,name FROM markets WHERE status='active' ORDER BY name")->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('admin/users', compact('users', 'markets', 'role', 'status', 'q', 'dashboardPage'));
        return;
    }

    if ($page === 'admin-markets') {
        $markets = $pdo->query("SELECT m.*,
                    (SELECT GROUP_CONCAT(md.day_of_week) FROM market_days md WHERE md.market_id=m.id) day_values,
                    (SELECT COUNT(*) FROM farmer_markets fm WHERE fm.market_id=m.id AND fm.status='active') farmer_count,
                    (SELECT COUNT(*) FROM orders o WHERE o.pickup_market_id=m.id) order_count
             FROM markets m ORDER BY m.status,m.name")->fetchAll();
        foreach ($markets as &$market) $market['day_label'] = public_days_to_label($market['day_values']);
        unset($market);
        $dashboardPage = $page;
        render_dashboard_view('admin/markets', compact('markets', 'dashboardPage'));
        return;
    }

    if ($page === 'admin-market-form') {
        $market = null; $id = get_int('id');
        if ($id > 0) {
            $query = $pdo->prepare('SELECT * FROM markets WHERE id=?'); $query->execute([$id]); $market = $query->fetch();
            if (!$market) { http_response_code(404); render_view('errors'); return; }
            $dayQuery = $pdo->prepare('SELECT day_of_week FROM market_days WHERE market_id=?'); $dayQuery->execute([$id]); $selectedDays = array_map('intval', $dayQuery->fetchAll(PDO::FETCH_COLUMN));
        } else $selectedDays = [6];
        $errors = pull_errors();
        $dashboardPage = 'admin-markets';
        render_dashboard_view('admin/market-form', compact('market', 'selectedDays', 'errors', 'dashboardPage'));
        return;
    }

    if ($page === 'admin-categories') {
        $categories = $pdo->query("SELECT c.*,COUNT(p.id) product_count,COUNT(CASE WHEN p.status='active' THEN 1 END) active_product_count FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id,c.name,c.description,c.status,c.sort_order,c.created_at,c.updated_at ORDER BY c.sort_order,c.name")->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('admin/categories', compact('categories', 'dashboardPage'));
        return;
    }

    if ($page === 'admin-moderation') {
        $products = $pdo->query("SELECT p.*,p.price_cents/100.0 AS price,u.name farmer_name,fp.stall_name,c.name category_name FROM products p JOIN users u ON u.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=u.id JOIN categories c ON c.id=p.category_id WHERE p.status='hidden' ORDER BY p.updated_at DESC")->fetchAll();
        $activeProducts = $pdo->query("SELECT p.*,p.price_cents/100.0 AS price,u.name farmer_name,fp.stall_name,c.name category_name FROM products p JOIN users u ON u.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=u.id JOIN categories c ON c.id=p.category_id WHERE p.status IN ('active','unavailable','sold_out') ORDER BY p.updated_at DESC LIMIT 12")->fetchAll();
        $reviews = $pdo->query("SELECT r.*,u.name customer_name,fp.stall_name,p.name product_name FROM reviews r JOIN users u ON u.id=r.customer_id JOIN users f ON f.id=r.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN products p ON p.id=r.product_id WHERE r.status IN ('hidden','removed') ORDER BY r.updated_at DESC")->fetchAll();
        $activeReviews = $pdo->query("SELECT r.*,u.name customer_name,fp.stall_name,p.name product_name FROM reviews r JOIN users u ON u.id=r.customer_id JOIN users f ON f.id=r.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN products p ON p.id=r.product_id WHERE r.status='published' ORDER BY r.created_at DESC LIMIT 12")->fetchAll();
        $messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 20")->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('admin/moderation', compact('products', 'activeProducts', 'reviews', 'activeReviews', 'messages', 'dashboardPage'));
        return;
    }

    if ($page === 'admin-reports') {
        $from = (string) request_value('from', date('Y-m-01'));
        $to = (string) request_value('to', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $to < $from) $to = date('Y-m-d');
        $summary = $pdo->prepare("SELECT COUNT(*) total_orders,COALESCE(SUM(CASE WHEN status='completed' THEN subtotal_cents ELSE 0 END),0) completed_value,COALESCE(SUM(CASE WHEN status IN ('accepted','ready','completed') THEN subtotal_cents ELSE 0 END),0) booked_value,COALESCE(SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END),0) cancelled_orders FROM orders WHERE date(created_at) BETWEEN ? AND ?");
        $summary->execute([$from, $to]); $reportSummary = $summary->fetch();
        $statusQuery = $pdo->prepare("SELECT status,COUNT(*) count,COALESCE(SUM(subtotal_cents),0) value FROM orders WHERE date(created_at) BETWEEN ? AND ? GROUP BY status ORDER BY count DESC"); $statusQuery->execute([$from,$to]); $statusBreakdown = $statusQuery->fetchAll();
        $farmerQuery = $pdo->prepare("SELECT u.id,fp.stall_name,COUNT(o.id) order_count,COALESCE(SUM(CASE WHEN o.status='completed' THEN o.subtotal_cents ELSE 0 END),0) completed_value FROM users u JOIN farmer_profiles fp ON fp.user_id=u.id LEFT JOIN orders o ON o.farmer_id=u.id AND date(o.created_at) BETWEEN ? AND ? AND o.status IN ('accepted','ready','completed') WHERE u.role='farmer' GROUP BY u.id,fp.stall_name ORDER BY order_count DESC LIMIT 10"); $farmerQuery->execute([$from,$to]); $farmerReport = $farmerQuery->fetchAll();
        $productQuery = $pdo->prepare("SELECT oi.product_name,SUM(oi.quantity) units,SUM(oi.line_total_cents) value FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE date(o.created_at) BETWEEN ? AND ? AND o.status IN ('accepted','ready','completed') GROUP BY oi.product_id,oi.product_name ORDER BY units DESC LIMIT 10"); $productQuery->execute([$from,$to]); $productReport = $productQuery->fetchAll();
        $marketQuery = $pdo->prepare("SELECT m.id,m.name,COUNT(o.id) order_count,COALESCE(SUM(CASE WHEN o.status='completed' THEN o.subtotal_cents ELSE 0 END),0) completed_value FROM markets m LEFT JOIN orders o ON o.pickup_market_id=m.id AND date(o.created_at) BETWEEN ? AND ? WHERE o.id IS NOT NULL GROUP BY m.id,m.name ORDER BY order_count DESC"); $marketQuery->execute([$from,$to]); $marketReport = $marketQuery->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('admin/reports', compact('from','to','reportSummary','statusBreakdown','farmerReport','productReport','marketReport','dashboardPage'));
        return;
    }

    if ($page === 'admin-announcements') {
        $announcements = $pdo->query('SELECT a.*,u.name author_name FROM announcements a LEFT JOIN users u ON u.id=a.created_by ORDER BY a.created_at DESC')->fetchAll();
        $dashboardPage = $page;
        render_dashboard_view('admin/announcements', compact('announcements', 'dashboardPage'));
        return;
    }

    http_response_code(404); render_view('errors');
}
