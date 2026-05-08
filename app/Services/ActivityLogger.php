<?php

namespace App\Services;

use App\Core\DB;

/**
 * ActivityLogger
 *
 * Writes a structured row to the `activity_log` table for every
 * significant action in the application.
 *
 * Usage (anywhere after Auth is resolved):
 *   $logger = new ActivityLogger();
 *   $logger->log($bookId, $userId, 'invoice.created', 'Invoice', $id, 'Invoice #000123 created');
 *
 * Or statically:
 *   ActivityLogger::write($bookId, $userId, 'expense.deleted', 'Expense', $id, '...');
 */
class ActivityLogger
{
    private static ?DB $db = null;

    // ─────────────────────────────────────────────────────────────────────────
    //  Instance method (convenient for DI-style controllers)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param int|null    $bookId      The business book this action belongs to (null = global)
     * @param int|null    $userId      User who performed the action
     * @param string      $action      Dot-notation event, e.g. 'invoice.created'
     * @param string|null $subjectType Model class name, e.g. 'Invoice'
     * @param int|null    $subjectId   Primary key of the affected record
     * @param string      $description Human-readable summary shown in the activity feed
     * @param array|null  $oldData     Snapshot of data before the change (for updates)
     * @param array|null  $newData     Snapshot of data after the change
     */
    public function log(
        ?int    $bookId,
        ?int    $userId,
        string  $action,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        string  $description = '',
        ?array  $oldData     = null,
        ?array  $newData     = null
    ): void {
        self::write(
            $bookId, $userId, $action,
            $subjectType, $subjectId,
            $description, $oldData, $newData
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Static method (use without instantiation)
    // ─────────────────────────────────────────────────────────────────────────
    public static function write(
        ?int    $bookId,
        ?int    $userId,
        string  $action,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        string  $description = '',
        ?array  $oldData     = null,
        ?array  $newData     = null
    ): void {
        try {
            $db = self::getDB();

            $ip        = self::resolveIp();
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $db->query(
                "INSERT INTO activity_log
                    (book_id, user_id, action, subject_type, subject_id,
                     description, old_data, new_data, ip_address, user_agent)
                 VALUES
                    (:book_id, :user_id, :action, :subject_type, :subject_id,
                     :description, :old_data, :new_data, :ip, :ua)",
                [
                    ':book_id'      => $bookId,
                    ':user_id'      => $userId,
                    ':action'       => $action,
                    ':subject_type' => $subjectType,
                    ':subject_id'   => $subjectId,
                    ':description'  => $description,
                    ':old_data'     => $oldData  !== null ? json_encode($oldData,  JSON_UNESCAPED_UNICODE) : null,
                    ':new_data'     => $newData  !== null ? json_encode($newData,  JSON_UNESCAPED_UNICODE) : null,
                    ':ip'           => $ip,
                    ':ua'           => $userAgent,
                ]
            );
        } catch (\Throwable $e) {
            // Never let logging break the application — fail silently
            error_log('[ActivityLogger] Failed to write log: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Helpers for controllers that need a diff snapshot
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns only the keys that changed between $before and $after.
     * Useful for update logging: only store what actually changed.
     */
    public static function diff(array $before, array $after): array
    {
        $changed = [];
        foreach ($after as $key => $newVal) {
            $oldVal = $before[$key] ?? null;
            if ((string)$oldVal !== (string)$newVal) {
                $changed[$key] = ['from' => $oldVal, 'to' => $newVal];
            }
        }
        return $changed;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Retrieve log entries for the activity feed (book dashboard)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch the most recent $limit entries for a book.
     * Returns enriched rows with user name and a formatted icon.
     */
    public static function recent(int $bookId, int $limit = 20): array
    {
        $db = self::getDB();

        $rows = $db->fetchAll(
            "SELECT al.*,
                    u.name  AS user_name,
                    u.photo AS user_photo
               FROM activity_log al
               LEFT JOIN users u ON u.id = al.user_id
              WHERE al.book_id = :book_id
              ORDER BY al.created_at DESC
              LIMIT :limit",
            [':book_id' => $bookId, ':limit' => $limit]
        );

        return array_map([self::class, 'decorateRow'], $rows);
    }

    /**
     * Fetch recent global (non-book-specific) log entries.
     */
    public static function recentGlobal(int $limit = 50): array
    {
        $db = self::getDB();

        $rows = $db->fetchAll(
            "SELECT al.*,
                    u.name  AS user_name,
                    b.name  AS book_name
               FROM activity_log al
               LEFT JOIN users u ON u.id  = al.user_id
               LEFT JOIN books b ON b.id  = al.book_id
              ORDER BY al.created_at DESC
              LIMIT :limit",
            [':limit' => $limit]
        );

        return array_map([self::class, 'decorateRow'], $rows);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Action → icon/colour mapping for the activity feed UI
    // ─────────────────────────────────────────────────────────────────────────
    private static function decorateRow(array $row): array
    {
        $map = [
            // Invoices
            'invoice.created'   => ['fa-file-invoice',       'text-primary'],
            'invoice.updated'   => ['fa-file-pen',           'text-warning'],
            'invoice.deleted'   => ['fa-file-circle-xmark',  'text-danger'],
            'invoice.paid'      => ['fa-circle-check',       'text-success'],
            // Sales / Purchases
            'sale.created'      => ['fa-cart-plus',          'text-success'],
            'purchase.created'  => ['fa-cart-shopping',      'text-info'],
            // Dues & Debts
            'due.created'       => ['fa-hand-holding-dollar','text-warning'],
            'due.payment'       => ['fa-money-bill-wave',    'text-success'],
            'due.cancelled'     => ['fa-ban',                'text-secondary'],
            'debt.created'      => ['fa-hand-holding-dollar','text-danger'],
            'debt.payment'      => ['fa-money-bill-wave',    'text-success'],
            // Funds
            'fund.in'           => ['fa-circle-arrow-down',  'text-success'],
            'fund.out'          => ['fa-circle-arrow-up',    'text-danger'],
            'fund.updated'      => ['fa-pen',                'text-warning'],
            'fund.deleted'      => ['fa-trash',              'text-danger'],
            // Expenses
            'expense.created'   => ['fa-receipt',            'text-warning'],
            'expense.updated'   => ['fa-pen',                'text-warning'],
            'expense.deleted'   => ['fa-trash',              'text-danger'],
            // Customers / Suppliers / Products
            'customer.created'  => ['fa-user-plus',          'text-primary'],
            'customer.updated'  => ['fa-user-pen',           'text-warning'],
            'supplier.created'  => ['fa-truck',              'text-info'],
            'product.created'   => ['fa-box',                'text-primary'],
            'product.updated'   => ['fa-box-open',           'text-warning'],
            'stock.adjusted'    => ['fa-warehouse',          'text-secondary'],
            // Auth
            'auth.login'        => ['fa-right-to-bracket',   'text-success'],
            'auth.logout'       => ['fa-right-from-bracket', 'text-secondary'],
        ];

        $action           = $row['action'] ?? '';
        [$icon, $colour]  = $map[$action] ?? ['fa-circle-dot', 'text-secondary'];

        $row['icon']       = $icon;
        $row['icon_class'] = $colour;

        // Parse JSON snapshots back to arrays if present
        if (isset($row['old_data']) && is_string($row['old_data'])) {
            $row['old_data'] = json_decode($row['old_data'], true);
        }
        if (isset($row['new_data']) && is_string($row['new_data'])) {
            $row['new_data'] = json_decode($row['new_data'], true);
        }

        return $row;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Internal utilities
    // ─────────────────────────────────────────────────────────────────────────
    private static function getDB(): DB
    {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }
        return self::$db;
    }

    /**
     * Resolve the real client IP, respecting common proxy headers
     * set by the Nginx config (fastcgi_param HTTP_X_REAL_IP).
     */
    private static function resolveIp(): string
    {
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // X-Forwarded-For can be a comma-separated list; take the first
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return '0.0.0.0';
    }
}
