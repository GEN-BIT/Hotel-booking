<?php require_once __DIR__ . '/../config/config.php';
require_login();

if (empty($_SESSION['pending_booking'])) {
    header('Location: ' . BASE_URL . 'rooms/index.php');
    exit;
}

$services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['pending_booking']['special_requests'] = trim($_POST['special_requests'] ?? '');
    $_SESSION['pending_booking']['extra_guests'] = $_POST['guest_name'] ?? [];
    $_SESSION['pending_booking']['selected_services'] = $_POST['services'] ?? [];
    header('Location: ' . BASE_URL . 'booking/review.php');
    exit;
}

$pb = $_SESSION['pending_booking'];
require __DIR__ . '/../includes/header.php';
?>
<h1><?= trans('guest_information') ?></h1>
<form method="post">
          <?= csrf_field() ?>
    <label><?= trans('special_requests_label') ?> <input type="text" name="special_requests" value="<?= htmlspecialchars($pb['special_requests'] ?? '') ?>"></label>
    <?php for ($i = 1; $i < $pb['guests']; $i++): ?>
        <label><?= trans('guest_name') ?> <?= $i + 1 ?> <input type="text" name="guest_name[]"></label>
    <?php endfor; ?>

    <?php if ($services): ?>
    <fieldset style="margin-top:1.5rem;">
        <legend><?= trans('extra_services_optional') ?></legend>
        <?php foreach ($services as $s): ?>
        <label class="checkbox">
            <input type="checkbox" name="services[<?= (int)$s['id'] ?>]" value="1">
            <?= htmlspecialchars($s['name']) ?> — $<?= number_format($s['price'], 2) ?>
            <?php if ($s['description']): ?>
                <small style="display:block; color:var(--color-muted);"><?= htmlspecialchars($s['description']) ?></small>
            <?php endif; ?>
        </label>
        <?php endforeach; ?>
    </fieldset>
    <?php endif; ?>

    <button type="submit"><?= trans('continue_to_review') ?></button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
