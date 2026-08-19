</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> <?= trans('site_name') ?> — <?= trans('all_rights_reserved') ?></p>
</footer>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <?php if (!empty($extraJS)): ?>
      <?php foreach ((array)$extraJS as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
      <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
