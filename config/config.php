<?php
session_start();
define('BASE_URL', 'http://localhost/hotel-booking/');
define('DEFAULT_LANG', 'en');
define('SUPPORTED_LANGS', ['en', 'rw', 'fr']);
require_once __DIR__ . '/db.php';

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