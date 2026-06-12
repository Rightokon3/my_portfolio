<?php
// ── Database config — change these to match your host ──
define('DB_HOST',    'localhost');
define('DB_NAME',    'nfo_portfolio');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

define('SESSION_TIMEOUT', 3600);   // 1 hour

// ── PDO singleton ──
class Database {
    private static $inst = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed. Check config.php']));
        }
    }

    public static function get() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function query($sql, $p = []) {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        return $s;
    }

    public function all($sql, $p = [])  { return $this->query($sql,$p)->fetchAll(); }
    public function one($sql, $p = [])  { return $this->query($sql,$p)->fetch(); }
    public function insert($sql, $p=[]) { $this->query($sql,$p); return $this->pdo->lastInsertId(); }
    public function count($t, $w='', $p=[]) {
        $row = $this->one("SELECT COUNT(*) c FROM $t".($w?" WHERE $w":''), $p);
        return $row['c'] ?? 0;
    }
}

// ── Auth ──
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) return false;
    if (time() - ($_SESSION['last_active'] ?? 0) > SESSION_TIMEOUT) { session_destroy(); return false; }
    $_SESSION['last_active'] = time();
    return true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) { http_response_code(401); die(json_encode(['error'=>'Unauthorized'])); }
        header('Location: ../admin/login.php'); exit;
    }
}

// ── Utilities ──
function clean($v)  { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }
function json($d, $c=200) { http_response_code($c); header('Content-Type: application/json'); echo json_encode($d); exit; }

function logAction($action, $details = '') {
    try {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $aid = $_SESSION['admin_id'] ?? null;
        Database::get()->query(
            "INSERT INTO activity_log (admin_id, action, details, ip_address) VALUES (?,?,?,?)",
            [$aid, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    } catch(Exception $e) {}
}
?>
