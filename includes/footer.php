</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Hotel Booking System</p>
</footer>
<script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <?php if (!empty($extraJS)): ?>
      <?php foreach ((array)$extraJS as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
      <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
