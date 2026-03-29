<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim($base_path, '/') . '/assets/style.css', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
</head>
<body>
<?php
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$isAdminArea = str_contains($requestUri, '/admin') && !str_contains($requestUri, '/admin/login');
?>
<header class="site-header">
    <div class="wrap">
        <div class="brand">
            <span class="brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 11.2 12 4l9 7.2v8.3a1.5 1.5 0 0 1-1.5 1.5h-4.8v-6h-5.4v6H4.5A1.5 1.5 0 0 1 3 19.5v-8.3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                </svg>
            </span>
            <?= htmlspecialchars($app['name'] ?? 'ДМаренда', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </div>
        <?php if ($isAdminArea): ?>
            <nav class="nav">
                <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/invitations', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Приглашения</a>
                <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/contracts', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Договоры</a>
                <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/payments', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Платежи</a>
                <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/content', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Контент</a>
                <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/admin/logout', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Выход</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="wrap main-content">
    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars((string) $flash_success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars((string) $flash_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <?= $content ?>
</main>
<!-- Куки-баннер -->
<div id="cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1e1e2e;color:#fff;padding:16px 24px;z-index:9999;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;font-size:14px;line-height:1.5;">
    <p style="margin:0;max-width:760px;">
        Мы используем файлы cookie для обеспечения работы сайта, авторизации и улучшения качества обслуживания.
        Продолжая использовать сайт, вы соглашаетесь с нашей
        <a href="<?= htmlspecialchars(rtrim($base_path, '/') . '/privacy', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" style="color:#7dd3fc;text-decoration:underline;" target="_blank" rel="noopener">политикой конфиденциальности</a>
        и использованием cookies в соответствии с Федеральным законом №152-ФЗ «О персональных данных».
    </p>
    <button id="cookie-accept" style="background:#3b82f6;color:#fff;border:none;padding:10px 24px;border-radius:6px;cursor:pointer;font-size:14px;white-space:nowrap;">Принять</button>
</div>
<script>
(function(){
    var banner = document.getElementById('cookie-banner');
    var btn = document.getElementById('cookie-accept');
    if (!banner || !btn) return;

    var consentKey = 'dmarenda_cookie_consent';

    function canUseLocalStorage() {
        try {
            var probe = '__cookie_probe__';
            localStorage.setItem(probe, '1');
            localStorage.removeItem(probe);
            return true;
        } catch (e) {
            return false;
        }
    }

    function getCookie(name) {
        var parts = ('; ' + document.cookie).split('; ' + name + '=');
        if (parts.length !== 2) return '';
        return decodeURIComponent(parts.pop().split(';').shift() || '');
    }

    function setCookie(name, value, days) {
        var expires = new Date(Date.now() + days * 864e5).toUTCString();
        var cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
        if (location.protocol === 'https:') {
            cookie += '; Secure';
        }
        document.cookie = cookie;
    }

    function getConsent() {
        if (canUseLocalStorage()) {
            return localStorage.getItem(consentKey) || '';
        }
        return getCookie(consentKey);
    }

    function saveConsent(value) {
        if (canUseLocalStorage()) {
            localStorage.setItem(consentKey, value);
        }
        setCookie(consentKey, value, 365);
    }

    if (!getConsent()) {
        banner.style.display = 'flex';
    }

    btn.addEventListener('click', function(){
        saveConsent('1');
        banner.style.display = 'none';
    });
})();
</script>
</body>
</html>
