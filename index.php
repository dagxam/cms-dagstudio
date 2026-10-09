<?php
declare(strict_types=1);
require __DIR__ . '/app/core.php';
if (!installed()) go('/install.php');
$siteName = config_value('site_name','DAG STUDIO CMS');
$siteDescription = config_value('site_description','Официальный сайт');
$siteType = config_value('site_type','company');
$types = array_filter(kinds(), static fn (string $key): bool => module_enabled($key), ARRAY_FILTER_USE_KEY);
$slug = trim((string)($_GET['p'] ?? ''));
$kind = (string)($_GET['kind'] ?? '');
if (!array_key_exists($kind, $types)) $kind = '';
$message = '';
$error = '';
$privacyUrl = config_value('privacy_url');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'contact') {
    verify_token();
    if ($privacyUrl === '' || empty($_POST['consent'])) {
        http_response_code(400);
        $error = 'Отправка обращения требует опубликованной политики и согласия.';
    } else {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    if (!empty($_POST['website'])) {
        http_response_code(400);
        exit('Недопустимая отправка.');
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 ||
        !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 ||
        mb_strlen($body) < 10 || mb_strlen($body) > 5000) {
        $error = 'Проверьте имя, адрес электронной почты и текст обращения (от 10 до 5000 символов).';
    } elseif (time() - (int)($_SESSION['last_contact'] ?? 0) < 60) {
        $error = 'Вы уже отправили сообщение. Повторите через минуту.';
    } else {
        database()->prepare('INSERT INTO messages(name,email,body) VALUES (?,?,?)')
            ->execute([$name,$email,$body]);
        $_SESSION['last_contact'] = time();
        $message = 'Спасибо! Ваше обращение сохранено и доступно администратору сайта.';
    }
    }
}

$record = null;
if ($slug !== '') {
    $q = database()->prepare('SELECT * FROM content WHERE slug=? AND status=? LIMIT 1');
    $q->execute([$slug,'published']);
    $record = $q->fetch();
    if ($record && !module_enabled($record['kind'])) $record = false;
    if (!$record) http_response_code(404);
}
if (!$record) {
    if ($slug !== '') $entries = [];
    elseif ($kind !== '') {
        $q = database()->prepare('SELECT * FROM content WHERE kind=? AND status=? ORDER BY created_at DESC LIMIT 60');
        $q->execute([$kind,'published']);
        $entries = $q->fetchAll();
    } else {
        $entries = database()->query("SELECT * FROM content WHERE status='published' ORDER BY created_at DESC LIMIT 100")->fetchAll();
        $entries = array_slice(array_values(array_filter($entries, static fn (array $item): bool => module_enabled($item['kind']))), 0, 24);
    }
}
$metaTitle = $record ? $record['title'] . ' — ' . $siteName : $siteName;
$metaDescription = $record ? ($record['summary'] ?: $siteDescription) : $siteDescription;
define('DAG_CMS_SITE_VIEW', true);
require __DIR__ . '/app/site-render.php';
