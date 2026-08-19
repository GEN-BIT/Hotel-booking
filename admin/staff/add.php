<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';
    $position = trim($_POST['position'] ?? '');

    if (!$name || !$email || !$password) {
        $error = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already in use.';
        } else {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (role_id, full_name, email, password_hash, is_verified)
                 VALUES ((SELECT id FROM roles WHERE name = ?), ?, ?, ?, 1)'
            );
            $stmt->execute([$role, $name, $email, $hash]);
            $userId = $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO staff (user_id, position, is_active) VALUES (?, ?, 1)');
            $stmt->execute([$userId, $position]);
            $pdo->commit();
            header('Location: index.php');
            exit;
        }
    }
}
?>
<h1>Add Staff</h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>Full Name <input type="text" name="full_name" required></label>
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <label>Role
        <select name="role">
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
        </select>
    </label>
    <label>Position <input type="text" name="position"></label>
    <button type="submit">Add Staff</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
