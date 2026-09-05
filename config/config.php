<?php
session_start();

// Load Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

// Detect localhost
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Build BASE_URL dynamically based on current request protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = '/hotel-booking/';

if ($isLocalhost) {
    define('BASE_URL', $protocol . '://' . $host . $basePath);
} else {
    define('BASE_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost/hotel-booking/', '/') . '/');
}
define('DEFAULT_LANG', 'en');
define('SUPPORTED_LANGS', ['en', 'rw', 'fr']);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_ENV['FORCE_HTTPS']) && $_ENV['FORCE_HTTPS'] === 'true') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require_once __DIR__ . '/db.php';

/**
 * CSRF Token Functions
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token']) || time() > ($_SESSION['csrf_expires'] ?? 0)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_expires'] = time() + ($_ENV['CSRF_TOKEN_LIFETIME'] ?? 1800);
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    if (time() > ($_SESSION['csrf_expires'] ?? 0)) {
        unset($_SESSION['csrf_token']);
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate Limiting Functions
 */
function check_rate_limit($action, $maxAttempts = 5, $windowSeconds = 300) {
    $key = 'rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
    }
    
    $data = $_SESSION[$key];
    if ($now - $data['first_attempt'] > $windowSeconds) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => $now];
        return true;
    }
    
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Input sanitization helper
 */
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

/**
 * Current user staff permissions
 */
function current_permissions() {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    static $permissions = null;
    if ($permissions !== null) {
        return $permissions;
    }
    
    global $pdo;
    $stmt = $pdo->prepare('SELECT permissions FROM staff WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $permString = $stmt->fetchColumn();
    
    if ($permString) {
        $permissions = array_filter(array_map('trim', explode(',', $permString)));
    } else {
        $permissions = [];
    }
    
    return $permissions;
}

function has_permission($permission) {
    $role = current_role();
    if ($role === 'admin') {
        return true;
    }
    return in_array($permission, current_permissions(), true);
}

function require_permission($permission) {
    if (!has_permission($permission)) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Get the current language from session or default.
 */
function current_lang() {
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
    if (!in_array($lang, SUPPORTED_LANGS, true)) {
        $lang = DEFAULT_LANG;
    }
    return $lang;
}

/**
 * Load translations for the current language.
 */
function load_translations() {
    static $translations = null;
    static $loadedLang = null;

    $lang = current_lang();
    if ($translations !== null && $loadedLang === $lang) {
        return $translations;
    }

    $file = __DIR__ . '/../assets/lang/' . $lang . '.php';
    $translations = file_exists($file) ? require $file : [];
    $loadedLang = $lang;

    return $translations;
}

/**
 * Translate a key. Falls back to English, then to the key itself.
 */
function trans($key, array $params = []) {
    $lang = current_lang();
    $translations = load_translations();

    $text = $translations[$key] ?? null;

    // Fallback to English if key not found in current language
    if ($text === null && $lang !== 'en') {
        $enFile = __DIR__ . '/../assets/lang/en.php';
        $en = file_exists($enFile) ? require $enFile : [];
        $text = $en[$key] ?? $key;
    } elseif ($text === null) {
        $text = $key;
    }

    // Replace placeholders like {name}
    if ($params) {
        foreach ($params as $k => $v) {
            $text = str_replace('{' . $k . '}', (string)$v, $text);
        }
    }

    return $text;
}

/**
 * Handle language switching via ?lang= query parameter.
 */
function handle_language_switch() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        $_SESSION['lang'] = $_GET['lang'];
        // Remove lang param from URL and redirect to keep it clean
        if (isset($_SERVER['REQUEST_URI'])) {
            $url = strtok($_SERVER['REQUEST_URI'], '?');
            $params = $_GET;
            unset($params['lang']);
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
            header('Location: ' . $url);
            exit;
        }
    }
}

handle_language_switch();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_role() {
    return $_SESSION['role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    if (current_role() !== $role) {
        http_response_code(403);
        die('Access denied.');
    }
}

function require_role_any(array $roles) {
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        die('Access denied.');
    }
}

function get_setting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

function set_setting($pdo, $key, $value) {
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

function log_activity($pdo, $action, $description) {
    $userId = $_SESSION['user_id'] ?? null;
    $name = $_SESSION['full_name'] ?? 'Guest';
    $stmt = $pdo->prepare('INSERT INTO activity_log (actor_user_id, actor_name, action, description) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $name, $action, $description]);
}

/**
 * Multi-currency support
 */
function get_currency() {
    return get_setting($GLOBALS['pdo'], 'currency', 'USD');
}

function currency_symbol($currency = null) {
    $currency = $currency ?: get_currency();
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'RWF' => 'RWF ',
    ];
    return $symbols[$currency] ?? '$';
}

function format_currency($amount, $currency = null) {
    $currency = $currency ?: get_currency();
    $symbol = currency_symbol($currency);
    
    if ($currency === 'RWF') {
        return $symbol . number_format((int)round($amount), 0);
    }
    
    return $symbol . number_format((float)$amount, 2);
}

/**
 * Get seasonal price for a room type on given dates
 */
function get_seasonal_price($pdo, $roomTypeId, $checkIn, $checkOut) {
    $stmt = $pdo->prepare(
        'SELECT price FROM room_type_pricing
         WHERE room_type_id = ? AND valid_from <= ? AND valid_until >= ?
         ORDER BY valid_from DESC LIMIT 1'
    );
    $stmt->execute([$roomTypeId, $checkIn, $checkOut]);
    $price = $stmt->fetchColumn();
    return $price !== false ? (float)$price : null;
}