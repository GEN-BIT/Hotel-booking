<?php require __DIR__ . '/../../includes/admin-header.php';
require_role_any(['admin']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT u.full_name, s.permissions FROM users u JOIN staff s ON s.user_id = u.id WHERE u.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) die('Staff member not found.');

$available = ['manage_rooms','manage_bookings','manage_guests','manage_payments','view_reports'];
$current = $row['permissions'] ? explode(',', $row['permissions']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = $_POST['permissions'] ?? [];
    $pdo->prepare('UPDATE staff SET permissions = ? WHERE user_id = ?')->execute([implode(',', $selected), $id]);
    header('Location: index.php');
    exit;
}
?>
<h1>Permissions — <?= htmlspecialchars($row['full_name']) ?></h1>
<form method="post">
          <?= csrf_field() ?>
    <?php foreach ($available as $perm): ?>
    <label class="checkbox"><input type="checkbox" name="permissions[]" value="<?= $perm ?>" <?= in_array($perm, $current) ? 'checked' : '' ?>> <?= str_replace('_',' ',ucfirst($perm)) ?></label>
    <?php endforeach; ?>
    <button type="submit">Save Permissions</button>
</form>
<?php require __DIR__ . '/../../includes/admin-footer.php'; ?>
