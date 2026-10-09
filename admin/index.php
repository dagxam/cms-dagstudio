<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$me = require_account();
$section = (string)($_GET['section'] ?? 'dashboard');
$types = kinds();
if (!in_array($section, array_merge(['dashboard','edit','templates','settings','users','messages'],array_keys($types)),true)) $section='dashboard';
if ($section === 'edit') {
    $id = max(0,(int)($_GET['id'] ?? 0));
    $record = null;
    if ($id) {
        $query = database()->prepare('SELECT * FROM content WHERE id=?');
        $query->execute([$id]);
        $record = $query->fetch();
        if (!$record) { http_response_code(404); exit('Запись не найдена'); }
        $kind = $record['kind'];
    } else $kind = (string)($_GET['kind'] ?? 'page');
    if (!array_key_exists($kind,$types)) { http_response_code(404); exit('Раздел не найден'); }
    require_module($kind);
    if (!module_enabled($kind)) { http_response_code(404); exit('Модуль отключён'); }
} elseif ($section !== 'dashboard') {
    require_module($section);
    if (isset($types[$section]) && !module_enabled($section)) {
        http_response_code(404);
        exit('Модуль отключён');
    }
}
$flash = $_SESSION['flash'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'],$_SESSION['flash_error']);
?><!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Панель управления — DAG STUDIO CMS</title>
<link id="dag-favicon" rel="icon" type="image/svg+xml" href="/assets/ornament-dark.svg"><script>try{document.documentElement.dataset.theme=localStorage.getItem("dagstudio-cms-theme")==="light"?"light":"dark"}catch(e){document.documentElement.dataset.theme="dark"}</script><script src="/assets/theme.js" defer></script><link rel="stylesheet" href="/assets/style.css?v=templates3"><link rel="stylesheet" href="/assets/templates.css?v=palette4"><script src="/assets/template-palettes.js?v=palette4" defer></script></head>
<body class="admin-layout">
<aside class="sidebar">
<a class="brand" href="/admin/index.php"><span class="brand-symbol" aria-hidden="true"><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""></span> <span>DAG STUDIO <b>CMS</b></span></a>
<span class="nav-label">УПРАВЛЕНИЕ</span>
<a class="nav-item <?=$section==='dashboard'?'active':''?>" href="/admin/index.php">◫ Обзор</a>
<?php foreach($types as $key=>$label): if(!allowed($key) || !module_enabled($key)) continue; ?>
<a class="nav-item <?=($section===$key||($section==='edit'&&$kind===$key))?'active':''?>" href="/admin/index.php?section=<?=h($key)?>"><?=['page'=>'▤','news'=>'▣','service'=>'◇','product'=>'▦'][$key]?> <?=h($label)?></a>
<?php endforeach; ?>
<?php if (allowed('messages')): ?><a class="nav-item <?=$section==='messages'?'active':''?>" href="?section=messages">✉ Обращения</a><?php endif; ?>
<?php if (allowed('users')): ?><a class="nav-item <?=$section==='users'?'active':''?>" href="?section=users">♙ Пользователи</a><?php endif; ?>
<?php if (allowed('settings')): ?><a class="nav-item <?=$section==='templates'?'active':''?>" href="?section=templates">◈ Выбор темы сайта</a><a class="nav-item <?=$section==='settings'?'active':''?>" href="?section=settings">⚙ Настройки</a><?php endif; ?>
<div class="sidebar-bottom"><p class="muted">Вы вошли как<br><strong><?=h($me['name'])?></strong></p>
<a class="nav-item" href="/" target="_blank" rel="noopener">↗ Открыть сайт</a>
<form method="post" action="/admin/login.php"><?=csrf()?><input type="hidden" name="logout" value="1"><button class="logout" type="submit">Выйти из аккаунта</button></form></div>
</aside>
<div class="workspace"><header class="topbar"><span class="topbar-brand">Панель управления <span class="muted">/ <?=h($section==='dashboard'?'Обзор':($types[$section]??ucfirst($section)))?></span></span><div class="admin-header-controls"><button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить тему" title="Переключить тему"><span class="theme-toggle-dark" aria-hidden="true">☾</span><span class="theme-toggle-light" aria-hidden="true">☼</span></button><span class="user-pill"><?=h($me['name'])?> · <?=h($me['role'])?></span></div></header>
<main class="main">
<?php if ($flash): ?><div class="notice"><?=h($flash)?></div><?php endif; ?>
<?php if ($flashError): ?><div class="error"><?=h($flashError)?></div><?php endif; ?>

<?php if ($section==='dashboard'):
$counts=[];
foreach ($types as $key=>$label) {
    if (!allowed($key) || !module_enabled($key)) continue;
    $q=database()->prepare('SELECT COUNT(*) FROM content WHERE kind=?');
    $q->execute([$key]);
    $counts[$key]=(int)$q->fetchColumn();
}
?>
<div class="eyebrow">DAG STUDIO / DASHBOARD</div><h1>Добро пожаловать, <?=h($me['name'])?></h1>
<p class="muted">Управляйте содержимым сайта из единой административной панели.</p>
<div class="stat-grid">
<?php foreach($counts as $key=>$count): ?><a class="stat box" href="?section=<?=h($key)?>">
<span class="muted"><?=h($types[$key])?></span><strong><?=number_format($count,0,',',' ')?></strong><span class="stat-link">Перейти в раздел ↗</span></a><?php endforeach; ?>
</div>
<div class="box welcome"><h2>Ваш сайт под контролем</h2>
<p class="muted">Начните с создания страницы или новости. В разделе «Шаблоны и дизайн» выберите оформление и настройте главную страницу.</p>
<?php foreach($types as $key=>$label): if(!allowed($key) || !module_enabled($key))continue;?>
<a class="button button-outline" href="?section=edit&kind=<?=h($key)?>">+ <?=h($label)?></a>
<?php endforeach; ?></div>

<?php elseif ($section==='edit'):
$row = $record ?: ['title'=>'','slug'=>'','summary'=>'','body'=>'','price'=>'','status'=>'draft'];
?>
<div class="eyebrow">РЕДАКТОР МАТЕРИАЛОВ</div>
<a class="back" href="?section=<?=h($kind)?>">← Вернуться к списку</a>
<h1><?=$record?'Редактирование':'Новый материал'?></h1>
<div class="box form-panel"><form method="post" action="/admin/actions.php">
<?=csrf()?><input type="hidden" name="action" value="save_content">
<input type="hidden" name="id" value="<?=h((string)($record['id']??0))?>">
<input type="hidden" name="kind" value="<?=h($kind)?>">
<label>Название<input required maxlength="250" name="title" value="<?=h($row['title'])?>"></label>
<label>Адрес страницы (латиницей)<input maxlength="190" name="slug" value="<?=h($row['slug'])?>" placeholder="Создаётся из заголовка автоматически"></label>
<label>Краткое описание<textarea name="summary" rows="3" maxlength="3000"><?=h($row['summary'])?></textarea></label>
<label>Текст материала<textarea name="body" rows="13" maxlength="100000"><?=h($row['body'])?></textarea></label>
<?php if ($kind==='product'): ?><label>Цена (₽)<input name="price" type="number" step="0.01" min="0" value="<?=h((string)$row['price'])?>"></label><?php endif;?>
<div class="two"><label>Статус публикации<select name="status">
<option value="draft" <?=$row['status']==='draft'?'selected':''?>>Черновик</option>
<option value="published" <?=$row['status']==='published'?'selected':''?>>Опубликовано</option>
</select></label><div class="form-submit"><button class="button" type="submit">Сохранить материал</button></div></div>
</form></div>

<?php elseif (isset($types[$section])):
$q = database()->prepare('SELECT id,title,slug,status,updated_at FROM content WHERE kind=? ORDER BY updated_at DESC LIMIT 100');
$q->execute([$section]);$rows=$q->fetchAll();
?>
<div class="heading-row"><div><div class="eyebrow">УПРАВЛЕНИЕ КОНТЕНТОМ</div><h1><?=h($types[$section])?></h1>
<p class="muted">Редактируйте и публикуйте материалы вашего сайта.</p></div>
<a class="button" href="?section=edit&kind=<?=h($section)?>">+ Добавить</a></div>
<div class="box table-wrap"><table><thead><tr><th>Материал</th><th>Статус</th><th>Обновлён</th><th>Действия</th></tr></thead>
<tbody><?php foreach($rows as $item): ?><tr>
<td><strong><?=h($item['title'])?></strong><small class="muted"><?=h($item['slug'])?></small></td>
<td><span class="tag <?=$item['status']==='published'?'tag-green':''?>"><?=h($item['status']==='published'?'Опубликовано':'Черновик')?></span></td>
<td><?=h($item['updated_at'])?></td>
<td><div class="actions"><a href="?section=edit&id=<?=(int)$item['id']?>">Изменить</a>
<form method="post" action="/admin/actions.php" onsubmit="return confirm('Удалить запись без возможности восстановления?')">
<?=csrf()?><input type="hidden" name="action" value="delete_content"><input type="hidden" name="id" value="<?=(int)$item['id']?>">
<button class="link-danger" type="submit">Удалить</button></form></div></td></tr>
<?php endforeach; ?></tbody></table>
<?php if(!$rows): ?><p class="empty">Материалов пока нет. Создайте первую запись.</p><?php endif; ?></div>

<?php elseif ($section==='templates'): ?>
<?php define('DAG_CMS_ADMIN_VIEW',true); require __DIR__ . '/template-editor.php'; ?>

<?php elseif ($section==='settings'): ?>
<div class="eyebrow">ПАРАМЕТРЫ САЙТА</div><h1>Основные настройки</h1>
<div class="box form-panel"><form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="save_settings">
<label>Название сайта<input name="site_name" required maxlength="150" value="<?=h(config_value('site_name'))?>"></label>
<label>Описание сайта<textarea name="site_description" maxlength="300" rows="3"><?=h(config_value('site_description'))?></textarea></label>
<label>Контактный e-mail<input name="contact_email" type="email" required value="<?=h(config_value('contact_email'))?>"></label>
<label>URL политики обработки персональных данных<input name="privacy_url" maxlength="500" placeholder="/?p=privacy" value="<?=h(config_value('privacy_url'))?>"></label><p class="muted">Пока этот адрес не задан, форма обращений отключена.</p>
<div class="settings-template-note"><strong>Текущий тип: <?=h(template_catalog()[site_template()]['label'])?></strong><p class="muted">Тип и оформление сайта меняются в разделе <a href="?section=templates">«Шаблоны и дизайн»</a>.</p><input type="hidden" name="site_type" value="<?=h(config_value('site_type'))?>"></div>
<fieldset><legend>Активные модули сайта</legend>
<?php foreach($types as $type=>$label): ?>
<label class="check"><input type="checkbox" name="modules[]" value="<?=h($type)?>" <?=module_enabled($type)?'checked':''?>> <?=h($label)?></label>
<?php endforeach; ?></fieldset>
<button class="button" type="submit">Сохранить настройки</button></form></div>

<?php elseif ($section==='users'):
$rows = database()->query('SELECT id,name,email,role,permissions,active,created_at FROM users ORDER BY id')->fetchAll();
?>
<div class="eyebrow">КОМАНДА</div><h1>Пользователи</h1>
<div class="box table-wrap"><table><thead><tr><th>Имя</th><th>Почта</th><th>Роль</th><th>Разделы</th></tr></thead><tbody>
<?php foreach($rows as $item): ?><tr><td><?=h($item['name'])?></td><td><?=h($item['email'])?></td>
<td><?=h($item['role'])?></td><td><?=h(implode(', ',json_decode($item['permissions']??'[]',true)?:[]))?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="box form-panel"><h2>Добавить сотрудника</h2><form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="create_user">
<label>Имя<input required name="name"></label><label>E-mail<input required name="email" type="email"></label>
<label>Временный пароль (от 12 символов)<input required minlength="12" name="password" type="password" autocomplete="new-password"></label>
<label>Роль<select name="role"><option value="editor">Редактор (с выбранными правами)</option><option value="admin">Администратор (все права)</option></select></label>
<fieldset><legend>Разрешённые разделы для редактора</legend>
<?php foreach($types as $type=>$label): ?><label class="check"><input type="checkbox" name="permissions[]" value="<?=h($type)?>"> <?=h($label)?></label><?php endforeach; ?></fieldset>
<button class="button" type="submit">Добавить пользователя</button></form></div>

<?php elseif ($section==='messages'):
$rows=database()->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 100')->fetchAll();
?>
<div class="eyebrow">ОБРАТНАЯ СВЯЗЬ</div><h1>Обращения посетителей</h1>
<?php foreach($rows as $message): ?><article class="box message">
<div class="heading-row"><strong><?=h($message['name'])?> <span class="muted">· <?=h($message['email'])?></span></strong>
<span class="tag <?=$message['is_read']?'':'tag-green'?>"><?=$message['is_read']?'Прочитано':'Новое'?></span></div>
<p><?=nl2br(h($message['body']))?></p><small class="muted"><?=h($message['created_at'])?></small>
<?php if(!$message['is_read']): ?><form method="post" action="/admin/actions.php"><?=csrf()?>
<input type="hidden" name="action" value="read_message"><input type="hidden" name="id" value="<?=(int)$message['id']?>">
<button class="button button-outline" type="submit">Отметить прочитанным</button></form><?php endif;?></article><?php endforeach;?>
<?php if(!$rows): ?><p class="muted">Обращений пока нет.</p><?php endif;?>
<?php endif;?>
</main></div></body></html>
