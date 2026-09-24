<?php

declare(strict_types=1);

function public_day_label(int|string|null $value): string
{
    $names = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];
    $day = (int) $value;
    return $names[$day] ?? '';
}

function route_public_page(string $page): void
{
    $pdo = db();
    if ($page === 'home') {
        $featuredProducts = $pdo->query(
            "SELECT p.id, p.name, p.description, p.price_cents / 100.0 AS price, p.unit, p.stock_quantity, p.image,
                    p.status, c.name AS category_name, f.id AS farmer_id, fp.stall_name AS farmer_name,
                    (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id AND r.status = 'published') AS average_rating
             FROM products p
             JOIN users f ON f.id = p.farmer_id
             JOIN farmer_profiles fp ON fp.user_id = f.id
             JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'active' AND f.status = 'approved' AND c.status = 'active'
             ORDER BY p.created_at DESC, p.id DESC LIMIT 8"
        )->fetchAll();

        $categories = $pdo->query(
            "SELECT c.id, c.name, c.description, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
             LEFT JOIN users f ON f.id = p.farmer_id AND f.status = 'approved'
             WHERE c.status = 'active'
             GROUP BY c.id,c.name,c.description ORDER BY c.sort_order, c.name LIMIT 8"
        )->fetchAll();

        $featuredMarkets = $pdo->query(
            "SELECT m.*, (SELECT GROUP_CONCAT(md.day_of_week) FROM market_days md WHERE md.market_id=m.id) AS day_values
             FROM markets m WHERE m.status='active' ORDER BY m.id LIMIT 3"
        )->fetchAll();
        foreach ($featuredMarkets as &$market) {
            $market['day_label'] = public_days_to_label($market['day_values']);
            $market['time_label'] = format_date(null) === '—' ? '' : date('g:i A', strtotime($market['open_time'])) . ' – ' . date('g:i A', strtotime($market['close_time']));
        }
        unset($market);

        $featuredFarmers = $pdo->query(
            "SELECT u.id, fp.stall_name, fp.description,
                    (SELECT AVG(r.rating) FROM reviews r WHERE r.farmer_id = u.id AND r.status = 'published') AS average_rating,
                    (SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE p.farmer_id = u.id AND p.status = 'active' AND c.status='active') AS product_count,
                    (SELECT m.name FROM farmer_markets fm JOIN markets m ON m.id = fm.market_id WHERE fm.farmer_id = u.id AND fm.status = 'active' LIMIT 1) AS market_name
             FROM users u JOIN farmer_profiles fp ON fp.user_id = u.id
             WHERE u.role = 'farmer' AND u.status = 'approved'
             ORDER BY product_count DESC, u.id LIMIT 3"
        )->fetchAll();

        $stats = [
            'products' => (int) $pdo->query("SELECT COUNT(*) FROM products p JOIN users f ON f.id=p.farmer_id JOIN categories c ON c.id=p.category_id WHERE p.status='active' AND f.status='approved' AND c.status='active'")->fetchColumn(),
            'farmers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='farmer' AND status='approved'")->fetchColumn(),
            'markets' => (int) $pdo->query("SELECT COUNT(*) FROM markets WHERE status='active'")->fetchColumn(),
        ];
        $announcement = $pdo->query("SELECT * FROM announcements WHERE status='published' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY published_at DESC LIMIT 1")->fetch() ?: null;
        render_view('home', compact('featuredProducts', 'categories', 'featuredMarkets', 'featuredFarmers', 'stats', 'announcement'));
        return;
    }

    if ($page === 'products') {
        $filters = [
            'q' => mb_substr((string) request_value('q', ''), 0, 100),
            'category' => (string) request_value('category', ''),
            'market' => (string) request_value('market', ''),
            'day' => (string) request_value('day', ''),
            'min_price' => (string) request_value('min_price', ''),
            'max_price' => (string) request_value('max_price', ''),
            'sort' => (string) request_value('sort', 'newest'),
        ];
        $where = ["p.status = 'active'", "f.status = 'approved'", "c.status = 'active'"];
        $params = [];
        if ($filters['q'] !== '') {
            $where[] = '(p.name LIKE ? OR p.description LIKE ? OR fp.stall_name LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }
        if (ctype_digit($filters['category'])) {
            $where[] = 'p.category_id = ?';
            $params[] = (int) $filters['category'];
        }
        if (ctype_digit($filters['market'])) {
            $where[] = 'EXISTS (SELECT 1 FROM farmer_markets fm WHERE fm.farmer_id = f.id AND fm.market_id = ? AND fm.status = \'active\')';
            $params[] = (int) $filters['market'];
        }
        if ($filters['day'] !== '' && ctype_digit($filters['day'])) {
            $where[] = 'EXISTS (SELECT 1 FROM farmer_markets fm JOIN market_days md ON md.market_id=fm.market_id WHERE fm.farmer_id=f.id AND md.day_of_week=? AND fm.status=\'active\')';
            $params[] = (int) $filters['day'];
        }
        if ($filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $where[] = 'p.price_cents >= ?';
            $params[] = (int) round((float) $filters['min_price'] * 100);
        }
        if ($filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $where[] = 'p.price_cents <= ?';
            $params[] = (int) round((float) $filters['max_price'] * 100);
        }
        $sort = match ($filters['sort']) {
            'price_asc' => 'p.price_cents ASC, p.name ASC',
            'price_desc' => 'p.price_cents DESC, p.name ASC',
            'name' => 'p.name ASC',
            default => 'p.created_at DESC, p.id DESC',
        };
        $perPage = 12;
        $currentPage = max(1, (int) request_value('page_number', 1));
        $count = $pdo->prepare('SELECT COUNT(*) FROM products p JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id WHERE ' . implode(' AND ', $where));
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        $statement = $pdo->prepare(
            "SELECT p.id, p.name, p.description, p.price_cents / 100.0 AS price, p.unit, p.stock_quantity, p.image, p.status,
                    c.name AS category_name, f.id AS farmer_id, fp.stall_name AS farmer_name,
                    (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id = p.id AND r.status = 'published') AS average_rating
             FROM products p JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id
             WHERE " . implode(' AND ', $where) . " ORDER BY $sort LIMIT $perPage OFFSET $offset"
        );
        $statement->execute($params);
        $products = $statement->fetchAll();
        $categories = $pdo->query("SELECT id,name FROM categories WHERE status='active' ORDER BY sort_order,name")->fetchAll();
        $markets = $pdo->query("SELECT id,name FROM markets WHERE status='active' ORDER BY name")->fetchAll();
        render_view('products', compact('products', 'categories', 'markets', 'filters', 'total', 'totalPages', 'currentPage'));
        return;
    }

    if ($page === 'product') {
        $id = get_int('id');
        $statement = $pdo->prepare(
            "SELECT p.*, p.price_cents / 100.0 AS price, c.name AS category_name,
                    f.id AS farmer_id, f.name AS contact_name, fp.stall_name, fp.description AS farmer_description,
                    (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') AS average_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.product_id=p.id AND r.status='published') AS review_count
             FROM products p JOIN categories c ON c.id=p.category_id JOIN users f ON f.id=p.farmer_id
             JOIN farmer_profiles fp ON fp.user_id=f.id
             WHERE p.id=? AND p.status='active' AND f.status='approved' AND c.status='active' LIMIT 1"
        );
        $statement->execute([$id]);
        $product = $statement->fetch();
        if (!$product) {
            http_response_code(404);
            render_view('errors');
            return;
        }
        $related = $pdo->prepare(
            "SELECT p.id,p.name,p.description,p.price_cents/100.0 AS price,p.unit,p.stock_quantity,p.image,p.status,c.name AS category_name,f.id AS farmer_id,fp.stall_name AS farmer_name,
                    (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') AS average_rating
             FROM products p JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id
             WHERE p.status='active' AND f.status='approved' AND c.status='active' AND p.category_id=(SELECT category_id FROM products WHERE id=?) AND p.id<>?
             ORDER BY p.created_at DESC LIMIT 4"
        );
        $related->execute([$id, $id]);
        $relatedProducts = $related->fetchAll();
        $reviews = $pdo->prepare(
            "SELECT r.*, u.name AS customer_name, p.image, p.name AS product_name
             FROM reviews r JOIN users u ON u.id=r.customer_id JOIN order_items oi ON oi.id=r.order_item_id JOIN products p ON p.id=r.product_id
             WHERE r.product_id=? AND r.status='published' ORDER BY r.created_at DESC LIMIT 8"
        );
        $reviews->execute([$id]);
        $productReviews = $reviews->fetchAll();
        $isFavorite = is_favorite('product', $id);
        render_view('product', compact('product', 'relatedProducts', 'productReviews', 'isFavorite'));
        return;
    }

    if ($page === 'markets') {
        $search = mb_substr((string) request_value('q', ''), 0, 100);
        $day = (string) request_value('day', '');
        $where = ["m.status='active'"];
        $params = [];
        if ($search !== '') {
            $where[] = '(m.name LIKE ? OR m.address LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($day !== '' && ctype_digit($day)) {
            $where[] = 'EXISTS (SELECT 1 FROM market_days md WHERE md.market_id=m.id AND md.day_of_week=?)';
            $params[] = (int) $day;
        }
        $statement = $pdo->prepare("SELECT m.*,
                    (SELECT GROUP_CONCAT(md.day_of_week) FROM market_days md WHERE md.market_id=m.id) day_values,
                    (SELECT COUNT(DISTINCT fm.farmer_id) FROM farmer_markets fm WHERE fm.market_id=m.id AND fm.status='active') farmer_count,
                    (SELECT COUNT(DISTINCT p.id) FROM farmer_markets fm JOIN products p ON p.farmer_id=fm.farmer_id AND p.status='active' JOIN categories pc ON pc.id=p.category_id AND pc.status='active' WHERE fm.market_id=m.id AND fm.status='active') product_count
             FROM markets m WHERE " . implode(' AND ', $where) . ' ORDER BY m.name');
        $statement->execute($params);
        $markets = $statement->fetchAll();
        foreach ($markets as &$market) {
            $market['day_label'] = public_days_to_label($market['day_values']);
            $market['time_label'] = date('g:i A', strtotime($market['open_time'])) . ' – ' . date('g:i A', strtotime($market['close_time']));
        }
        unset($market);
        render_view('markets', compact('markets', 'search', 'day'));
        return;
    }

    if ($page === 'market') {
        $id = get_int('id');
        $statement = $pdo->prepare("SELECT * FROM markets WHERE id=? AND status='active' LIMIT 1");
        $statement->execute([$id]);
        $market = $statement->fetch();
        if (!$market) {
            http_response_code(404);
            render_view('errors');
            return;
        }
        $days = $pdo->prepare('SELECT day_of_week FROM market_days WHERE market_id=? ORDER BY day_of_week');
        $days->execute([$id]);
        $market['days'] = array_map('intval', $days->fetchAll(PDO::FETCH_COLUMN));
        $market['is_favorite'] = is_favorite('market', $id);
        $farmers = $pdo->prepare(
            "SELECT u.id,fp.stall_name,fp.description,fm.stall_label,fm.pickup_instructions,
                    (SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE p.farmer_id=u.id AND p.status='active' AND c.status='active') product_count,
                    (SELECT AVG(r.rating) FROM reviews r WHERE r.farmer_id=u.id AND r.status='published') average_rating
             FROM farmer_markets fm JOIN users u ON u.id=fm.farmer_id JOIN farmer_profiles fp ON fp.user_id=u.id
             WHERE fm.market_id=? AND fm.status='active' AND u.status='approved' ORDER BY fp.stall_name"
        );
        $farmers->execute([$id]);
        $marketFarmers = $farmers->fetchAll();
        $products = $pdo->prepare(
            "SELECT p.id,p.name,p.description,p.price_cents/100.0 AS price,p.unit,p.stock_quantity,p.image,p.status,c.name AS category_name,f.id AS farmer_id,fp.stall_name AS farmer_name,
                    (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') average_rating
             FROM farmer_markets fm JOIN products p ON p.farmer_id=fm.farmer_id AND p.status='active' JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id
             WHERE fm.market_id=? AND fm.status='active' AND f.status='approved' AND c.status='active' ORDER BY p.updated_at DESC"
        );
        $products->execute([$id]);
        $marketProducts = $products->fetchAll();
        render_view('market', compact('market', 'marketFarmers', 'marketProducts'));
        return;
    }

    if ($page === 'farmers') {
        $search = mb_substr((string) request_value('q', ''), 0, 100);
        $marketId = (int) request_value('market', 0);
        $where = ["u.role='farmer'", "u.status='approved'"];
        $params = [];
        if ($search !== '') {
            $where[] = '(fp.stall_name LIKE ? OR fp.description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($marketId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM farmer_markets fm WHERE fm.farmer_id=u.id AND fm.market_id=? AND fm.status=\'active\')';
            $params[] = $marketId;
        }
        $statement = $pdo->prepare(
            "SELECT u.id,fp.stall_name,fp.description,fp.latitude,fp.longitude,
                    (SELECT GROUP_CONCAT(m.name, ', ') FROM farmer_markets fm JOIN markets m ON m.id=fm.market_id WHERE fm.farmer_id=u.id AND fm.status='active') market_names,
                    (SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE p.farmer_id=u.id AND p.status='active' AND c.status='active') product_count,
                    (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.farmer_id=u.id AND r.status='published') average_rating
             FROM users u JOIN farmer_profiles fp ON fp.user_id=u.id WHERE " . implode(' AND ', $where) . ' ORDER BY fp.stall_name'
        );
        $statement->execute($params);
        $farmers = $statement->fetchAll();
        $markets = $pdo->query("SELECT id,name FROM markets WHERE status='active' ORDER BY name")->fetchAll();
        render_view('farmers', compact('farmers', 'markets', 'search', 'marketId'));
        return;
    }

    if ($page === 'farmer') {
        $id = get_int('id');
        $statement = $pdo->prepare(
            "SELECT u.id,u.name,u.created_at,fp.*,
                    (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.farmer_id=u.id AND r.status='published') average_rating,
                    (SELECT COUNT(*) FROM reviews r WHERE r.farmer_id=u.id AND r.status='published') review_count,
                    (SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE p.farmer_id=u.id AND p.status='active' AND c.status='active') product_count
             FROM users u JOIN farmer_profiles fp ON fp.user_id=u.id WHERE u.id=? AND u.role='farmer' AND u.status='approved' LIMIT 1"
        );
        $statement->execute([$id]);
        $farmer = $statement->fetch();
        if (!$farmer) {
            http_response_code(404);
            render_view('errors');
            return;
        }
        $products = $pdo->prepare(
            "SELECT p.id,p.name,p.description,p.price_cents/100.0 AS price,p.unit,p.stock_quantity,p.image,p.status,c.name AS category_name,f.id AS farmer_id,fp.stall_name AS farmer_name,
                    (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.product_id=p.id AND r.status='published') average_rating
             FROM products p JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id
             WHERE p.farmer_id=? AND p.status='active' AND c.status='active' ORDER BY p.updated_at DESC"
        );
        $products->execute([$id]);
        $farmerProducts = $products->fetchAll();
        $markets = $pdo->prepare(
            "SELECT m.*,fm.stall_label,fm.pickup_instructions,
                    (SELECT GROUP_CONCAT(md.day_of_week) FROM market_days md WHERE md.market_id=m.id) day_values
             FROM farmer_markets fm JOIN markets m ON m.id=fm.market_id
             WHERE fm.farmer_id=? AND fm.status='active' ORDER BY m.name"
        );
        $markets->execute([$id]);
        $farmerMarkets = $markets->fetchAll();
        foreach ($farmerMarkets as &$market) {
            $market['day_label'] = public_days_to_label($market['day_values']);
        }
        unset($market);
        $reviews = $pdo->prepare(
            "SELECT r.*,u.name AS customer_name,p.name AS product_name FROM reviews r JOIN users u ON u.id=r.customer_id JOIN products p ON p.id=r.product_id
             WHERE r.farmer_id=? AND r.status='published' ORDER BY r.created_at DESC LIMIT 8"
        );
        $reviews->execute([$id]);
        $farmerReviews = $reviews->fetchAll();
        $isFavorite = is_favorite('farmer', $id);
        render_view('farmer', compact('farmer', 'farmerProducts', 'farmerMarkets', 'farmerReviews', 'isFavorite'));
        return;
    }

    render_view($page);
}

function public_days_to_label(?string $values): string
{
    if (!$values) return 'Schedule unavailable';
    $days = array_map('intval', explode(',', $values));
    sort($days);
    $labels = array_map('public_day_label', $days);
    if (count($labels) === 7) return 'Every day';
    if ($labels === ['Fri', 'Sat']) return 'Friday & Saturday';
    if ($labels === ['Sat', 'Sun']) return 'Saturday & Sunday';
    return implode(' · ', $labels);
}
