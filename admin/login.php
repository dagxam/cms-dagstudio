<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (!installed()) go('/install.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    verify_token();
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
    go('/admin/login.php');
}
if (account()) go('/admin/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $fingerprint = hash('sha256', $email . '|' . $ip);
    $pdo = database();
    $q = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE fingerprint=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
    $q->execute([$fingerprint]);
    if ((int)$q->fetchColumn() >= 7) {
        http_response_code(429);
        $error = 'Слишком много попыток. Попробуйте через 15 минут.';
    } else {
        $q = $pdo->prepare('SELECT id,password_hash FROM users WHERE email=? AND active=1 LIMIT 1');
        $q->execute([$email]);
        $record = $q->fetch();
        if ($record && password_verify($password, $record['password_hash'])) {
            $pdo->prepare('DELETE FROM login_attempts WHERE fingerprint=?')->execute([$fingerprint]);
            if (password_needs_rehash($record['password_hash'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($password,PASSWORD_DEFAULT),$record['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$record['id'];
            go('/admin/index.php');
        }
        $pdo->prepare('INSERT INTO login_attempts(fingerprint) VALUES (?)')->execute([$fingerprint]);
        $error = 'Неверная почта или пароль.';
    }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Вход — DAG STUDIO CMS</title>
<link id="dag-favicon" rel="icon" type="image/svg+xml" href="/assets/ornament-dark.svg"><script>try{document.documentElement.dataset.theme=localStorage.getItem("dagstudio-cms-theme")==="light"?"light":"dark"}catch(e){document.documentElement.dataset.theme="dark"}</script><script src="/assets/theme.js" defer></script><link rel="stylesheet" href="/assets/style.css?v=ornament2"></head>
<body class="auth-page"><main class="auth-card box"><div class="auth-theme-bar"><button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить тему" title="Переключить тему"><span class="theme-toggle-dark" aria-hidden="true"><i class="fa-solid fa-moon"></i></span><span class="theme-toggle-light" aria-hidden="true"><i class="fa-solid fa-sun"></i></span></button></div>
<div class="brand"><span class="brand-symbol" aria-hidden="true"><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""></span> <span>DAG STUDIO <b>CMS</b></span></div>
<h1>Панель управления</h1><p class="muted">Войдите для управления вашим сайтом.</p>
<?php if (isset($_GET['installed'])): ?><div class="notice">CMS установлена. Войдите под учётной записью администратора.</div><?php endif; ?>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<form method="post"><?=csrf()?>
<label>Электронная почта<input required type="email" name="email" autocomplete="username"></label>
<label>Пароль<span class="password-field"><input id="login-password" required type="password" name="password" autocomplete="current-password"><button type="button" class="password-eye" data-password-toggle aria-controls="login-password" aria-label="Показать пароль" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></span></label>
<button class="button" type="submit">Войти в CMS</button></form>
<a class="back" href="/"><i class="fa-solid fa-arrow-left cms-icon-inline" aria-hidden="true"></i> Перейти на сайт</a></main></body></html>
