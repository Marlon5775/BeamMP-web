<header>
    <h1 class="headertitle"><?php echo htmlspecialchars($h1Title ?? 'Mon Site'); ?></h1>

    <!-- 🔁 Sélecteur de langue -->
    <div class="language-selector">
        <a href="?lang=fr">🇫🇷 FR</a> | <a href="?lang=en">🇬🇧 EN</a> | <a href="?lang=de">🇩🇪 DE</a>
    </div>

    <?php if (!empty($buttons) && is_array($buttons)): ?>
        <div class="btn-container">
            <?php foreach ($buttons as $button): ?>
                <a href="<?php echo htmlspecialchars($button['link']); ?>" class="btn-link" title="<?php echo htmlspecialchars($button['title']); ?>">
                    <img src="<?php echo htmlspecialchars($button['icon']); ?>" alt="<?php echo htmlspecialchars($button['title']); ?>" class="btn-icon">
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-logged-in">
            <span><?= t('header_welcome') ?> <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> !</span>
            <form action="/auth/logout.php" method="POST" style="display: inline;">
                <button type="submit" class="btn btn-logout"><?= t('header_logout') ?></button>
            </form>
        </div>
    <?php else: ?>
        <div class="user-login">
            <button class="btn btn-login"><?= t('header_login') ?></button>
        </div>
    <?php endif; ?>
</header>
