<?php

declare(strict_types=1);

/** Return the shared MySQL connection for XAMPP/phpMyAdmin installations. */
function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    if (preg_match('/^[a-zA-Z0-9_]+$/', DB_NAME) !== 1) {
        throw new RuntimeException('DB_NAME contains invalid characters.');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ];

    try {
        $connection = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASSWORD,
            $options
        );
    } catch (PDOException $exception) {
        if (($exception->getCode() !== '42000') && !str_contains(strtolower($exception->getMessage()), 'unknown database')) {
            throw $exception;
        }

        $server = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
            DB_USER,
            DB_PASSWORD,
            $options
        );
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $connection = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASSWORD,
            $options
        );
    }

    $connection->exec("SET time_zone = '+05:00'");
    ensure_mysql_installation($connection);
    ensure_pickup_horizon($connection);
    return $connection;
}

function ensure_mysql_installation(PDO $pdo): void
{
    $requiredTables = [
        'users','farmer_profiles','markets','market_days','farmer_markets','farmer_market_days',
        'categories','products','pickup_slots','cart_items','orders','order_items','order_events',
        'favorites','reviews','notifications','announcements','contact_messages','audit_logs','app_meta'
    ];
    $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
    $table = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name IN ($placeholders)");
    $table->execute(array_merge([DB_NAME], $requiredTables));
    $tableCount = (int) $table->fetchColumn();
    $allTables = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?");
    $allTables->execute([DB_NAME]);
    $databaseTableCount = (int) $allTables->fetchColumn();

    if ($tableCount !== count($requiredTables)) {
        // Never run the destructive phpMyAdmin dump against a partially populated
        // database. A user must explicitly re-import the complete dump in that case.
        if ($databaseTableCount > 0) {
            throw new RuntimeException('MarketLink database schema is incomplete. Import database/marketlink_mysql.sql in phpMyAdmin before using the application.');
        }
        $schemaPath = BASE_PATH . '/database/marketlink_mysql.sql';
        if (!is_file($schemaPath)) {
            throw new RuntimeException('The MySQL schema file is missing.');
        }
        $schema = (string) file_get_contents($schemaPath);
        if (DB_NAME !== 'marketlink') {
            $schema = str_replace('CREATE DATABASE IF NOT EXISTS `marketlink`', 'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '`', $schema);
            $schema = str_replace('USE marketlink;', 'USE `' . DB_NAME . '`;', $schema);
        }
        $pdo->exec($schema);
    }

    // Compatibility upgrade for the first MySQL schema revision.
    $profileTimestamp = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name='farmer_profiles' AND column_name='updated_at'");
    $profileTimestamp->execute([DB_NAME]);
    if ((int) $profileTimestamp->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE farmer_profiles ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
    $declinedColumn = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name='orders' AND column_name='declined_at'");
    $declinedColumn->execute([DB_NAME]);
    if ((int) $declinedColumn->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE orders ADD declined_at DATETIME NULL AFTER completed_at');
    }

    $seed = $pdo->prepare("SELECT meta_value FROM app_meta WHERE meta_key='demo_operational_seed' LIMIT 1");
    $seed->execute();
    if ((string) ($seed->fetchColumn() ?: '0') !== '1') {
        $pdo->beginTransaction();
        try {
            generate_pickup_slots($pdo);
            seed_demo_orders($pdo, date('Y-m-d H:i:s'));
            $mark = $pdo->prepare("UPDATE app_meta SET meta_value='1' WHERE meta_key='demo_operational_seed'");
            $mark->execute();
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }
}

function generate_pickup_slots(PDO $pdo, ?int $farmerId = null): void
{
    $query = "SELECT u.id AS farmer_id, fp.pickup_start, fp.pickup_end, fp.cutoff_time, fm.market_id
              FROM users u
              JOIN farmer_profiles fp ON fp.user_id = u.id
              JOIN farmer_markets fm ON fm.farmer_id = u.id AND fm.status = 'active'
              JOIN markets m ON m.id = fm.market_id AND m.status = 'active'
              WHERE u.role = 'farmer' AND u.status = 'approved'";
    $params = [];
    if ($farmerId !== null) {
        $query .= ' AND u.id = ?';
        $params[] = $farmerId;
    }
    $query .= ' ORDER BY u.id, fm.market_id';
    $statement = $pdo->prepare($query);
    $statement->execute($params);
    $memberships = $statement->fetchAll();

    $insert = $pdo->prepare("INSERT INTO pickup_slots (farmer_id, market_id, pickup_date, start_time, end_time, cutoff_at, capacity, booked_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?) ON DUPLICATE KEY UPDATE start_time=VALUES(start_time), end_time=VALUES(end_time), cutoff_at=VALUES(cutoff_at), status=IF(booked_count=0,'open',status)");
    $customDays = $pdo->prepare('SELECT 1 FROM farmer_market_days WHERE user_id = ? LIMIT 1');
    $farmerDay = $pdo->prepare('SELECT 1 FROM farmer_market_days WHERE user_id = ? AND day_of_week = ? LIMIT 1');
    $marketDay = $pdo->prepare('SELECT 1 FROM market_days WHERE market_id = ? AND day_of_week = ? LIMIT 1');

    for ($offset = -28; $offset <= 42; $offset++) {
        $date = new DateTimeImmutable('today', new DateTimeZone(APP_TIMEZONE));
        $date = $date->modify(($offset >= 0 ? '+' : '') . $offset . ' days');
        $dateString = $date->format('Y-m-d');
        $weekday = (int) $date->format('w');

        foreach ($memberships as $membership) {
            $customDays->execute([$membership['farmer_id']]);
            if ($customDays->fetchColumn()) {
                $farmerDay->execute([$membership['farmer_id'], $weekday]);
                if (!$farmerDay->fetchColumn()) continue;
            } else {
                $marketDay->execute([$membership['market_id'], $weekday]);
                if (!$marketDay->fetchColumn()) continue;
            }

            $cutoffTime = date('H:i:s', strtotime((string) $membership['cutoff_time']));
            $cutoffAt = $date->modify('-1 day')->format('Y-m-d') . ' ' . $cutoffTime;
            $status = $dateString < date('Y-m-d') ? 'completed' : 'open';
            $insert->execute([
                $membership['farmer_id'],
                $membership['market_id'],
                $dateString,
                $membership['pickup_start'],
                $membership['pickup_end'],
                $cutoffAt,
                30,
                $status,
            ]);
        }
    }
}

function close_unbooked_future_slots(PDO $pdo, ?int $farmerId = null, ?int $marketId = null): void
{
    $where = ["pickup_date >= CURDATE()", "status = 'open'", "booked_count = 0"];
    $params = [];
    if ($farmerId !== null) { $where[] = 'farmer_id = ?'; $params[] = $farmerId; }
    if ($marketId !== null) { $where[] = 'market_id = ?'; $params[] = $marketId; }
    $statement = $pdo->prepare('UPDATE pickup_slots SET status=\'closed\' WHERE ' . implode(' AND ', $where));
    $statement->execute($params);
}

function ensure_pickup_horizon(PDO $pdo): void
{
    $required = date('Y-m-d', strtotime('+35 days'));
    $maximum = $pdo->query('SELECT MAX(pickup_date) FROM pickup_slots')->fetchColumn();
    if (!$maximum || $maximum < $required) {
        generate_pickup_slots($pdo);
    }
}

function seed_demo_orders(PDO $pdo, string $now): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() > 0) {
        return;
    }

    $order = $pdo->prepare('INSERT INTO orders (order_number, customer_id, farmer_id, pickup_market_id, pickup_slot_id, status, subtotal_cents, note, pickup_address, cutoff_at, created_at, updated_at, accepted_at, ready_at, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $item = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit, price_cents, quantity, line_total_cents) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $event = $pdo->prepare('INSERT INTO order_events (order_id, actor_id, event_type, from_status, to_status, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $notification = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, ?, ?, ?, ?, ?)');

    $pastSlot = $pdo->query("SELECT * FROM pickup_slots WHERE farmer_id=2 AND pickup_date<CURDATE() ORDER BY pickup_date DESC LIMIT 1")->fetch();
    $farmer2Future = $pdo->query("SELECT * FROM pickup_slots WHERE farmer_id=3 AND cutoff_at>NOW() ORDER BY pickup_date,start_time LIMIT 1")->fetch();
    $farmer1Future = $pdo->query("SELECT * FROM pickup_slots WHERE farmer_id=2 AND cutoff_at>NOW() ORDER BY pickup_date,start_time LIMIT 1")->fetch();
    $farmer3Future = $pdo->query("SELECT * FROM pickup_slots WHERE farmer_id=4 AND cutoff_at>NOW() ORDER BY pickup_date,start_time LIMIT 1")->fetch();

    if ($pastSlot) {
        $created = $pastSlot['pickup_date'] . ' 07:30:00';
        $order->execute(['ML-DEMO-1001', 5, 2, $pastSlot['market_id'], $pastSlot['id'], 'completed', 110000, 'Please pack the spinach separately.', 'House 24, Street 7, Gulberg III, Lahore', $pastSlot['cutoff_at'], $created, $created, $pastSlot['pickup_date'].' 08:05:00', $pastSlot['pickup_date'].' 10:15:00', $pastSlot['pickup_date'].' 12:40:00']);
        $orderId = (int) $pdo->lastInsertId();
        $item->execute([$orderId, 1, 'Organic Tomatoes', 'kg', 22000, 2, 44000]);
        $tomatoItemId = (int) $pdo->lastInsertId();
        $item->execute([$orderId, 2, 'Baby Spinach', 'bag', 18000, 1, 18000]);
        $item->execute([$orderId, 4, 'Free Range Eggs', 'dozen', 48000, 1, 48000]);
        $event->execute([$orderId, 5, 'placed', null, 'placed', 'Order placed from the weekly marketplace.', $created]);
        $event->execute([$orderId, 2, 'accepted', 'placed', 'accepted', 'Thank you, Sara. Your basket is confirmed.', $pastSlot['pickup_date'].' 08:05:00']);
        $event->execute([$orderId, 2, 'ready', 'accepted', 'ready', 'Your pickup basket is ready.', $pastSlot['pickup_date'].' 10:15:00']);
        $event->execute([$orderId, 5, 'completed', 'ready', 'completed', 'Pickup completed.', $pastSlot['pickup_date'].' 12:40:00']);
        $review = $pdo->prepare("INSERT INTO reviews (order_id,order_item_id,product_id,customer_id,farmer_id,rating,comment,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'published',?,?)");
        $review->execute([$orderId, $tomatoItemId, 1, 5, 2, 5, 'Beautifully ripe and packed with care. The spinach was especially fresh!', $pastSlot['pickup_date'].' 13:10:00', $pastSlot['pickup_date'].' 13:10:00']);
    }

    if ($farmer2Future) {
        $created = date('Y-m-d H:i:s', strtotime('-3 hours'));
        $order->execute(['ML-DEMO-1002', 5, 3, $farmer2Future['market_id'], $farmer2Future['id'], 'placed', 161000, 'Please call when the fruit is ready.', 'House 24, Street 7, Gulberg III, Lahore', $farmer2Future['cutoff_at'], $created, $created, null, null, null]);
        $orderId = (int) $pdo->lastInsertId();
        $item->execute([$orderId, 5, 'Sweet Strawberries', 'box', 65000, 1, 65000]);
        $item->execute([$orderId, 6, 'Desi Mangoes', 'kg', 48000, 2, 96000]);
        $event->execute([$orderId, 5, 'placed', null, 'placed', 'Order placed from the weekly marketplace.', $created]);
        $pdo->prepare('UPDATE pickup_slots SET booked_count=booked_count+1 WHERE id=?')->execute([$farmer2Future['id']]);
        $notification->execute([5, 'order', 'Order placed successfully', 'Order ML-DEMO-1002 is waiting for farmer confirmation.', url('order', ['id' => $orderId]), $created]);
        $notification->execute([3, 'order', 'New pre-order', 'Sara Ali placed an order for strawberries and mangoes.', url('farmer-order', ['id' => $orderId]), $created]);
    }

    if ($farmer1Future) {
        $created = date('Y-m-d H:i:s', strtotime('-90 minutes'));
        $order->execute(['ML-DEMO-1003', 6, 2, $farmer1Future['market_id'], $farmer1Future['id'], 'ready', 114000, '', 'House 8, Street 12, Model Town, Lahore', $farmer1Future['cutoff_at'], $created, $created, $created, date('Y-m-d H:i:s', strtotime('-20 minutes')), null]);
        $orderId = (int) $pdo->lastInsertId();
        $item->execute([$orderId, 4, 'Free Range Eggs', 'dozen', 48000, 2, 96000]);
        $item->execute([$orderId, 2, 'Baby Spinach', 'bag', 18000, 1, 18000]);
        $event->execute([$orderId, 6, 'placed', null, 'placed', 'Order placed.', $created]);
        $event->execute([$orderId, 2, 'accepted', 'placed', 'accepted', 'Order accepted.', $created]);
        $event->execute([$orderId, 2, 'ready', 'accepted', 'ready', 'Basket packed and ready for pickup.', date('Y-m-d H:i:s', strtotime('-20 minutes'))]);
        $pdo->prepare('UPDATE pickup_slots SET booked_count=booked_count+1 WHERE id=?')->execute([$farmer1Future['id']]);
        $notification->execute([6, 'order', 'Ready for pickup', 'Your Green Valley Organics basket is packed and ready.', url('order', ['id' => $orderId]), date('Y-m-d H:i:s', strtotime('-20 minutes'))]);
    }

    if ($farmer3Future) {
        $created = date('Y-m-d H:i:s', strtotime('-5 hours'));
        $order->execute(['ML-DEMO-1004', 5, 4, $farmer3Future['market_id'], $farmer3Future['id'], 'accepted', 130000, '', 'House 24, Street 7, Gulberg III, Lahore', $farmer3Future['cutoff_at'], $created, $created, date('Y-m-d H:i:s', strtotime('-2 hours')), null, null]);
        $orderId = (int) $pdo->lastInsertId();
        $item->execute([$orderId, 9, 'Country Sourdough', 'loaf', 35000, 1, 35000]);
        $item->execute([$orderId, 10, 'Creamy Goat Cheese', 'piece', 95000, 1, 95000]);
        $event->execute([$orderId, 5, 'placed', null, 'placed', 'Order placed.', $created]);
        $event->execute([$orderId, 4, 'accepted', 'placed', 'accepted', 'Order accepted.', date('Y-m-d H:i:s', strtotime('-2 hours'))]);
        $pdo->prepare('UPDATE pickup_slots SET booked_count=booked_count+1 WHERE id=?')->execute([$farmer3Future['id']]);
        $notification->execute([5, 'order', 'Order accepted', 'Honey Bee Acres accepted your bakery order.', url('order', ['id' => $orderId]), date('Y-m-d H:i:s', strtotime('-2 hours'))]);
    }
}
