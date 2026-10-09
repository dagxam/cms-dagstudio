<?php
declare(strict_types=1);
require __DIR__ . '/app/core.php';
header('X-Robots-Tag: noindex, nofollow');
if (installed()) {
    http_response_code(403);
    exit('CMS уже установлена. Повторная установка закрыта.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_token();
    $host = trim((string)($_POST['host'] ?? 'localhost'));
    $port = (int)($_POST['port'] ?? 3306);
    $dbName = trim((string)($_POST['database'] ?? ''));
    $dbUser = trim((string)($_POST['username'] ?? ''));
    $dbPass = (string)($_POST['db_password'] ?? '');
    $adminName = trim((string)($_POST['admin_name'] ?? ''));
    $adminEmail = mb_strtolower(trim((string)($_POST['admin_email'] ?? '')));
    $adminPass = (string)($_POST['admin_password'] ?? '');
    $siteName = trim((string)($_POST['site_name'] ?? ''));
    $siteType = (string)($_POST['site_type'] ?? 'company');
    $createdConfig = false;
    try {
        if (!preg_match('/^[a-zA-Z0-9.:-]{1,255}$/', $host) || $port < 1 || $port > 65535 ||
            !preg_match('/^[a-zA-Z0-9_]{1,64}$/', $dbName) || $dbUser === '') {
            throw new RuntimeException('Проверьте адрес и учётные данные базы MySQL.');
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($adminName) < 2 ||
            mb_strlen($adminName) > 120 || strlen($adminPass) < 12) {
            throw new RuntimeException('Укажите имя, действительный e-mail и пароль от 12 символов.');
        }
        if ($siteName === '' || mb_strlen($siteName) > 150 ||
            !in_array($siteType, ['government','company','organization','store'], true)) {
            throw new RuntimeException('Заполните название и тип сайта.');
        }
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4",
            $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema === false) throw new RuntimeException('Файл структуры базы не найден.');
        foreach (explode(';', $schema) as $statement) {
            if (trim($statement) !== '') $pdo->exec($statement);
        }
        if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) {
            throw new RuntimeException('В этой базе CMS уже есть пользователь. Нужна чистая БД.');
        }
        $pdo->beginTransaction();
        $query = $pdo->prepare('INSERT INTO users(email,name,password_hash,role) VALUES (?,?,?,?)');
        $query->execute([$adminEmail,$adminName,password_hash($adminPass,PASSWORD_DEFAULT),'admin']);
        $query = $pdo->prepare('INSERT INTO settings(name,value) VALUES (?,?)');
        foreach ([
            'site_name'=>$siteName, 'site_type'=>$siteType,
            'site_description'=>'Официальный сайт', 'contact_email'=>$adminEmail
        ] as $name=>$value) $query->execute([$name,$value]);
        $values = ['host'=>$host,'port'=>$port,'name'=>$dbName,'user'=>$dbUser,'pass'=>$dbPass];
        $contents = "<?php\nreturn " . var_export($values, true) . ";\n";
        $filename = __DIR__ . '/storage/config.php';
        $file = @fopen($filename, 'x');
        if (!$file) throw new RuntimeException('Папка storage недоступна для записи.');
        $createdConfig = true;
        $bytes = fwrite($file, $contents);
        fclose($file);
        if ($bytes !== strlen($contents)) throw new RuntimeException('Не удалось записать настройки.');
        @chmod($filename, 0600);
        $pdo->commit();
        go('/admin/login.php?installed=1');
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        if ($createdConfig) @unlink(__DIR__ . '/storage/config.php');
        error_log('[DAG CMS install] ' . $exception->getMessage());
        $error = $exception instanceof RuntimeException
            ? $exception->getMessage() : 'Ошибка подключения или создания базы. Проверьте журнал PHP.';
    }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Установка DAG STUDIO CMS</title><link rel="stylesheet" href="/assets/style.css"></head>
<body class="auth-page"><main class="auth-card box">
<div class="brand"><span class="brand-icon">D</span> DAG STUDIO <b>CMS</b></div>
<h1>Установка CMS</h1><p class="muted">Укажите данные заранее созданной базы MySQL и первого администратора.</p>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<form method="post"><?=csrf()?>
<h3>Сайт</h3>
<label>Название<input required maxlength="150" name="site_name" value="<?=h($_POST['site_name'] ?? 'Мой сайт')?>"></label>
<label>Тип сайта<select name="site_type">
<option value="government">Администрация</option>
<option value="company">Компания</option>
<option value="organization">Организация</option>
<option value="store">Магазин</option></select></label>
<h3>Подключение к MySQL</h3>
<div class="two"><label>Хост<input required name="host" value="<?=h($_POST['host'] ?? 'localhost')?>"></label>
<label>Порт<input required type="number" name="port" value="<?=h($_POST['port'] ?? '3306')?>"></label></div>
<label>Имя базы<input required name="database" value="<?=h($_POST['database'] ?? '')?>"></label>
<label>Пользователь<input required name="username" value="<?=h($_POST['username'] ?? '')?>"></label>
<label>Пароль базы<input type="password" name="db_password" autocomplete="off"></label>
<h3>Администратор</h3>
<label>Имя<input required name="admin_name" value="<?=h($_POST['admin_name'] ?? '')?>"></label>
<label>E-mail<input required type="email" name="admin_email" value="<?=h($_POST['admin_email'] ?? '')?>"></label>
<label>Пароль (минимум 12 символов)<input required minlength="12" type="password" name="admin_password" autocomplete="new-password"></label>
<button class="button" type="submit">Создать сайт</button>
</form></main></body></html>
