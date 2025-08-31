<?php
// Trouver la racine d'instance
function findInstanceRoot($maxLevels = 5) {
    $dir = dirname($_SERVER['SCRIPT_FILENAME']);
    for ($i = 0; $i < $maxLevels; $i++) {
        if (file_exists($dir . '/bootstrap.php')) return $dir;
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    throw new Exception("Instance root (bootstrap.php) not found");
}
$instanceRoot = findInstanceRoot(5);
require_once $instanceRoot . '/bootstrap.php';
require_once __DIR__ . '/includes/BeamMP/i18n.php';
require_once __DIR__ . '/includes/roles.php';

// --- Redirection automatique si connecté ---
if (isset($_SESSION['user_id'])) {
    header('Location: /pages/BeamMP/BeamMP.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('title') ?></title>
    <link rel="stylesheet" href="assets/css/root.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.webp">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.webp">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.webp">
    <link rel="manifest" href="/site.webmanifest">
</head>

<body>
    <div class="page-wrapper">
    <?php
    $title = 'Accueil';
    $h1end = $_ENV['TITLE'] ?? 'BeamMP';
    $h1Title = 'Gestion serveur BeamMP - ' . $h1end;
    include __DIR__ . '/includes/header.php';
    ?>

    <main class="main-container">
            <!-- Section visible avant connexion -->
            <div class="welcome-container">
        <h2><?= t('welcome_h2') ?></h2>
        <p>
            <?= t('welcome_p') ?>
        </p>
        <ul>
            <li>
                <strong><?= t('beammp_title') ?></strong><br>
                <?= t('beammp_description') ?>
            </li>
            
        </ul>
    </div>
    </main>

    <!-- Modale de Connexion -->
    <div id="dialogue-bg" class="dialogue-background"></div>
    <div id="dialogue" class="dialogue-box">
        <button class="btn-close">✖</button>
        <h2>Connexion</h2>
        <form action="auth/auth.php" method="POST">
            <input type="text" name="username" placeholder="<?= t('username') ?>" required>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="<?= t('password') ?>" required>
                <button type="button" class="toggle-password" aria-label="Afficher/Masquer le mot de passe">👁️</button>
            </div>
            <button type="submit" class="btn btn-submit"><?= t('submit') ?></button>
        </form>
        <p class="error-message" style="display: none;">Erreur de connexion.</p>
    </div>

        <?php if (isset($_SESSION['login_error'])): ?>
            <p class="error-message"><?php echo htmlspecialchars($_SESSION['login_error']); ?></p>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</div>
    <?php include __DIR__ . '/includes/BeamMP/traductions_js.php'; ?>
    <script src="assets/js/index.js"></script>
</body>
</html>
