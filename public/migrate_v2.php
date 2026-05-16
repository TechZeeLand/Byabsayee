<?php
/**
 * Byabsayee — Migration v2
 * Adds: designations, book_members, employee_invitations, notifications
 * Run via browser: http://your-site/migrate_v2.php
 * Or CLI: php migrate_v2.php
 * Safe to run multiple times. DELETE after use.
 */

define('BASE_PATH', __DIR__);

$env = BASE_PATH . '/.env';
if (file_exists($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$host   = getenv('DB_HOST') ?: 'mariadb';
$port   = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'byabsayee_db';
$user   = getenv('DB_USER') ?: 'byabsayee_user';
$pass   = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("❌  Cannot connect: " . $e->getMessage());
}

$log = [];

function run(PDO $pdo, string $sql, string $desc): void {
    global $log;
    try { $pdo->exec($sql); $log[] = "✅  {$desc}"; }
    catch (Exception $e) { $log[] = "⚠️  {$desc} — " . $e->getMessage(); }
}

function tableExists(PDO $pdo, string $table): bool {
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $s->execute([$table]);
    return (bool)$s->fetchColumn();
}

function cols(PDO $pdo, string $table): array {
    $rows = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    return array_map(fn($r) => strtolower($r['Field']), $rows);
}

function addCol(PDO $pdo, string $table, string $col, string $def, string $after = ''): void {
    global $log;
    if (!tableExists($pdo, $table)) { $log[] = "⏭️  SKIP {$table}.{$col}"; return; }
    if (in_array(strtolower($col), cols($pdo, $table))) { $log[] = "──  {$table}.{$col} exists"; return; }
    $pos = $after ? " AFTER `{$after}`" : '';
    run($pdo, "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}{$pos}", "Added {$table}.{$col}");
}

// ── 1. designations ──────────────────────────────────────────────────────────
if (!tableExists($pdo, 'designations')) {
    run($pdo, "CREATE TABLE `designations` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`     INT UNSIGNED NOT NULL,
        `name`        VARCHAR(80)  NOT NULL,
        `permissions` JSON         NOT NULL DEFAULT ('{}'),
        `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
        INDEX `idx_book` (`book_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Created designations table");
}

// ── 2. book_members ──────────────────────────────────────────────────────────
if (!tableExists($pdo, 'book_members')) {
    run($pdo, "CREATE TABLE `book_members` (
        `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`         INT UNSIGNED NOT NULL,
        `user_id`         INT UNSIGNED NOT NULL,
        `employee_id`     INT UNSIGNED NULL DEFAULT NULL,
        `designation_id`  INT UNSIGNED NULL DEFAULT NULL,
        `designation_name` VARCHAR(80) NULL DEFAULT NULL,
        `permissions`     JSON         NOT NULL DEFAULT ('{}'),
        `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_book_user` (`book_id`, `user_id`),
        FOREIGN KEY (`book_id`)    REFERENCES `books`(`id`)    ON DELETE CASCADE,
        FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`designation_id`) REFERENCES `designations`(`id`) ON DELETE SET NULL,
        INDEX `idx_book` (`book_id`),
        INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Created book_members table");
}

// ── 3. employee_invitations ──────────────────────────────────────────────────
if (!tableExists($pdo, 'employee_invitations')) {
    run($pdo, "CREATE TABLE `employee_invitations` (
        `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`          INT UNSIGNED NOT NULL,
        `invited_by`       INT UNSIGNED NOT NULL,
        `email`            VARCHAR(180) NOT NULL,
        `user_id`          INT UNSIGNED NULL DEFAULT NULL,
        `designation_id`   INT UNSIGNED NULL DEFAULT NULL,
        `designation_name` VARCHAR(80)  NULL DEFAULT NULL,
        `permissions`      JSON         NOT NULL DEFAULT ('{}'),
        `token`            VARCHAR(128) NOT NULL UNIQUE,
        `status`           ENUM('pending','accepted','rejected','expired') NOT NULL DEFAULT 'pending',
        `expires_at`       DATETIME     NOT NULL,
        `responded_at`     DATETIME     NULL DEFAULT NULL,
        `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`)    REFERENCES `books`(`id`)    ON DELETE CASCADE,
        FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`)    ON DELETE CASCADE,
        FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL,
        FOREIGN KEY (`designation_id`) REFERENCES `designations`(`id`) ON DELETE SET NULL,
        INDEX `idx_book`  (`book_id`),
        INDEX `idx_email` (`email`),
        INDEX `idx_token` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Created employee_invitations table");
}

// ── 4. notifications ─────────────────────────────────────────────────────────
if (!tableExists($pdo, 'notifications')) {
    run($pdo, "CREATE TABLE `notifications` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT UNSIGNED NOT NULL,
        `book_id`    INT UNSIGNED NULL DEFAULT NULL,
        `type`       VARCHAR(60)  NOT NULL DEFAULT 'info',
        `title`      VARCHAR(255) NOT NULL,
        `body`       TEXT         NULL,
        `action_url` VARCHAR(255) NULL DEFAULT NULL,
        `data`       JSON         NULL DEFAULT NULL,
        `read_at`    DATETIME     NULL DEFAULT NULL,
        `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
        INDEX `idx_user`    (`user_id`),
        INDEX `idx_unread`  (`user_id`, `read_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Created notifications table");
}

// ── 5. Add missing columns to employees ──────────────────────────────────────
addCol($pdo, 'employees', 'designation_id',   'INT UNSIGNED NULL DEFAULT NULL AFTER `role_id`', '');
addCol($pdo, 'employees', 'designation_name', 'VARCHAR(80) NULL DEFAULT NULL AFTER `designation_id`', '');
addCol($pdo, 'employees', 'invitation_id',    'INT UNSIGNED NULL DEFAULT NULL', '');
addCol($pdo, 'employees', 'address',          'TEXT NULL DEFAULT NULL AFTER `email`', '');
addCol($pdo, 'employees', 'nid_number',       'VARCHAR(30) NULL DEFAULT NULL AFTER `nid_image`', '');
addCol($pdo, 'employees', 'emergency_contact','VARCHAR(120) NULL DEFAULT NULL', '');
addCol($pdo, 'employees', 'notes',            'TEXT NULL DEFAULT NULL', '');

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Byabsayee Migration v2</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:30px;line-height:1.7}
h1{color:#38bdf8;margin-bottom:24px}
.ok{color:#4ade80}.warn{color:#fbbf24}.info{color:#94a3b8}
pre{background:#1e293b;padding:20px;border-radius:8px;border:1px solid #334155}
</style>
</head>
<body>
<h1>⚡ Byabsayee — Migration v2</h1>
<pre>
<?php foreach ($log as $l): ?>
<?= htmlspecialchars($l) . "\n" ?>
<?php endforeach; ?>

<span class="ok">✅ Migration complete. Delete this file now.</span>
</pre>
</body>
</html>
