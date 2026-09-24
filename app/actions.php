<?php

declare(strict_types=1);

function handle_post_action(string $page): void
{
    require_post();
    $pdo = db();

    switch ($page) {
        case 'login':
            handle_login();
            return;
        case 'logout':
            logout_user();
            redirect_to('home');
            return;
        case 'register':
            handle_registration();
            return;
        case 'contact-send':
            handle_contact_message();
            return;

        case 'cart-add':
            handle_cart_add();
            return;
        case 'cart-update':
            handle_cart_update();
            return;
        case 'cart-remove':
            handle_cart_remove();
            return;
        case 'checkout':
            handle_checkout();
            return;
        case 'favorite-toggle':
            handle_favorite_toggle();
            return;
        case 'order-cancel':
            handle_order_cancel();
            return;
        case 'reorder':
            handle_reorder();
            return;
        case 'review-submit':
            handle_review_submit();
            return;
        case 'profile-update':
            handle_profile_update();
            return;
        case 'notification-read':
            handle_notification_read();
            return;

        case 'farmer-product-save':
            handle_farmer_product_save();
            return;
        case 'farmer-product-delete':
            handle_farmer_product_delete();
            return;
        case 'farmer-order-status':
            handle_farmer_order_status();
            return;
        case 'farmer-review-reply':
            handle_farmer_review_reply();
            return;
        case 'farmer-slots-save':
            handle_farmer_slots_save();
            return;

        case 'admin-user-create':
            handle_admin_user_create();
            return;
        case 'admin-user-status':
            handle_admin_user_status();
            return;
        case 'admin-market-save':
            handle_admin_market_save();
            return;
        case 'admin-market-delete':
            handle_admin_market_delete();
            return;
        case 'admin-category-save':
            handle_admin_category_save();
            return;
        case 'admin-moderation':
            handle_admin_moderation();
            return;
        case 'admin-announcement-save':
            handle_admin_announcement_save();
            return;

        default:
            http_response_code(405);
            exit('This action is not available.');
    }
}

function handle_login(): never
{
    $email = strtolower(post_string('email'));
    $password = (string) ($_POST['password'] ?? '');
    $next = (string) ($_POST['next'] ?? '');
    remember_old(['email' => $email]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        remember_errors(['Enter a valid email address and password.']);
        redirect_to('login');
    }

    $statement = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    $blocked = !$user || !password_verify($password, $user['password_hash']);
    if (!$blocked && !account_can_access($user)) {
        flash('error', 'This account is not currently active. Please contact MarketLink support.');
        redirect_to('login');
    }
    if ($blocked) {
        usleep(250000);
        remember_errors(['The email or password is incorrect.']);
        redirect_to('login');
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = db()->prepare('UPDATE users SET password_hash=?, updated_at=? WHERE id=?');
        $rehash->execute([password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), $user['id']]);
    }
    db()->prepare('UPDATE users SET last_login_at=? WHERE id=?')->execute([date('Y-m-d H:i:s'), $user['id']]);
    login_user($user);
    clear_old();
    $destination = safe_return_path(rawurldecode($next));
    if ($destination === url('home')) {
        $destination = url('dashboard');
    }
    redirect($destination);
}

function handle_registration(): never
{
    $role = post_string('role');
    if (!in_array($role, ['customer', 'farmer'], true)) {
        $role = 'customer';
    }
    $name = post_string('name');
    $email = strtolower(post_string('email'));
    $phone = post_string('phone');
    $address = post_string('address');
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $terms = isset($_POST['terms']);
    $errors = [];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) $errors[] = 'Enter a valid email address.';
    if (preg_match('/^[0-9+\-\s()]{7,25}$/', $phone) !== 1) $errors[] = 'Enter a valid contact number.';
    if (mb_strlen($address) < 5 || mb_strlen($address) > 500) $errors[] = 'Enter your address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if (!hash_equals($password, $confirmation)) $errors[] = 'Password confirmation does not match.';
    if (!$terms) $errors[] = 'Accept the pickup-only terms to create an account.';

    $stallName = '';
    $description = '';
    $rawDays = (array) ($_POST['operating_days'] ?? []);
    $days = array_values(array_unique(array_map('intval', $rawDays)));
    $days = array_values(array_filter($days, fn($day) => $day >= 0 && $day <= 6));
    $pickupStart = post_string('pickup_start', '08:00');
    $pickupEnd = post_string('pickup_end', '13:00');
    $cutoffTime = post_string('cutoff_time', '20:00');
    if ($role === 'farmer') {
        $stallName = post_string('stall_name');
        $description = post_string('description');
        if (mb_strlen($stallName) < 2 || mb_strlen($stallName) > 100) $errors[] = 'Enter a valid stall or business name.';
        if (mb_strlen($description) > 800) $errors[] = 'Farm introduction is too long.';
        if (!$days || count($days) !== count(array_unique($rawDays))) $errors[] = 'Select valid market operating days.';
        if (!valid_time_range($pickupStart, $pickupEnd) || !valid_time_value($cutoffTime)) $errors[] = 'Pickup window and cutoff times are not valid.';
    }
    remember_old($_POST);

    $pdo = db();
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE email=? LIMIT 1');
    $exists->execute([$email]);
    if ($exists->fetchColumn()) $errors[] = 'An account already exists for this email address.';
    if ($errors) {
        remember_errors($errors);
        redirect_to('register', ['role' => $role]);
    }

    $now = date('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT INTO users (role,status,name,email,phone,address,password_hash,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $statement->execute([$role, $role === 'farmer' ? 'pending' : 'active', $name, $email, $phone, $address, password_hash($password, PASSWORD_DEFAULT), $now, $now]);
        $userId = (int) $pdo->lastInsertId();
        if ($role === 'farmer') {
            $profile = $pdo->prepare('INSERT INTO farmer_profiles (user_id,stall_name,description,pickup_start,pickup_end,cutoff_time) VALUES (?,?,?,?,?,?)');
            $profile->execute([$userId, $stallName, $description, $pickupStart, $pickupEnd, $cutoffTime]);
            $dayStatement = $pdo->prepare('INSERT INTO farmer_market_days (user_id,day_of_week) VALUES (?,?)');
            foreach ($days as $day) $dayStatement->execute([$userId, $day]);
        }
        $notification = $pdo->prepare('INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)');
        $notification->execute([$userId, 'account', $role === 'farmer' ? 'Farmer application received' : 'Welcome to MarketLink', $role === 'farmer' ? 'An administrator will review your farmer registration shortly.' : 'Your customer account is ready. Start exploring this week’s produce.', url('dashboard')]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }

    clear_old();
    $signedIn = db()->prepare('SELECT * FROM users WHERE id=?');
    $signedIn->execute([$userId]);
    login_user($signedIn->fetch());
    flash('info', $role === 'farmer' ? 'Your farmer account is pending admin approval. You can complete your profile while you wait.' : 'Your customer account is ready.');
    redirect_to('dashboard');
}

function handle_contact_message(): never
{
    $name = post_string('name');
    $email = strtolower(post_string('email'));
    $subject = post_string('subject');
    $message = post_string('message');
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 100) $errors[] = 'Enter a valid subject.';
    if (mb_strlen($message) < 10 || mb_strlen($message) > 1500) $errors[] = 'Message must be between 10 and 1,500 characters.';
    remember_old(['name' => $name, 'email' => $email, 'message' => $message]);
    if ($errors) { remember_errors($errors); redirect_to('contact'); }
    $statement = db()->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)');
    $statement->execute([$name, $email, $subject, $message]);
    clear_old();
    flash('success', 'Thanks! Your message has been received by the MarketLink team.');
    redirect_to('contact');
}

function handle_cart_add(): never
{
    $user = require_role('customer');
    $productId = post_int('product_id');
    $quantity = max(1, min(20, post_int('quantity', 1)));
    $statement = db()->prepare("SELECT p.id,p.name,p.stock_quantity,p.status,f.status farmer_status FROM products p JOIN users f ON f.id=p.farmer_id JOIN categories c ON c.id=p.category_id AND c.status='active' WHERE p.id=? LIMIT 1");
    $statement->execute([$productId]);
    $product = $statement->fetch();
    if (!$product || $product['status'] !== 'active' || $product['farmer_status'] !== 'approved' || (int)$product['stock_quantity'] < 1) {
        flash('error', 'This product is not currently available.');
        redirect_to('explore');
    }

    $existing = db()->prepare('SELECT quantity FROM cart_items WHERE user_id=? AND product_id=?');
    $existing->execute([$user['id'], $productId]);
    $current = (int) ($existing->fetchColumn() ?: 0);
    $newQuantity = min((int)$product['stock_quantity'], min(20, $current + $quantity));
    if ($newQuantity <= $current) {
        flash('warning', 'You already have the available quantity of this item in your basket.');
        redirect_to('cart');
    }
    $save = db()->prepare('INSERT INTO cart_items (user_id,product_id,quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), updated_at=CURRENT_TIMESTAMP');
    $save->execute([$user['id'], $productId, $newQuantity]);
    flash('success', $product['name'] . ' was added to your basket.');
    redirect_to('cart');
}

function handle_cart_update(): never
{
    $user = require_role('customer');
    $quantities = (array) ($_POST['quantities'] ?? []);
    $update = db()->prepare('UPDATE cart_items SET quantity=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?');
    $remove = db()->prepare('DELETE FROM cart_items WHERE id=? AND user_id=?');
    foreach ($quantities as $id => $quantity) {
        if (!ctype_digit((string) $id)) continue;
        $quantity = max(0, min(20, (int) $quantity));
        if ($quantity === 0) $remove->execute([(int)$id, $user['id']]);
        else $update->execute([$quantity, (int)$id, $user['id']]);
    }
    flash('success', 'Your basket quantities were updated.');
    redirect_to('cart');
}

function handle_cart_remove(): never
{
    $user = require_role('customer');
    $id = post_int('item_id');
    db()->prepare('DELETE FROM cart_items WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
    flash('info', 'The item was removed from your basket.');
    redirect_to('cart');
}

function handle_checkout(): never
{
    $user = require_role('customer');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $cart = $pdo->prepare("SELECT ci.id,ci.product_id,ci.quantity,p.name,p.price_cents,p.unit,p.stock_quantity,p.status product_status,f.status farmer_status,f.id farmer_id,fp.stall_name FROM cart_items ci JOIN products p ON p.id=ci.product_id JOIN users f ON f.id=p.farmer_id JOIN farmer_profiles fp ON fp.user_id=f.id JOIN categories c ON c.id=p.category_id AND c.status='active' WHERE ci.user_id=? ORDER BY f.id,p.id FOR UPDATE");
        $cart->execute([$user['id']]);
        $items = $cart->fetchAll();
        if (!$items) throw new DomainException('Your basket is empty.');
        $groups = [];
        foreach ($items as $item) $groups[(int)$item['farmer_id']][] = $item;
        $selectedSlots = (array) ($_POST['slots'] ?? []);
        $note = mb_substr(post_string('note'), 0, 500);
        $createdOrders = [];

        foreach ($groups as $farmerId => $farmerItems) {
            $slotId = (int) ($selectedSlots[$farmerId] ?? 0);
            $slotQuery = $pdo->prepare("SELECT ps.*,m.name market_name FROM pickup_slots ps JOIN markets m ON m.id=ps.market_id AND m.status='active' JOIN users fu ON fu.id=ps.farmer_id AND fu.status='approved' JOIN farmer_markets fm ON fm.farmer_id=ps.farmer_id AND fm.market_id=ps.market_id AND fm.status='active' WHERE ps.id=? AND ps.farmer_id=? AND ps.status='open' AND ps.cutoff_at>NOW() AND ps.booked_count<ps.capacity LIMIT 1 FOR UPDATE");
            $slotQuery->execute([$slotId, $farmerId]);
            $slot = $slotQuery->fetch();
            if (!$slot) throw new DomainException('Choose a valid future pickup slot for every farmer in your basket.');

            $subtotal = 0;
            foreach ($farmerItems as $item) {
                $quantity = (int) $item['quantity'];
                if ($item['product_status'] !== 'active' || $item['farmer_status'] !== 'approved' || (int)$item['stock_quantity'] < $quantity) {
                    throw new DomainException($item['name'] . ' no longer has the requested quantity available.');
                }
                $reserve = $pdo->prepare('UPDATE products SET stock_quantity=stock_quantity-?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND stock_quantity>=? AND status=\'active\'');
                $reserve->execute([$quantity, $item['product_id'], $quantity]);
                if ($reserve->rowCount() !== 1) throw new DomainException('Stock changed while you were checking out. Please review your basket.');
                $subtotal += (int) $item['price_cents'] * $quantity;
            }

            $orderNumber = random_order_number();
            $orderInsert = $pdo->prepare("INSERT INTO orders (order_number,customer_id,farmer_id,pickup_market_id,pickup_slot_id,status,subtotal_cents,note,pickup_address,cutoff_at,created_at,updated_at) VALUES (?,?,?,?,?,'placed',?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");
            $orderInsert->execute([$orderNumber, $user['id'], $farmerId, $slot['market_id'], $slot['id'], $subtotal, $note, $user['address'], $slot['cutoff_at']]);
            $orderId = (int) $pdo->lastInsertId();
            $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,unit,price_cents,quantity,line_total_cents) VALUES (?,?,?,?,?,?,?)');
            foreach ($farmerItems as $item) {
                $itemInsert->execute([$orderId, $item['product_id'], $item['name'], $item['unit'], $item['price_cents'], $item['quantity'], (int)$item['price_cents'] * (int)$item['quantity']]);
            }
            $event = $pdo->prepare('INSERT INTO order_events (order_id,actor_id,event_type,from_status,to_status,note) VALUES (?,?,\'placed\',NULL,\'placed\',?)');
            $event->execute([$orderId, $user['id'], 'Pre-order placed for ' . $slot['market_name'] . '.']);
            $book = $pdo->prepare('UPDATE pickup_slots SET booked_count=booked_count+1 WHERE id=? AND booked_count<capacity');
            $book->execute([$slot['id']]);
            if ($book->rowCount() !== 1) throw new DomainException('That pickup slot just filled. Please choose another slot.');
            $delete = $pdo->prepare('DELETE FROM cart_items WHERE user_id=? AND product_id=?');
            foreach ($farmerItems as $item) $delete->execute([$user['id'], $item['product_id']]);
            $notification = $pdo->prepare('INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)');
            $notification->execute([$user['id'], 'order', 'Order placed successfully', 'Order ' . $orderNumber . ' is waiting for farmer confirmation.', url('order', ['id' => $orderId])]);
            $notification->execute([$farmerId, 'order', 'New pre-order', $user['name'] . ' placed a new pickup order.', url('farmer-order', ['id' => $orderId])]);
            $createdOrders[] = $orderId;
        }
        $pdo->commit();
        clear_old();
        flash('success', count($createdOrders) > 1 ? 'Your basket was split into ' . count($createdOrders) . ' pickup orders successfully.' : 'Your pickup order was placed successfully.');
        redirect_to('order', ['id' => $createdOrders[0]]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($exception instanceof DomainException) {
            remember_errors([$exception->getMessage()]);
            remember_old(['note' => post_string('note'), 'slots' => $_POST['slots'] ?? []]);
            redirect_to('cart');
        }
        throw $exception;
    }
}

function handle_favorite_toggle(): never
{
    $user = require_role('customer');
    $type = post_string('type');
    $id = post_int('id');
    $tables = ['farmer' => 'users', 'product' => 'products', 'market' => 'markets'];
    if (!isset($tables[$type])) redirect_to('home');
    $exists = db()->prepare("SELECT 1 FROM {$tables[$type]} WHERE id=? LIMIT 1");
    $exists->execute([$id]);
    if (!$exists->fetchColumn()) { flash('error', 'That item is no longer available.'); redirect_to('home'); }
    $favorite = db()->prepare('SELECT 1 FROM favorites WHERE user_id=? AND item_type=? AND item_id=?');
    $favorite->execute([$user['id'], $type, $id]);
    if ($favorite->fetchColumn()) {
        db()->prepare('DELETE FROM favorites WHERE user_id=? AND item_type=? AND item_id=?')->execute([$user['id'], $type, $id]);
        flash('info', 'Removed from your saved items.');
    } else {
        db()->prepare('INSERT INTO favorites (user_id,item_type,item_id) VALUES (?,?,?)')->execute([$user['id'], $type, $id]);
        flash('success', 'Saved to your favorites.');
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? url('favorites');
    $safe = safe_return_path($referer);
    redirect($safe === url('home') ? url('favorites') : $safe);
}

function handle_order_cancel(): never
{
    $user = require_role('customer');
    $id = post_int('order_id');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare("SELECT * FROM orders WHERE id=? AND customer_id=? LIMIT 1 FOR UPDATE");
        $query->execute([$id, $user['id']]);
        $order = $query->fetch();
        if (!$order) throw new DomainException('Order not found.');
        if (!in_array($order['status'], ['placed', 'accepted'], true)) throw new DomainException('This order can no longer be cancelled.');
        if (strtotime($order['cutoff_at']) <= time()) throw new DomainException('The farmer order cutoff has passed. Please contact the farmer.');
        $previousStatus = $order['status'];
        $claim = $pdo->prepare("UPDATE orders SET status='cancelled',cancelled_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status=?");
        $claim->execute([$id, $previousStatus]);
        if ($claim->rowCount() !== 1) throw new DomainException('This order was changed by another request. Please refresh.');
        $items = $pdo->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=?');
        $items->execute([$id]);
        $release = $pdo->prepare('UPDATE products SET stock_quantity=stock_quantity+?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
        foreach ($items as $item) $release->execute([$item['quantity'], $item['product_id']]);
        $pdo->prepare("UPDATE pickup_slots SET booked_count=CASE WHEN booked_count>0 THEN booked_count-1 ELSE 0 END WHERE id=?")->execute([$order['pickup_slot_id']]);
        $pdo->prepare("INSERT INTO order_events (order_id,actor_id,event_type,from_status,to_status,note) VALUES (?,?,'cancelled',?,'cancelled','Cancelled by customer before cutoff.')")->execute([$id, $user['id'], $previousStatus]);
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")->execute([$order['farmer_id'], 'order', 'Order cancelled', 'Order ' . $order['order_number'] . ' was cancelled by the customer.', url('farmer-order', ['id' => $id])]);
        $pdo->commit();
        flash('success', 'Your order was cancelled and the stock was released.');
        redirect_to('order', ['id' => $id]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($exception instanceof DomainException) { flash('error', $exception->getMessage()); redirect_to('order', ['id' => $id]); }
        throw $exception;
    }
}

function handle_reorder(): never
{
    $user = require_role('customer');
    $id = post_int('order_id');
    $query = db()->prepare("SELECT oi.*,p.status,p.stock_quantity,f.status farmer_status FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN products p ON p.id=oi.product_id JOIN users f ON f.id=p.farmer_id WHERE o.id=? AND o.customer_id=? AND o.status='completed'");
    $query->execute([$id, $user['id']]);
    $items = $query->fetchAll();
    if (!$items) { flash('error', 'This order cannot be reordered.'); redirect_to('orders'); }
    $pdo = db();
    $pdo->beginTransaction();
    $added = 0;
    try {
        $stockLookup = $pdo->prepare('SELECT stock_quantity,status FROM products WHERE id=? FOR UPDATE');
        $cartLookup = $pdo->prepare('SELECT quantity FROM cart_items WHERE user_id=? AND product_id=? FOR UPDATE');
        $insert = $pdo->prepare('INSERT INTO cart_items (user_id,product_id,quantity) VALUES (?,?,?)');
        $update = $pdo->prepare('UPDATE cart_items SET quantity=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=? AND product_id=?');
        foreach ($items as $item) {
            if ($item['status'] !== 'active' || $item['farmer_status'] !== 'approved') continue;
            $stockLookup->execute([$item['product_id']]);
            $stock = $stockLookup->fetch();
            if (!$stock || $stock['status'] !== 'active' || (int) $stock['stock_quantity'] < 1) continue;
            $cartLookup->execute([$user['id'], $item['product_id']]);
            $current = (int) ($cartLookup->fetchColumn() ?: 0);
            $newQuantity = min((int) $stock['stock_quantity'], min(20, $current + (int) $item['quantity']));
            if ($newQuantity <= $current) continue;
            if ($current === 0) $insert->execute([$user['id'], $item['product_id'], $newQuantity]);
            else $update->execute([$newQuantity, $user['id'], $item['product_id']]);
            $added++;
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
    if (!$added) flash('error', 'None of these items are currently available.');
    else flash('success', $added . ' available item(s) were added to a new basket. Availability and prices were rechecked.');
    redirect_to('cart');
}

function handle_review_submit(): never
{
    $user = require_role('customer');
    $orderItemId = post_int('order_item_id');
    $rating = max(1, min(5, post_int('rating')));
    $comment = mb_substr(post_string('comment'), 0, 800);
    $query = db()->prepare("SELECT oi.id,oi.order_id,oi.product_id,oi.product_name,o.customer_id,o.farmer_id,o.status FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.id=? LIMIT 1");
    $query->execute([$orderItemId]);
    $item = $query->fetch();
    if (!$item || (int)$item['customer_id'] !== (int)$user['id'] || $item['status'] !== 'completed') {
        flash('error', 'You can review only your completed order items.'); redirect_to('orders');
    }
    $exists = db()->prepare('SELECT 1 FROM reviews WHERE order_item_id=?');
    $exists->execute([$orderItemId]);
    if ($exists->fetchColumn()) { flash('warning', 'You already reviewed this item.'); redirect_to('order', ['id' => $item['order_id']]); }
    if (mb_strlen($comment) < 4) { flash('error', 'Please add a short review comment.'); redirect_to('review-form', ['item' => $orderItemId]); }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare("INSERT INTO reviews (order_id,order_item_id,product_id,customer_id,farmer_id,rating,comment) VALUES (?,?,?,?,?,?,?)");
        $insert->execute([$item['order_id'], $orderItemId, $item['product_id'], $user['id'], $item['farmer_id'], $rating, $comment]);
        $pdo->prepare('UPDATE order_items SET reviewed_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$orderItemId]);
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")->execute([$item['farmer_id'], 'review', 'New customer review', $user['name'] . ' reviewed ' . $item['product_name'] . '.', url('farmer-dashboard')]);
        $pdo->commit();
    } catch (Throwable $exception) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $exception; }
    flash('success', 'Thank you! Your review is now visible to the community.');
    redirect_to('order', ['id' => $item['order_id']]);
}

function handle_profile_update(): never
{
    $user = require_auth();
    $name = post_string('name');
    $phone = post_string('phone');
    $address = post_string('address');
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Enter a valid name.';
    if (preg_match('/^[0-9+\-\s()]{7,25}$/', $phone) !== 1) $errors[] = 'Enter a valid phone number.';
    if (mb_strlen($address) < 5 || mb_strlen($address) > 500) $errors[] = 'Enter a valid address.';
    if ($password !== '' && strlen($password) < 8) $errors[] = 'New password must have at least 8 characters.';
    if ($password !== '' && !hash_equals($password, $confirmation)) $errors[] = 'Password confirmation does not match.';
    remember_old($_POST);
    if ($errors) { remember_errors($errors); redirect_to('profile'); }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($password !== '') {
            $update = $pdo->prepare('UPDATE users SET name=?,phone=?,address=?,password_hash=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $update->execute([$name, $phone, $address, password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        } else {
            $update = $pdo->prepare('UPDATE users SET name=?,phone=?,address=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $update->execute([$name, $phone, $address, $user['id']]);
        }
        if ($user['role'] === 'farmer') {
            $stall = post_string('stall_name');
            $description = post_string('description');
            $pickupStart = post_string('pickup_start', '08:00');
            $pickupEnd = post_string('pickup_end', '13:00');
            $cutoff = post_string('cutoff_time', '20:00');
            $marketId = post_int('market_id');
            if (mb_strlen($stall) < 2 || mb_strlen($stall) > 100 || !valid_time_range($pickupStart, $pickupEnd)) {
                $pdo->rollBack(); remember_errors(['Check the farm name and pickup window.']); redirect_to('profile');
            }
            $marketValid = $pdo->prepare("SELECT 1 FROM markets WHERE id=? AND status='active'");
            $marketValid->execute([$marketId]);
            if (!$marketValid->fetchColumn()) { $pdo->rollBack(); remember_errors(['Choose an active market for your pickup stall.']); redirect_to('profile'); }
            $profile = $pdo->prepare('UPDATE farmer_profiles SET stall_name=?,description=?,pickup_start=?,pickup_end=?,cutoff_time=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=?');
            $profile->execute([$stall, mb_substr($description, 0, 800), $pickupStart, $pickupEnd, $cutoff, $user['id']]);
            $membership = $pdo->prepare("INSERT INTO farmer_markets (farmer_id,market_id,stall_label,pickup_instructions) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status='active', stall_label=VALUES(stall_label), pickup_instructions=VALUES(pickup_instructions)");
            $membership->execute([$user['id'], $marketId, $stall, 'Collect from the ' . $stall . ' stall during the published pickup window.']);
            $dayDelete = $pdo->prepare('DELETE FROM farmer_market_days WHERE user_id=?');
            $dayDelete->execute([$user['id']]);
            $dayInsert = $pdo->prepare('INSERT INTO farmer_market_days (user_id,day_of_week) VALUES (?,?)');
            foreach (array_unique(array_map('intval', (array) ($_POST['operating_days'] ?? []))) as $day) if ($day >= 0 && $day <= 6) $dayInsert->execute([$user['id'], $day]);
        }
        $pdo->commit();
        if ($user['role'] === 'farmer') {
            close_unbooked_future_slots($pdo, (int) $user['id']);
            generate_pickup_slots($pdo, (int) $user['id']);
        }
    } catch (Throwable $exception) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $exception; }
    clear_old();
    flash('success', 'Your profile was updated.');
    redirect_to('profile');
}

function handle_notification_read(): never
{
    $user = require_auth();
    $id = post_int('notification_id');
    db()->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
    $link = db()->prepare('SELECT link FROM notifications WHERE id=? AND user_id=?');
    $link->execute([$id, $user['id']]);
    $target = $link->fetchColumn();
    redirect($target ? safe_return_path($target) : url('dashboard'));
}

function handle_farmer_product_save(): never
{
    $user = require_approved_farmer();
    $id = post_int('product_id');
    $name = post_string('name');
    $description = post_string('description');
    $categoryId = post_int('category_id');
    $price = (float) str_replace(',', '', post_string('price'));
    $unit = post_string('unit', 'piece');
    $stock = max(0, post_int('stock_quantity'));
    $status = post_string('status', 'active');
    if (!in_array($status, ['active', 'unavailable', 'sold_out'], true)) $status = 'active';
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Product name must be between 2 and 100 characters.';
    if (mb_strlen($description) > 1200) $errors[] = 'Product description is too long.';
    if ($categoryId < 1 || $price <= 0 || mb_strlen($unit) > 20) $errors[] = 'Choose a category and enter a valid price and unit.';
    $category = db()->prepare("SELECT 1 FROM categories WHERE id=? AND status='active'");
    $category->execute([$categoryId]);
    if (!$category->fetchColumn()) $errors[] = 'Choose an active product category.';
    $existing = null;
    if ($id > 0) {
        $query = db()->prepare('SELECT * FROM products WHERE id=? AND farmer_id=?');
        $query->execute([$id, $user['id']]);
        $existing = $query->fetch();
        if (!$existing) { http_response_code(403); exit('You do not own this product.'); }
        if ($existing['status'] === 'hidden') $status = 'hidden';
    }
    remember_old($_POST);
    if ($errors) { remember_errors($errors); redirect_to('farmer-product-form', $id ? ['id' => $id] : []); }

    try {
        $image = upload_product_image($_FILES['image'] ?? [], $existing['image'] ?? null);
    } catch (RuntimeException $exception) {
        remember_errors([$exception->getMessage()]);
        redirect_to('farmer-product-form', $id ? ['id' => $id] : []);
    }
    $now = date('Y-m-d H:i:s');
    if ($existing) {
        $statement = db()->prepare("UPDATE products SET category_id=?,name=?,description=?,price_cents=?,unit=?,stock_quantity=?,image=?,status=?,updated_at=? WHERE id=? AND farmer_id=?");
        $statement->execute([$categoryId, $name, $description, (int)round($price * 100), $unit, $stock, $image, $status, $now, $id, $user['id']]);
    } else {
        $statement = db()->prepare("INSERT INTO products (farmer_id,category_id,name,description,price_cents,unit,stock_quantity,image,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $statement->execute([$user['id'], $categoryId, $name, $description, (int)round($price * 100), $unit, $stock, $image, $status, $now, $now]);
        $id = (int) db()->lastInsertId();
    }
    clear_old();
    flash('success', $existing ? 'Product listing was updated.' : 'Product was added to your weekly stock.');
    redirect_to('farmer-products');
}

function handle_farmer_product_delete(): never
{
    $user = require_approved_farmer();
    $id = post_int('product_id');
    $query = db()->prepare('SELECT id,name FROM products WHERE id=? AND farmer_id=?');
    $query->execute([$id, $user['id']]);
    $product = $query->fetch();
    if (!$product) { flash('error', 'Product not found.'); redirect_to('farmer-products'); }
    db()->prepare("UPDATE products SET status='hidden',updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$id]);
    flash('success', $product['name'] . ' was hidden. Historical order records were kept.');
    redirect_to('farmer-products');
}

function handle_farmer_order_status(): never
{
    $user = require_approved_farmer();
    $id = post_int('order_id');
    $target = post_string('status');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT * FROM orders WHERE id=? AND farmer_id=? LIMIT 1 FOR UPDATE');
        $query->execute([$id, $user['id']]);
        $order = $query->fetch();
        if (!$order) throw new DomainException('Order not found.');
        $allowed = ['placed' => ['accepted', 'declined'], 'accepted' => ['ready'], 'ready' => ['completed']];
        if (!isset($allowed[$order['status']]) || !in_array($target, $allowed[$order['status']], true)) throw new DomainException('That order status change is not allowed.');
        $previousStatus = $order['status'];
        $column = match ($target) { 'accepted' => 'accepted_at', 'ready' => 'ready_at', 'completed' => 'completed_at', 'declined' => 'declined_at' };
        $claim = $pdo->prepare("UPDATE orders SET status=?,{$column}=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status=?");
        $claim->execute([$target, $id, $previousStatus]);
        if ($claim->rowCount() !== 1) throw new DomainException('This order was changed by another request. Please refresh.');
        if ($target === 'declined') {
            $items = $pdo->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=?');
            $items->execute([$id]);
            $release = $pdo->prepare('UPDATE products SET stock_quantity=stock_quantity+? WHERE id=?');
            foreach ($items as $item) $release->execute([$item['quantity'], $item['product_id']]);
            $pdo->prepare('UPDATE pickup_slots SET booked_count=CASE WHEN booked_count>0 THEN booked_count-1 ELSE 0 END WHERE id=?')->execute([$order['pickup_slot_id']]);
        }
        $message = match ($target) { 'accepted' => 'Your order was accepted by the farmer.', 'ready' => 'Your pickup basket is ready for collection.', 'completed' => 'Pickup completed. Thank you!', default => 'The farmer could not accept this order. Stock has been released.' };
        $event = $pdo->prepare('INSERT INTO order_events (order_id,actor_id,event_type,from_status,to_status,note) VALUES (?,?,?,?,?,?)');
        $event->execute([$id, $user['id'], $target, $order['status'], $target, $message]);
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,?,?,?,?)")->execute([$order['customer_id'], 'order', 'Order ' . $target, $message, url('order', ['id' => $id])]);
        $pdo->commit();
        flash('success', $target === 'declined' ? 'The order was declined and stock was released.' : 'Order status updated to ' . status_label($target) . '.');
        redirect_to('farmer-order', ['id' => $id]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($exception instanceof DomainException) { flash('error', $exception->getMessage()); redirect_to('farmer-order', ['id' => $id]); }
        throw $exception;
    }
}

function handle_farmer_review_reply(): never
{
    $user = require_approved_farmer();
    $id = post_int('review_id');
    $response = mb_substr(post_string('response'), 0, 1000);
    $query = db()->prepare("SELECT id FROM reviews WHERE id=? AND farmer_id=? AND status='published'");
    $query->execute([$id, $user['id']]);
    if (!$query->fetchColumn()) { flash('error', 'Review not found.'); redirect_to('farmer-dashboard'); }
    if (mb_strlen($response) < 2) { flash('error', 'Write a short reply.'); redirect_to('farmer-dashboard'); }
    db()->prepare("UPDATE reviews SET response=?,response_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$response, $id]);
    flash('success', 'Your reply was published under the customer review.');
    redirect_to('farmer-dashboard');
}

function handle_farmer_slots_save(): never
{
    $user = require_approved_farmer();
    $start = post_string('pickup_start');
    $end = post_string('pickup_end');
    $cutoff = post_string('cutoff_time');
    if (!valid_time_range($start, $end) || !valid_time_value($cutoff)) { flash('error', 'Enter valid pickup and cutoff times.'); redirect_to('farmer-slots'); }
    $days = array_values(array_unique(array_map('intval', (array) ($_POST['operating_days'] ?? []))));
    $days = array_values(array_filter($days, fn($day) => $day >= 0 && $day <= 6));
    if (!$days) { flash('error', 'Select at least one operating day.'); redirect_to('farmer-slots'); }
    $pdo = db(); $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE farmer_profiles SET pickup_start=?,pickup_end=?,cutoff_time=?,updated_at=CURRENT_TIMESTAMP WHERE user_id=?')->execute([$start, $end, $cutoff, $user['id']]);
        $pdo->prepare('DELETE FROM farmer_market_days WHERE user_id=?')->execute([$user['id']]);
        $insert = $pdo->prepare('INSERT INTO farmer_market_days (user_id,day_of_week) VALUES (?,?)');
        foreach ($days as $day) $insert->execute([$user['id'], $day]);
        $pdo->commit();
    } catch (Throwable $exception) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $exception; }
    close_unbooked_future_slots($pdo, (int) $user['id']);
    generate_pickup_slots($pdo, (int) $user['id']);
    flash('success', 'Pickup schedule updated. New future slots are now available.');
    redirect_to('farmer-slots');
}

function handle_admin_user_create(): never
{
    $admin = require_role('admin');
    $role = post_string('role', 'customer');
    if (!in_array($role, ['customer', 'farmer'], true)) $role = 'customer';
    $name = post_string('name');
    $email = strtolower(post_string('email'));
    $phone = post_string('phone');
    $address = post_string('address');
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $stallName = post_string('stall_name');
    $description = post_string('description');
    $status = $role === 'farmer' ? post_string('status', 'pending') : 'active';
    $marketId = post_int('market_id');
    $pickupStart = post_string('pickup_start', '08:00');
    $pickupEnd = post_string('pickup_end', '13:00');
    $cutoffTime = post_string('cutoff_time', '20:00');
    $rawDays = (array) ($_POST['operating_days'] ?? []);
    $days = array_values(array_unique(array_filter(array_map('intval', $rawDays), fn($day) => $day >= 0 && $day <= 6)));
    $errors = [];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Enter a valid name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) $errors[] = 'Enter a valid email address.';
    if (preg_match('/^[0-9+\-\s()]{7,25}$/', $phone) !== 1) $errors[] = 'Enter a valid contact number.';
    if (mb_strlen($address) < 5 || mb_strlen($address) > 500) $errors[] = 'Enter a valid address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if (!hash_equals($password, $confirmation)) $errors[] = 'Password confirmation does not match.';
    if ($role === 'farmer') {
        if (mb_strlen($stallName) < 2 || mb_strlen($stallName) > 100) $errors[] = 'Enter a valid stall or business name.';
        if (!in_array($status, ['pending', 'approved'], true)) $status = 'pending';
        if (!$days || count($days) !== count(array_unique($rawDays))) $errors[] = 'Select valid operating days for the farmer.';
        if (!valid_time_range($pickupStart, $pickupEnd) || !valid_time_value($cutoffTime)) $errors[] = 'Enter valid pickup and cutoff times.';
        $market = db()->prepare("SELECT 1 FROM markets WHERE id=? AND status='active'");
        $market->execute([$marketId]);
        if ($marketId < 1 || !$market->fetchColumn()) $errors[] = 'Choose an active market for the farmer.';
    }
    remember_old($_POST);
    if ($errors) { remember_errors($errors); redirect_to('admin-users'); }

    $exists = db()->prepare('SELECT 1 FROM users WHERE email=? LIMIT 1');
    $exists->execute([$email]);
    if ($exists->fetchColumn()) { remember_errors(['An account already exists for this email address.']); redirect_to('admin-users'); }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $now = date('Y-m-d H:i:s');
        $insert = $pdo->prepare('INSERT INTO users (role,status,name,email,phone,address,password_hash,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $insert->execute([$role, $status, $name, $email, $phone, $address, password_hash($password, PASSWORD_DEFAULT), $now, $now]);
        $userId = (int) $pdo->lastInsertId();
        if ($role === 'farmer') {
            $profile = $pdo->prepare('INSERT INTO farmer_profiles (user_id,stall_name,description,latitude,longitude,pickup_start,pickup_end,cutoff_time,approved_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $profile->execute([$userId, $stallName, $description, null, null, $pickupStart, $pickupEnd, $cutoffTime, $status === 'approved' ? $now : null]);
            $membership = $pdo->prepare("INSERT INTO farmer_markets (farmer_id,market_id,stall_label,pickup_instructions,status) VALUES (?,?,?,?,'active')");
            $membership->execute([$userId, $marketId, $stallName, 'Collect from the ' . $stallName . ' stall during the published pickup window.']);
            $dayInsert = $pdo->prepare('INSERT INTO farmer_market_days (user_id,day_of_week) VALUES (?,?)');
            foreach ($days as $day) $dayInsert->execute([$userId, $day]);
        }
        $pdo->prepare("INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)")->execute([$userId, 'account', $role === 'farmer' ? 'Farmer account created' : 'Customer account created', 'Your MarketLink account was created by an administrator.']);
        audit_log($admin['id'], 'user_created', 'user', $userId, $role . ': ' . $email);
        $pdo->commit();
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($exception->getCode() === '23000' || $exception->errorInfo[1] === 1062) { remember_errors(['That email address is already registered.']); redirect_to('admin-users'); }
        throw $exception;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
    clear_old();
    if ($role === 'farmer' && $status === 'approved') generate_pickup_slots(db(), $userId);
    flash('success', ucfirst($role) . ' account created successfully. Login details were saved securely.');
    redirect_to('admin-users');
}

function handle_admin_user_status(): never
{
    $admin = require_role('admin');
    $id = post_int('user_id');
    $status = post_string('status');
    $reason = mb_substr(post_string('reason'), 0, 500);
    $pdo = db();
    $query = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $query->execute([$id]);
    $target = $query->fetch();
    if (!$target) { flash('error', 'User not found.'); redirect_to('admin-users'); }
    if ((int) $target['id'] === (int) $admin['id']) {
        flash('error', 'You cannot change the status of the administrator account you are using.');
        redirect_to('admin-users');
    }
    if ($target['role'] === 'admin') {
        $activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();
        if ($activeAdmins <= 1) { flash('error', 'The final administrator cannot be deactivated.'); redirect_to('admin-users'); }
    }
    $allowed = $target['role'] === 'farmer'
        ? ['pending', 'approved', 'rejected', 'suspended']
        : ($target['role'] === 'customer' ? ['active', 'deactivated'] : ['active', 'suspended']);
    if (!in_array($status, $allowed, true)) { flash('error', 'That account status is not valid.'); redirect_to('admin-users'); }
    $pdo->prepare('UPDATE users SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$status, $id]);
    if ($target['role'] === 'farmer' && $status === 'approved') {
        $pdo->prepare('UPDATE farmer_profiles SET approved_at=COALESCE(approved_at,CURRENT_TIMESTAMP) WHERE user_id=?')->execute([$id]);
        generate_pickup_slots($pdo, $id);
    } elseif ($target['role'] === 'farmer' && $status !== 'approved') {
        close_unbooked_future_slots($pdo, $id);
    }
    audit_log($admin['id'], 'user_status_changed', 'user', $id, $target['status'] . ' -> ' . $status . ($reason ? ': ' . $reason : ''));
    $message = $reason ?: 'Your account status was updated by a MarketLink administrator.';
    $pdo->prepare('INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)')->execute([$id, 'account', 'Account status updated', $message]);
    flash('success', $target['name'] . ' is now ' . status_label($status) . '.');
    redirect_to('admin-users');
}

function handle_admin_market_save(): never
{
    $admin = require_role('admin');
    $id = post_int('market_id');
    $name = post_string('name');
    $description = post_string('description');
    $address = post_string('address');
    $latitude = (float) post_string('latitude');
    $longitude = (float) post_string('longitude');
    $openTime = post_string('open_time', '08:00');
    $closeTime = post_string('close_time', '14:00');
    $status = post_string('status', 'active');
    $days = array_values(array_unique(array_map('intval', (array) ($_POST['operating_days'] ?? []))));
    $days = array_values(array_filter($days, fn($day) => $day >= 0 && $day <= 6));
    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Market name must be between 2 and 120 characters.';
    if (mb_strlen($address) < 5 || mb_strlen($address) > 500) $errors[] = 'Enter a valid market address.';
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) $errors[] = 'Map coordinates are outside valid ranges.';
    if (!valid_time_range($openTime, $closeTime)) $errors[] = 'Market opening and closing times are not valid.';
    if (!$days) $errors[] = 'Select at least one market operating day.';
    if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';
    remember_old($_POST);
    if ($errors) { remember_errors($errors); redirect_to('admin-market-form', $id ? ['id' => $id] : []); }
    $isNew = $id < 1;
    $pdo = db(); $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $exists = $pdo->prepare('SELECT 1 FROM markets WHERE id=?'); $exists->execute([$id]);
            if (!$exists->fetchColumn()) throw new DomainException('Market not found.');
            $statement = $pdo->prepare("UPDATE markets SET name=?,description=?,address=?,latitude=?,longitude=?,map_provider='OpenStreetMap',open_time=?,close_time=?,status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $statement->execute([$name,$description,$address,$latitude,$longitude,$openTime,$closeTime,$status,$id]);
        } else {
            $statement = $pdo->prepare("INSERT INTO markets (name,description,address,latitude,longitude,map_provider,open_time,close_time,status,created_at,updated_at) VALUES (?,?,?,?,?,'OpenStreetMap',?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");
            $statement->execute([$name,$description,$address,$latitude,$longitude,$openTime,$closeTime,$status]);
            $id = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('DELETE FROM market_days WHERE market_id=?')->execute([$id]);
        $dayInsert = $pdo->prepare('INSERT INTO market_days (market_id,day_of_week) VALUES (?,?)');
        foreach ($days as $day) $dayInsert->execute([$id,$day]);
        audit_log($admin['id'], $isNew ? 'market_created' : 'market_saved', 'market', $id, $name);
        $pdo->commit();
    } catch (Throwable $exception) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $exception; }
    close_unbooked_future_slots($pdo, null, $id);
    generate_pickup_slots($pdo);
    clear_old();
    flash('success', 'Market details and operating days were saved.');
    redirect_to('admin-markets');
}

function handle_admin_market_delete(): never
{
    $admin = require_role('admin');
    $id = post_int('market_id');
    $query = db()->prepare('SELECT * FROM markets WHERE id=?'); $query->execute([$id]); $market = $query->fetch();
    if (!$market) { flash('error', 'Market not found.'); redirect_to('admin-markets'); }
    db()->prepare("UPDATE markets SET status='inactive',updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$id]);
    db()->prepare("UPDATE farmer_markets SET status='inactive' WHERE market_id=?")->execute([$id]);
    close_unbooked_future_slots(db(), null, $id);
    audit_log($admin['id'], 'market_deactivated', 'market', $id, $market['name']);
    flash('success', 'Market was deactivated. Historical orders were preserved.');
    redirect_to('admin-markets');
}

function handle_admin_category_save(): never
{
    $admin = require_role('admin');
    $id = post_int('category_id');
    $name = post_string('name');
    $description = post_string('description');
    $status = post_string('status', 'active');
    $sort = post_int('sort_order');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || mb_strlen($description) > 300 || !in_array($status,['active','inactive'],true)) {
        flash('error', 'Enter a valid category name, description, and status.'); redirect_to('admin-categories');
    }
    try {
        if ($id > 0) {
            $statement = db()->prepare('UPDATE categories SET name=?,description=?,status=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $statement->execute([$name,$description,$status,$sort,$id]);
        } else {
            $statement = db()->prepare('INSERT INTO categories (name,description,status,sort_order,created_at,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
            $statement->execute([$name,$description,$status,$sort]); $id = (int) db()->lastInsertId();
        }
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000' || $exception->errorInfo[1] === 1062 || str_contains(strtolower($exception->getMessage()), 'duplicate')) {
            flash('error', 'A category with this name already exists.');
            redirect_to('admin-categories');
        }
        throw $exception;
    }
    audit_log($admin['id'], 'category_saved', 'category', $id, $name);
    flash('success', 'Product category was saved.');
    redirect_to('admin-categories');
}

function handle_admin_moderation(): never
{
    $admin = require_role('admin');
    $type = post_string('type');
    $id = post_int('content_id');
    $action = post_string('action');
    $reason = mb_substr(post_string('reason'), 0, 500);
    if ($reason === '') { flash('error', 'A moderation reason is required.'); redirect_to('admin-moderation'); }
    if ($type === 'product' && in_array($action,['hide','restore'],true)) {
        $query = db()->prepare('SELECT * FROM products WHERE id=?'); $query->execute([$id]); $item = $query->fetch();
        if (!$item) { flash('error', 'Product not found.'); redirect_to('admin-moderation'); }
        $newStatus = $action === 'hide' ? 'hidden' : ((int)$item['stock_quantity'] > 0 ? 'active' : 'sold_out');
        db()->prepare('UPDATE products SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$newStatus,$id]);
        audit_log($admin['id'], 'product_moderated', 'product', $id, $item['status'].' -> '.$newStatus.': '.$reason);
        if ($action === 'hide') db()->prepare('INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)')->execute([$item['farmer_id'],'moderation','Product hidden',$reason]);
        flash('success', $action === 'hide' ? 'Product was hidden from the marketplace.' : 'Product listing was restored.');
        redirect_to('admin-moderation');
    }
    if ($type === 'review' && in_array($action,['hide','restore'],true)) {
        $query = db()->prepare('SELECT * FROM reviews WHERE id=?'); $query->execute([$id]); $item = $query->fetch();
        if (!$item) { flash('error', 'Review not found.'); redirect_to('admin-moderation'); }
        $newStatus = $action === 'hide' ? 'hidden' : 'published';
        db()->prepare('UPDATE reviews SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$newStatus,$id]);
        audit_log($admin['id'], 'review_moderated', 'review', $id, $item['status'].' -> '.$newStatus.': '.$reason);
        flash('success', $action === 'hide' ? 'Review was hidden.' : 'Review was restored.');
        redirect_to('admin-moderation');
    }
    flash('error', 'Invalid moderation action.'); redirect_to('admin-moderation');
}

function handle_admin_announcement_save(): never
{
    $admin = require_role('admin');
    $id = post_int('announcement_id');
    $title = post_string('title');
    $message = post_string('message');
    $status = post_string('status', 'published');
    $expires = post_string('expires_at');
    if (mb_strlen($title) < 3 || mb_strlen($title) > 120 || mb_strlen($message) < 5 || mb_strlen($message) > 800 || !in_array($status,['draft','published'],true)) {
        flash('error','Enter a valid title, message, and status.'); redirect_to('admin-announcements');
    }
    if ($expires !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$expires)) $expires = '';
    if ($id > 0) {
        db()->prepare('UPDATE announcements SET title=?,message=?,status=?,expires_at=?,published_at=CASE WHEN ?=\'published\' THEN COALESCE(published_at,CURRENT_TIMESTAMP) ELSE published_at END,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$title,$message,$status,$expires?:null,$status,$id]);
    } else {
        db()->prepare('INSERT INTO announcements (title,message,status,created_by,published_at,expires_at) VALUES (?,?,?,?,CASE WHEN ?=\'published\' THEN CURRENT_TIMESTAMP ELSE NULL END,?)')->execute([$title,$message,$status,$admin['id'],$status,$expires?:null]); $id=(int)db()->lastInsertId();
    }
    audit_log($admin['id'],'announcement_saved','announcement',$id,$title);
    flash('success', $status==='published' ? 'Announcement published.' : 'Announcement saved as a draft.');
    redirect_to('admin-announcements');
}

function status_label(string $status): string
{
    return str_replace('_', ' ', ucfirst($status));
}

function valid_time_value(string $time): bool
{
    if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $matches)) return false;
    return (int) $matches[1] >= 0 && (int) $matches[1] <= 23 && (int) $matches[2] >= 0 && (int) $matches[2] <= 59;
}

function valid_time_range(string $start, string $end): bool
{
    return valid_time_value($start) && valid_time_value($end) && strtotime($start) < strtotime($end);
}
