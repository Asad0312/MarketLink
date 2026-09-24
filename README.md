# MarketLink — eGreen Basket

MarketLink is a responsive local farmers-market web application built with plain PHP and MySQL/MariaDB for XAMPP. It connects customers with nearby farmers, weekly produce listings, community pickup markets, pre-orders, reviews, and role-based administration.

## Technology

- PHP 8.2+
- MySQL 8 / MariaDB 10.4+
- HTML5 and custom CSS3
- OpenStreetMap (no map API key required)
- Small vanilla browser JavaScript only for password visibility and destructive-action confirmation

## Main Features

### Public

- Animated responsive marketplace homepage
- Product search, category, price, market, day, and sorting filters
- Product and farmer profiles
- Market directory with embedded OpenStreetMap and directions
- About, Contact, FAQ, and accessibility support

### Customer

- Registration and secure login
- Basket, quantity updates, and per-farmer pickup slots
- Split pickup orders when products belong to different farmers
- Order tracking, cutoff-based cancellation, and reorder
- Favorite farmers, products, and markets
- Post-completion product reviews
- In-app order notifications and profile management

### Farmer

- Registration with admin approval workflow
- Farm profile, operating days, markets, pickup windows, and cutoff
- Product, pricing, image, stock, and availability management
- Incoming order queue with accepted, declined, ready, and completed states
- Low-stock alerts, order history, best sellers, and pickup capacity

### Admin

- Platform-wide metrics
- Direct creation of customer and farmer accounts from **Users → Add user**
- Farmer approval/suspension and customer activation/deactivation
- Market and category management
- Product/review moderation and support inbox
- Date-filtered platform, farmer, product, and market reports
- Platform announcements

## phpMyAdmin Installation

1. Copy the project to `C:\xampp\htdocs\Techwize7`.
2. Start Apache and MySQL from XAMPP.
3. Open `http://localhost/phpmyadmin`.
4. Select **Import**.
5. Import `database/marketlink_mysql.sql`.
6. The script creates the `marketlink` database, all tables, and demo records.
7. Open `http://localhost/Techwize7/`.

Default database configuration:

```text
Host: 127.0.0.1
Port: 3306
Database: marketlink
User: root
Password: blank
```

If the local MySQL root account has a password, update `config/app.php`.

## Credentials

All supplied evaluation credentials are in the separate file:

```text
DEMO_CREDENTIALS.txt
```

Passwords are stored as secure hashes in MySQL. The plaintext values exist only in that separate demonstration file.

## Project Structure

```text
app/                    Page controllers and POST actions
assets/                 CSS and minimal browser JavaScript
config/                 Application and MySQL configuration
database/               phpMyAdmin-compatible MySQL SQL
DEMO_CREDENTIALS.txt    Separate role credentials
includes/               Database, helpers, validation, security
uploads/                Validated product images
views/                  PHP templates and partials
index.php               Front controller
```

## Security

- PDO prepared statements
- `password_hash()` and `password_verify()`
- Session regeneration after authentication
- CSRF checks on all state-changing forms
- Central role and ownership authorization
- Uploaded-file MIME and image validation with random filenames
- Executable upload blocking
- Transactional stock and pickup-slot updates
- Soft deletion and administrative audit logging

## Scope

Payment gateways, delivery logistics, farmer licensing, and food-safety certification are intentionally excluded. Orders are paid directly at pickup.
