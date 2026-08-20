<?php
http_response_code(404);
require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/header.php';
?>

<div class="site-main" style="text-align: center; padding: 4rem 1rem;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h1 style="font-size: 6rem; margin: 0; color: var(--color-accent); font-weight: 700;">404</h1>
        <h2 style="margin: 1rem 0; color: var(--color-primary);"><?= trans('page_not_found') ?></h2>
        <p style="color: var(--color-muted); font-size: 1.1rem; margin-bottom: 2rem;">
            The page you're looking for doesn't exist or has been moved.
        </p>
        
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>" class="cta">Go Home</a>
            <a href="<?= BASE_URL ?>rooms/index.php" class="cta">Browse Rooms</a>
        </div>
        
        <div style="margin-top: 3rem; padding: 2rem; background: var(--color-surface); border-radius: var(--radius); border: 1px solid var(--color-border);">
            <h3 style="margin: 0 0 1rem; color: var(--color-primary);">Looking for something?</h3>
            <p style="color: var(--color-muted); margin-bottom: 1.5rem;">
                Try searching for rooms or check out our popular destinations.
            </p>
            <form method="get" action="<?= BASE_URL ?>rooms/search.php" style="max-width: 400px; margin: 0 auto; display: flex; gap: 0.5rem;">
                <input type="text" name="q" placeholder="Search rooms..." style="flex: 1; padding: 0.75rem 1rem; border: 1px solid var(--color-border); border-radius: var(--radius); background: var(--color-bg); color: var(--color-text);">
                <button type="submit" class="cta" style="padding: 0.75rem 1.5rem;">Search</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
