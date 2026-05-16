<?php
/**
 * Byabsayee — Migration v3
 * Adds: employee_salary_payments
 * Run: yoursite.com/migrate_v3.php — then DELETE this file.
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
$pdo = new PDO(
    'mysql:host='.(getenv('DB_HOST')?:'db').';port='.(getenv('DB_PORT')?:'3306').';dbname='.(getenv('DB_NAME')?:'byabsayee_db').';charset=utf8mb4',
    getenv('DB_USER'), getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$log = [];
function run($pdo,$sql,$d){global $log;try{$pdo->exec($sql);$log[]="✅ $d";}catch(Exception $e){$log[]="⚠️ $d — ".$e->getMessage();}}
function has($pdo,$t){$s=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");$s->execute([$t]);return(bool)$s->fetchColumn();}

if (!has($pdo,'employee_salary_payments')) {
    run($pdo, "CREATE TABLE `employee_salary_payments` (
        `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `book_id`        INT UNSIGNED NOT NULL,
        `employee_id`    INT UNSIGNED NOT NULL,
        `expense_id`     INT UNSIGNED NULL DEFAULT NULL,
        `amount`         DECIMAL(15,2) NOT NULL,
        `period_label`   VARCHAR(40) NULL DEFAULT NULL COMMENT 'e.g. May 2026',
        `period_from`    DATE NULL DEFAULT NULL,
        `period_to`      DATE NULL DEFAULT NULL,
        `payment_method` VARCHAR(60) NOT NULL DEFAULT 'cash',
        `note`           TEXT NULL,
        `created_by`     INT UNSIGNED NULL,
        `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`book_id`)     REFERENCES `books`(`id`)     ON DELETE CASCADE,
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`expense_id`)  REFERENCES `expenses`(`id`)  ON DELETE SET NULL,
        INDEX `idx_book_emp` (`book_id`, `employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", "Created employee_salary_payments");
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migration v3</title>
<style>body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:30px;line-height:1.7}pre{background:#1e293b;padding:20px;border-radius:8px;border:1px solid #334155}</style>
</head><body>
<h1 style="color:#38bdf8">⚡ Migration v3</h1>
<pre><?php foreach($log as $l) echo htmlspecialchars($l)."\n"; ?><span style="color:#4ade80">✅ Done. Delete this file.</span></pre>
</body></html>
