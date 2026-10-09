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
?><!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?=h(mb_substr($metaDescription,0,250))?>">
<title><?=h($metaTitle)?></title><link rel="stylesheet" href="/assets/style.css"></head>
<body class="site-page">
<header class="site-header"><div class="container header-inner">
<a class="brand" href="/"><span class="brand-icon">D</span> <span><?=h($siteName)?></span></a>
<nav class="site-nav"><a href="/">Главная</a>
<?php foreach ($types as $key=>$label): ?>
<a href="/?kind=<?=h($key)?>" class="<?=$kind===$key?'current':''?>"><?=h($label)?></a>
<?php endforeach;?></nav>
<a class="small-link" href="/admin/login.php">Вход</a></div></header>
<main class="container">
<?php if ($record): ?>
<section class="article-page"><a class="back" href="/?kind=<?=h($record['kind'])?>">← Назад к разделу</a>
<div class="eyebrow"><?=h($types[$record['kind']] ?? 'Материал')?></div>
<h1><?=h($record['title'])?></h1>
<?php if ($record['summary']): ?><p class="lead"><?=h($record['summary'])?></p><?php endif;?>
<?php if ($record['kind']==='product' && $record['price']!==null): ?><p class="price"><?=h(number_format((float)$record['price'],2,',',' '))?> ₽</p><?php endif;?>
<div class="article-body"><?=nl2br(h($record['body']))?></div>
</section>
<?php elseif ($slug !== ''): ?>
<section class="hero"><div class="eyebrow">404 / СТРАНИЦА НЕ НАЙДЕНА</div><h1>Такой страницы нет</h1>
<p class="lead">Возможно, материал ещё не опубликован.</p><a class="button" href="/">На главную</a></section>
<?php else: ?>
<section class="hero"><div class="eyebrow">DAG STUDIO / <?=h(strtoupper($siteType))?></div>
<h1><?=h($kind ? $types[$kind] : $siteName)?></h1>
<p class="lead"><?=h($siteDescription)?></p>
<div class="hero-actions"><a href="#materials" class="button">Смотреть материалы ↗</a><a href="#contact" class="button button-outline">Связаться с нами</a></div>
</section>
<section id="materials" class="section-block"><div class="section-heading"><div><div class="eyebrow">ПУБЛИКАЦИИ</div>
<h2><?=$kind?h($types[$kind]):'Последние материалы'?></h2></div><span class="muted"><?=count($entries)?> записей</span></div>
<div class="cards">
<?php foreach ($entries as $item): ?><a class="content-card box" href="/?p=<?=rawurlencode($item['slug'])?>">
<div class="card-symbol"><?=['page'=>'▤','news'=>'▣','service'=>'◇','product'=>'▦'][$item['kind']]?></div>
<span class="eyebrow"><?=h($types[$item['kind']])?></span>
<h3><?=h($item['title'])?></h3>
<p class="muted"><?=h(mb_strimwidth($item['summary'] ?: $item['body'],0,180,'…','UTF-8'))?></p>
<?php if($item['kind']==='product' && $item['price']!==null):?><span class="price"><?=h(number_format((float)$item['price'],2,',',' '))?> ₽</span><?php endif;?>
<span class="card-link">Подробнее <span>↗</span></span></a><?php endforeach;?>
</div><?php if (!$entries): ?><div class="box empty-state">Публикаций пока нет. Они появятся после добавления в панели управления.</div><?php endif;?></section>
<section class="section-block contact-section" id="contact"><div><div class="eyebrow">ОБРАТНАЯ СВЯЗЬ</div>
<h2>Свяжитесь с нами</h2><p class="muted">Заполните форму, и ваше сообщение поступит в административную панель.</p>
<p><?=h(config_value('contact_email'))?></p></div>
<?php if ($privacyUrl): ?><div class="box contact-form"><?php if($message):?><div class="notice"><?=h($message)?></div><?php endif;?>
<?php if($error):?><div class="error"><?=h($error)?></div><?php endif;?>
<form method="post" action="/?kind=<?=h($kind)?>#contact"><?=csrf()?>
<input type="hidden" name="action" value="contact">
<div class="honeypot" aria-hidden="true"><label>Сайт<input tabindex="-1" name="website" autocomplete="off"></label></div>
<label>Ваше имя<input required maxlength="120" name="name" value="<?=h($_POST['name'] ?? '')?>"></label>
<label>Электронная почта<input required type="email" name="email" value="<?=h($_POST['email'] ?? '')?>"></label>
<label>Сообщение<textarea required minlength="10" maxlength="5000" rows="5" name="body"><?=h($_POST['body'] ?? '')?></textarea></label>
<label class="check privacy-check"><input type="checkbox" name="consent" value="1" required> Даю согласие на обработку указанных данных согласно <a href="<?=h($privacyUrl)?>" target="_blank" rel="noopener noreferrer">политике обработки персональных данных</a>.</label>
<button class="button" type="submit">Отправить сообщение</button></form>
</div><?php else: ?><p class="muted">Онлайн-форма будет доступна после публикации политики обработки персональных данных.</p><?php endif; ?></section>
<?php endif;?></main>
<footer class="site-footer"><div class="container footer-inner"><span><?=h($siteName)?> · <?=date('Y')?></span>
<span>Работает на <strong>DAG STUDIO CMS</strong></span></div></footer>
</body></html>
