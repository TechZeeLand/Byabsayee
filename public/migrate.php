<?php
/**
 * Byabsayee — Database Migration Script
 * ======================================
 * Place this file in your project ROOT (same folder as composer.json).
 * Run it via browser: http://your-site/migrate.php
 * Or via CLI:         php migrate.php
 *
 * It is safe to run multiple times — it only adds what's missing.
 * DELETE this file after running it successfully.
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('BASE_PATH', __DIR__);

$env = BASE_PATH . '/.env';
if (file_exists($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$host    = getenv('DB_HOST') ?: 'mariadb';
$port    = getenv('DB_PORT') ?: '3306';
$dbname  = getenv('DB_NAME') ?: 'byabsayee_db';
$user    = getenv('DB_USER') ?: 'byabsayee_user';
$pass    = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("❌  Cannot connect to database: " . $e->getMessage());
}

// ── Helpers ───────────────────────────────────────────────────────────────────
$log = [];

function run(PDO $pdo, string $sql, string $description): void {
    global $log;
    try {
        $pdo->exec($sql);
        $log[] = "  ✅  {$description}";
    } catch (Exception $e) {
        $log[] = "  ⚠️   {$description} — " . $e->getMessage();
    }
}

/** Returns set of existing column names for a table (lowercase). */
function cols(PDO $pdo, string $table): array {
    $rows = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    return array_map(fn($r) => strtolower($r['Field']), $rows);
}

/** Returns true if a table exists in the current database. */
function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables
                            WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

/** Add a column only if it doesn't exist yet. */
function addCol(PDO $pdo, string $table, string $col, string $definition, string $after = ''): void {
    global $log;
    if (!tableExists($pdo, $table)) {
        $log[] = "  ⏭️   SKIP addCol({$table}.{$col}) — table doesn't exist yet";
        return;
    }
    if (in_array(strtolower($col), cols($pdo, $table), true)) {
        $log[] = "  ──  {$table}.{$col} already exists";
        return;
    }
    $pos = $after ? " AFTER `{$after}`" : '';
    run($pdo, "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}{$pos}",
        "Added {$table}.{$col}");
}

// ── Section header ─────────────────────────────────────────────────────────────
function section(string $title): void {
    global $log;
    $log[] = "\n<b>{$title}</b>";
}

// =============================================================================
// STEP 1: CREATE MISSING TABLES
// =============================================================================
section('Step 1 — Create missing tables');

// book_currencies
if (!tableExists($pdo, 'book_currencies')) {
    run($pdo, "CREATE TABLE `book_currencies` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`    INT UNSIGNED NOT NULL,
        `code`       VARCHAR(10)  NOT NULL DEFAULT 'BDT',
        `symbol`     VARCHAR(5)   NOT NULL DEFAULT '৳',
        `name`       VARCHAR(80)  NULL,
        `is_default` TINYINT(1)   NOT NULL DEFAULT 1,
        `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created book_currencies');
} else {
    $log[] = "  ──  book_currencies already exists";
}

// invoice_method_options
if (!tableExists($pdo, 'invoice_method_options')) {
    run($pdo, "CREATE TABLE `invoice_method_options` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`    INT UNSIGNED NOT NULL,
        `type`       ENUM('delivery','payment') NOT NULL,
        `label`      VARCHAR(120) NOT NULL,
        `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created invoice_method_options');
} else {
    $log[] = "  ──  invoice_method_options already exists";
}

// payments
if (!tableExists($pdo, 'payments')) {
    run($pdo, "CREATE TABLE `payments` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `invoice_id` INT UNSIGNED NOT NULL,
        `amount`     DECIMAL(15,2) NOT NULL,
        `method`     VARCHAR(60)   NOT NULL DEFAULT 'cash',
        `date`       DATE NOT NULL,
        `note`       TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created payments');
} else {
    $log[] = "  ──  payments already exists";
}

// invoice_attachments
if (!tableExists($pdo, 'invoice_attachments')) {
    run($pdo, "CREATE TABLE `invoice_attachments` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `invoice_id` INT UNSIGNED NOT NULL,
        `filename`   VARCHAR(255) NOT NULL,
        `path`       VARCHAR(500) NOT NULL,
        `size`       INT UNSIGNED NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created invoice_attachments');
} else {
    $log[] = "  ──  invoice_attachments already exists";
}

// report_entries
if (!tableExists($pdo, 'report_entries')) {
    run($pdo, "CREATE TABLE `report_entries` (
        `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`      INT UNSIGNED NOT NULL,
        `type`         ENUM('in','out') NOT NULL,
        `category`     VARCHAR(60) NOT NULL,
        `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `description`  VARCHAR(255) NULL,
        `source_table` VARCHAR(60) NULL,
        `source_id`    INT UNSIGNED NULL,
        `date`         DATE NOT NULL,
        `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created report_entries');
} else {
    $log[] = "  ──  report_entries already exists";
}

// product_batches
if (!tableExists($pdo, 'product_batches')) {
    run($pdo, "CREATE TABLE `product_batches` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `product_id`    INT UNSIGNED NOT NULL,
        `book_id`       INT UNSIGNED NOT NULL,
        `barcode`       VARCHAR(60) NULL,
        `buy_price`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `sell_price`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `initial_qty`   DECIMAL(15,3) NOT NULL DEFAULT 0.000,
        `remaining_qty` DECIMAL(15,3) NOT NULL DEFAULT 0.000,
        `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created product_batches');
} else {
    $log[] = "  ──  product_batches already exists";
}

// funds
if (!tableExists($pdo, 'funds')) {
    run($pdo, "CREATE TABLE `funds` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`    INT UNSIGNED NOT NULL,
        `type`       ENUM('in','out') NOT NULL DEFAULT 'in',
        `title`      VARCHAR(255) NOT NULL,
        `amount`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `fund_date`  DATE NOT NULL,
        `note`       TEXT NULL,
        `created_by` INT UNSIGNED NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created funds');
} else {
    $log[] = "  ──  funds already exists";
}

// expense_categories
if (!tableExists($pdo, 'expense_categories')) {
    run($pdo, "CREATE TABLE `expense_categories` (
        `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`   INT UNSIGNED NOT NULL,
        `name`      VARCHAR(120) NOT NULL,
        `icon`      VARCHAR(60) NOT NULL DEFAULT 'fa-tag',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created expense_categories');
} else {
    $log[] = "  ──  expense_categories already exists";
}

// expenses
if (!tableExists($pdo, 'expenses')) {
    run($pdo, "CREATE TABLE `expenses` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`      INT UNSIGNED NOT NULL,
        `category_id`  INT UNSIGNED NULL DEFAULT NULL,
        `title`        VARCHAR(255) NOT NULL,
        `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `expense_date` DATE NOT NULL,
        `paid_to`      VARCHAR(120) NULL,
        `note`         TEXT NULL,
        `attachment`   VARCHAR(255) NULL,
        `created_by`   INT UNSIGNED NULL,
        `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created expenses');
} else {
    $log[] = "  ──  expenses already exists";
}

// dues
if (!tableExists($pdo, 'dues')) {
    run($pdo, "CREATE TABLE `dues` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`     INT UNSIGNED NOT NULL,
        `customer_id` INT UNSIGNED NULL DEFAULT NULL,
        `invoice_id`  INT UNSIGNED NULL DEFAULT NULL,
        `title`       VARCHAR(255) NOT NULL,
        `amount`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `due_date`    DATE NULL,
        `note`        TEXT NULL,
        `status`      ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
        `created_by`  INT UNSIGNED NULL,
        `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`)     REFERENCES `books`(`id`)     ON DELETE CASCADE,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`invoice_id`)  REFERENCES `invoices`(`id`)  ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created dues');
} else {
    $log[] = "  ──  dues already exists";
}

// due_payments
if (!tableExists($pdo, 'due_payments')) {
    run($pdo, "CREATE TABLE `due_payments` (
        `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `due_id`         INT UNSIGNED NOT NULL,
        `book_id`        INT UNSIGNED NOT NULL,
        `amount`         DECIMAL(15,2) NOT NULL,
        `payment_method` VARCHAR(60) NOT NULL DEFAULT 'cash',
        `note`           TEXT NULL,
        `paid_by`        INT UNSIGNED NULL,
        `paid_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`due_id`) REFERENCES `dues`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created due_payments');
} else {
    $log[] = "  ──  due_payments already exists";
}

// debts
if (!tableExists($pdo, 'debts')) {
    run($pdo, "CREATE TABLE `debts` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`     INT UNSIGNED NOT NULL,
        `supplier_id` INT UNSIGNED NULL DEFAULT NULL,
        `invoice_id`  INT UNSIGNED NULL DEFAULT NULL,
        `title`       VARCHAR(255) NOT NULL,
        `party`       VARCHAR(120) NULL,
        `amount`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `due_date`    DATE NULL,
        `note`        TEXT NULL,
        `status`      ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
        `created_by`  INT UNSIGNED NULL,
        `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`)     REFERENCES `books`(`id`)     ON DELETE CASCADE,
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`invoice_id`)  REFERENCES `invoices`(`id`)  ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created debts');
} else {
    $log[] = "  ──  debts already exists";
}

// debt_payments
if (!tableExists($pdo, 'debt_payments')) {
    run($pdo, "CREATE TABLE `debt_payments` (
        `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `debt_id`        INT UNSIGNED NOT NULL,
        `book_id`        INT UNSIGNED NOT NULL,
        `amount`         DECIMAL(15,2) NOT NULL,
        `payment_method` VARCHAR(60) NOT NULL DEFAULT 'cash',
        `note`           TEXT NULL,
        `paid_by`        INT UNSIGNED NULL,
        `paid_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`debt_id`) REFERENCES `debts`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created debt_payments');
} else {
    $log[] = "  ──  debt_payments already exists";
}

// coupons
if (!tableExists($pdo, 'coupons')) {
    run($pdo, "CREATE TABLE `coupons` (
        `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`        INT UNSIGNED NOT NULL,
        `name`           VARCHAR(120) NOT NULL,
        `code`           VARCHAR(30) NOT NULL,
        `discount_type`  ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
        `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `note`           TEXT NULL,
        `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
        `created_by`     INT UNSIGNED NULL,
        `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `uq_book_code` (`book_id`, `code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created coupons');
} else {
    $log[] = "  ──  coupons already exists";
}

// returns
if (!tableExists($pdo, 'returns')) {
    run($pdo, "CREATE TABLE `returns` (
        `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`         INT UNSIGNED NOT NULL,
        `invoice_id`      INT UNSIGNED NULL DEFAULT NULL,
        `type`            ENUM('sales_return','purchase_return') NOT NULL,
        `return_no`       VARCHAR(40) NOT NULL,
        `date`            DATE NOT NULL,
        `customer_id`     INT UNSIGNED NULL DEFAULT NULL,
        `supplier_id`     INT UNSIGNED NULL DEFAULT NULL,
        `subtotal`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `discount`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `delivery_charge` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_refund`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `remarks`         TEXT NULL,
        `status`          VARCHAR(20) NOT NULL DEFAULT 'completed',
        `created_by`      INT UNSIGNED NULL,
        `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `deleted_at`      DATETIME NULL DEFAULT NULL,
        FOREIGN KEY (`book_id`)     REFERENCES `books`(`id`)     ON DELETE CASCADE,
        FOREIGN KEY (`invoice_id`)  REFERENCES `invoices`(`id`)  ON DELETE SET NULL,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created returns');
} else {
    $log[] = "  ──  returns already exists";
}

// return_items
if (!tableExists($pdo, 'return_items')) {
    run($pdo, "CREATE TABLE `return_items` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `return_id`   INT UNSIGNED NOT NULL,
        `product_id`  INT UNSIGNED NULL DEFAULT NULL,
        `description` VARCHAR(255) NOT NULL,
        `qty`         DECIMAL(15,3) NOT NULL DEFAULT 0.000,
        `unit_price`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `line_total`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (`return_id`)  REFERENCES `returns`(`id`)  ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    'Created return_items');
} else {
    $log[] = "  ──  return_items already exists";
}

// =============================================================================
// STEP 2: ADD MISSING COLUMNS TO EXISTING TABLES
// =============================================================================
section('Step 2 — Add missing columns');

// ── books ────────────────────────────────────────────────────────────────────
addCol($pdo, 'books', 'theme_color', "VARCHAR(7) NULL DEFAULT '#1a6b4a'", 'color');
addCol($pdo, 'books', 'email',       'VARCHAR(180) NULL',                  'description');
addCol($pdo, 'books', 'phone',       'VARCHAR(30) NULL',                   'email');

// ── book_business_details ────────────────────────────────────────────────────
addCol($pdo, 'book_business_details', 'invoice_font',             "VARCHAR(60) NOT NULL DEFAULT 'DejaVu Sans'", 'footer_note');
addCol($pdo, 'book_business_details', 'inventory_method',         "ENUM('FIFO','LIFO') NOT NULL DEFAULT 'FIFO'", 'invoice_counter');
addCol($pdo, 'book_business_details', 'invoice_prefix_purchase',  "VARCHAR(20) NOT NULL DEFAULT 'PUR'",          'inventory_method');
addCol($pdo, 'book_business_details', 'invoice_counter_purchase', 'INT UNSIGNED NOT NULL DEFAULT 1',             'invoice_prefix_purchase');

// ── book_currencies ──────────────────────────────────────────────────────────
addCol($pdo, 'book_currencies', 'name', 'VARCHAR(80) NULL', 'symbol');

// ── invoices ─────────────────────────────────────────────────────────────────
addCol($pdo, 'invoices', 'points_discount', "DECIMAL(15,2) NOT NULL DEFAULT 0.00", 'discount');
addCol($pdo, 'invoices', 'delivery_charge', "DECIMAL(15,2) NOT NULL DEFAULT 0.00", 'points_discount');
addCol($pdo, 'invoices', 'handling_charge', "DECIMAL(15,2) NOT NULL DEFAULT 0.00", 'delivery_charge');
addCol($pdo, 'invoices', 'delivery_type',   "VARCHAR(30) NULL DEFAULT 'own'",       'handling_charge');
addCol($pdo, 'invoices', 'rounding',        "DECIMAL(10,4) NOT NULL DEFAULT 0.0000",'delivery_type');
addCol($pdo, 'invoices', 'note_customer',   'TEXT NULL',                             'notes');
addCol($pdo, 'invoices', 'note_seller',     'TEXT NULL',                             'note_customer');
addCol($pdo, 'invoices', 'delivery_method', 'VARCHAR(120) NULL',                     'note_seller');
addCol($pdo, 'invoices', 'payment_method',  'VARCHAR(120) NULL',                     'delivery_method');
addCol($pdo, 'invoices', 'theme_color',     "VARCHAR(7) NULL DEFAULT '#1a6b4a'",     'payment_method');
addCol($pdo, 'invoices', 'currency_symbol', "VARCHAR(5) NOT NULL DEFAULT '৳'",       'theme_color');
addCol($pdo, 'invoices', 'currency_code',   "VARCHAR(10) NOT NULL DEFAULT 'BDT'",    'currency_symbol');
addCol($pdo, 'invoices', 'public_token',    'VARCHAR(40) NULL',                      'currency_code');

// ── invoice_items ─────────────────────────────────────────────────────────────
addCol($pdo, 'invoice_items', 'variant', 'VARCHAR(120) NULL', 'description');

// ── products ──────────────────────────────────────────────────────────────────
addCol($pdo, 'products', 'product_code', 'VARCHAR(60) NULL', 'sku');

// ── funds ─────────────────────────────────────────────────────────────────────
addCol($pdo, 'funds', 'note',       'TEXT NULL',                                          'fund_date');
addCol($pdo, 'funds', 'created_by', 'INT UNSIGNED NULL',                                  'note');
addCol($pdo, 'funds', 'updated_at', 'DATETIME NULL ON UPDATE CURRENT_TIMESTAMP',           'created_at');

// ── expenses ──────────────────────────────────────────────────────────────────
addCol($pdo, 'expenses', 'category_id',  'INT UNSIGNED NULL DEFAULT NULL', 'book_id');
addCol($pdo, 'expenses', 'expense_date', 'DATE NOT NULL DEFAULT (CURRENT_DATE)',  'amount');
addCol($pdo, 'expenses', 'paid_to',      'VARCHAR(120) NULL',               'expense_date');
addCol($pdo, 'expenses', 'note',         'TEXT NULL',                        'paid_to');
addCol($pdo, 'expenses', 'attachment',   'VARCHAR(255) NULL',                'note');
addCol($pdo, 'expenses', 'created_by',   'INT UNSIGNED NULL',                'attachment');
addCol($pdo, 'expenses', 'updated_at',   'DATETIME NULL ON UPDATE CURRENT_TIMESTAMP', 'created_at');

// ── dues ──────────────────────────────────────────────────────────────────────
addCol($pdo, 'dues', 'customer_id', 'INT UNSIGNED NULL DEFAULT NULL',        'book_id');
addCol($pdo, 'dues', 'invoice_id',  'INT UNSIGNED NULL DEFAULT NULL',        'customer_id');
addCol($pdo, 'dues', 'paid_amount', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',  'amount');
addCol($pdo, 'dues', 'due_date',    'DATE NULL',                              'paid_amount');
addCol($pdo, 'dues', 'note',        'TEXT NULL',                              'due_date');
addCol($pdo, 'dues', 'status',      "ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid'", 'note');
addCol($pdo, 'dues', 'created_by',  'INT UNSIGNED NULL',                      'status');
addCol($pdo, 'dues', 'updated_at',  'DATETIME NULL ON UPDATE CURRENT_TIMESTAMP', 'created_at');

// ── debts ─────────────────────────────────────────────────────────────────────
addCol($pdo, 'debts', 'supplier_id', 'INT UNSIGNED NULL DEFAULT NULL',        'book_id');
addCol($pdo, 'debts', 'invoice_id',  'INT UNSIGNED NULL DEFAULT NULL',        'supplier_id');
addCol($pdo, 'debts', 'party',       'VARCHAR(120) NULL',                     'title');
addCol($pdo, 'debts', 'paid_amount', 'DECIMAL(15,2) NOT NULL DEFAULT 0.00',  'amount');
addCol($pdo, 'debts', 'due_date',    'DATE NULL',                              'paid_amount');
addCol($pdo, 'debts', 'note',        'TEXT NULL',                              'due_date');
addCol($pdo, 'debts', 'status',      "ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid'", 'note');
addCol($pdo, 'debts', 'created_by',  'INT UNSIGNED NULL',                      'status');
addCol($pdo, 'debts', 'updated_at',  'DATETIME NULL ON UPDATE CURRENT_TIMESTAMP', 'created_at');

// =============================================================================
// STEP 3: SEED DEFAULT DATA FOR EXISTING BOOKS
// =============================================================================
section('Step 3 — Seed default data');

// Seed BDT currency for any book that has none
if (tableExists($pdo, 'book_currencies')) {
    $books = $pdo->query("SELECT id FROM books WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
    $seeded = 0;
    foreach ($books as $bookId) {
        $has = $pdo->prepare("SELECT COUNT(*) FROM book_currencies WHERE book_id=?");
        $has->execute([$bookId]);
        if (!$has->fetchColumn()) {
            $pdo->prepare("INSERT INTO book_currencies (book_id,code,symbol,name,is_default,sort_order) VALUES (?,?,?,?,1,0)")
                ->execute([$bookId,'BDT','৳','Bangladeshi Taka']);
            $seeded++;
        }
    }
    $log[] = "  ✅  Seeded BDT currency for {$seeded} book(s) that had none";
}

// =============================================================================
// OUTPUT
// =============================================================================
$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">
    <title>Byabsayee Migration</title>
    <style>
        body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem;line-height:1.7}
        h1{color:#34d399;margin-bottom:1.5rem}
        b{color:#fbbf24;font-size:1.1em;display:block;margin-top:1rem}
        .ok{color:#34d399} .warn{color:#fb923c} .skip{color:#64748b}
        .done{background:#166534;color:#bbf7d0;padding:1rem 1.5rem;border-radius:8px;margin-top:2rem;font-size:1.1em}
    </style></head><body>
    <h1>🚀 Byabsayee — Database Migration</h1><pre>';
}

foreach ($log as $line) {
    if ($isCli) {
        echo strip_tags($line) . "\n";
    } else {
        $line = str_replace('✅', '<span class="ok">✅</span>', $line);
        $line = str_replace('⚠️', '<span class="warn">⚠️ </span>', $line);
        $line = str_replace('──', '<span class="skip">──</span>', $line);
        $line = str_replace('⏭️', '<span class="skip">⏭️ </span>', $line);
        echo $line . "\n";
    }
}

$msg = "\n✅  Migration complete. DELETE this file (migrate.php) from your server now!";
if ($isCli) {
    echo $msg . "\n";
} else {
    echo '</pre><div class="done">' . $msg . '</div></body></html>';
}
