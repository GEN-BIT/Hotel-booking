<?php
session_start();
define('BASE_URL', 'http://localhost/hotel-booking/');
require_once __DIR__ . '/db.php';

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
