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
        $defaultModules = $siteType === 'store' ? ['page','news','product'] : ['page','news','service'];
        foreach ([
            'site_name'=>$siteName, 'site_type'=>$siteType,
            'enabled_modules'=>json_encode($defaultModules),
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
?><!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#111214">
  <title>Установка — DAG STUDIO CMS</title>
  <link id="dag-favicon" rel="icon" type="image/svg+xml" href="/assets/ornament-dark.svg">
  <script>try{document.documentElement.dataset.theme=localStorage.getItem('dagstudio-cms-theme')==='light'?'light':'dark'}catch(e){document.documentElement.dataset.theme='dark'}</script>
  <script src="/assets/theme.js" defer></script>
  <link rel="stylesheet" href="/assets/style.css?v=ornament2">
</head>
<body class="install-page">
<div class="install-shell">
  <header class="install-header">
    <a class="brand install-brand" href="/"><span class="brand-symbol" aria-hidden="true"><img class="logo-on-dark" src="/assets/ornament-dark.svg" alt=""><img class="logo-on-light" src="/assets/ornament-light.svg" alt=""></span><span>DAG STUDIO <b>CMS</b></span></a>
    <div class="install-header-actions">
      <span class="install-header-tag"><span class="install-header-dot" aria-hidden="true"></span> СОВРЕМЕННАЯ CMS · ДАГЕСТАНСКИЙ ХАРАКТЕР</span>
      <button type="button" class="theme-toggle" data-theme-toggle aria-label="Переключить тему" title="Переключить тему"><span aria-hidden="true" class="theme-toggle-dark"><i class="fa-solid fa-moon"></i></span><span aria-hidden="true" class="theme-toggle-light"><i class="fa-solid fa-sun"></i></span></button>
    </div>
  </header>

  <main class="install-main">
    <aside class="install-aside" aria-labelledby="install-welcome">
      <div class="install-hero-art" aria-hidden="true"></div>
      <div class="install-hero-content">
        <div class="install-hero-logo" aria-hidden="true">
          <img class="logo-on-dark" src="/assets/ornament-dark.svg" alt="">
          <img class="logo-on-light" src="/assets/ornament-light.svg" alt="">
        </div>
        <h1 id="install-welcome">DAG STUDIO <span>CMS</span></h1>
        <div class="install-ornament-line" aria-hidden="true"><i></i><b><i class="fa-solid fa-diamond"></i></b><i></i></div>
        <p class="install-hero-kicker">СОВРЕМЕННАЯ СИСТЕМА УПРАВЛЕНИЯ САЙТАМИ</p>
        <p class="install-lead">Простая установка. Мощные возможности.<br>Создавайте современные сайты, управляйте контентом и развивайте проекты вместе с DAG STUDIO CMS.</p>
      </div>
      <div class="install-feature-grid" aria-label="Преимущества CMS">
        <div class="install-feature"><span aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span><strong>Гибкое<br>управление</strong></div>
        <div class="install-feature"><span aria-hidden="true"><i class="fa-solid fa-code"></i></span><strong>Современная<br>архитектура</strong></div>
        <div class="install-feature"><span aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span><strong>Надёжная<br>защита</strong></div>
        <div class="install-feature"><span aria-hidden="true"><i class="fa-solid fa-gears"></i></span><strong>Расширяемые<br>возможности</strong></div>
      </div>
      <p class="install-aside-note"><span>ИДЕИ</span> <i>·</i> <span>КОНТЕНТ</span> <i>·</i> <span>ЛЮДИ</span> <i>·</i> <span>ВОЗМОЖНОСТИ</span></p>
    </aside>

    <section class="install-panel" aria-labelledby="install-form-title">
      <div class="install-progress" aria-label="Этапы установки">
        <span class="install-progress-item is-current"><b>1</b><span>Основные сведения</span></span>
        <span class="install-progress-item"><b>2</b><span>База данных</span></span>
        <span class="install-progress-item"><b>3</b><span>Администратор</span></span>
        <span class="install-progress-item"><b>4</b><span>Завершение</span></span>
      </div>
      <div class="install-panel-header">
        <p class="install-panel-overline">ПЕРВЫЙ ЗАПУСК · DAG STUDIO CMS</p>
        <h2 id="install-form-title">Установка <em>DAG STUDIO CMS</em></h2>
        <p class="install-panel-subtitle">Заполните параметры для первоначальной настройки. Это займёт всего несколько минут.</p>
      </div>

      <?php if ($error): ?>
      <div class="error install-error" role="alert"><?=h($error)?></div>
      <?php endif; ?>

      <form method="post" class="install-form">
        <?=csrf()?>
        <div class="install-form-columns">
          <div class="install-form-column">
            <fieldset class="install-group">
              <legend><span class="install-step">01</span> Основные сведения</legend>
              <div class="install-input-grid">
                <label>Название сайта
                  <input required maxlength="150" name="site_name" value="<?=h($_POST['site_name'] ?? 'Мой сайт')?>" placeholder="Название организации">
                </label>
                <label>Тип сайта
                  <select required name="site_type">
                    <option value="government" <?=($_POST['site_type'] ?? 'company') === 'government' ? 'selected' : ''?>>Администрация</option>
                    <option value="company" <?=($_POST['site_type'] ?? 'company') === 'company' ? 'selected' : ''?>>Компания</option>
                    <option value="organization" <?=($_POST['site_type'] ?? 'company') === 'organization' ? 'selected' : ''?>>Организация</option>
                    <option value="store" <?=($_POST['site_type'] ?? 'company') === 'store' ? 'selected' : ''?>>Магазин</option>
                  </select>
                </label>
              </div>
            </fieldset>
            <fieldset class="install-group install-admin-group">
              <legend><span class="install-step">03</span> Администратор сайта</legend>
              <p class="install-group-lead">Эти данные используются только для входа в панель управления CMS.</p>
              <div class="install-input-grid">
                <label>Ваше имя
                  <input required maxlength="120" name="admin_name" value="<?=h($_POST['admin_name'] ?? '')?>" placeholder="Имя администратора" autocomplete="name">
                </label>
                <label>Электронная почта
                  <input required type="email" name="admin_email" value="<?=h($_POST['admin_email'] ?? '')?>" placeholder="admin@example.ru" autocomplete="email" spellcheck="false">
                </label>
                <label class="install-field-wide">Пароль администратора
                  <span class="password-field"><input id="install-admin-pass" required minlength="12" type="password" name="admin_password" autocomplete="new-password" placeholder="Не менее 12 символов" aria-describedby="install-password-hint"><button type="button" class="password-eye" data-password-toggle aria-controls="install-admin-pass" aria-label="Показать пароль" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></span>
                  <small id="install-password-hint">Пароль администратора отличается от пароля MySQL. Сохраните его для входа.</small>
                </label>
              </div>
              <div class="install-security-tip">
                <span class="install-security-symbol" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                <div><strong>Защищённая установка</strong><p>Пароль администратора сохраняется в виде хеша. После установки повторный запуск мастера блокируется.</p></div>
              </div>
            </fieldset>
          </div>
          <div class="install-form-column">
            <fieldset class="install-group">
              <legend><span class="install-step">02</span> База данных MySQL</legend>
              <div class="install-input-grid">
                <label>Хост MySQL
                  <input required maxlength="255" name="host" value="<?=h($_POST['host'] ?? 'localhost')?>" placeholder="localhost" autocomplete="off" spellcheck="false">
                </label>
                <label>Порт
                  <input required type="number" min="1" max="65535" name="port" value="<?=h((string)($_POST['port'] ?? '3306'))?>" inputmode="numeric">
                </label>
                <label>Имя базы данных
                  <input required name="database" value="<?=h($_POST['database'] ?? '')?>" placeholder="database_name" autocomplete="off" spellcheck="false">
                </label>
                <label>Пользователь MySQL
                  <input required name="username" value="<?=h($_POST['username'] ?? '')?>" placeholder="db_user" autocomplete="off" spellcheck="false">
                </label>
                <label class="install-field-wide">Пароль базы данных
                  <span class="password-field"><input id="install-db-pass" type="password" name="db_password" autocomplete="off" placeholder="Пароль пользователя MySQL"><button type="button" class="password-eye" data-password-toggle aria-controls="install-db-pass" aria-label="Показать пароль" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button></span>
                </label>
              </div>
            </fieldset>
            <div class="install-safety-card">
              <div class="install-safety-symbol" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
              <div><strong>Всё для вашего сайта</strong><p>После установки вы получите панель управления, публикацию материалов, роли пользователей и настройку разделов.</p></div>
            </div>
          </div>
        </div>
        <div class="install-form-footer">
          <p>Данные подключения будут храниться только на сервере. Сохраните пароль администратора для последующего входа.</p>
          <button class="button install-submit" type="submit"><span aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles"></i></span> Создать сайт <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></button>
        </div>
      </form>
    </section>
  </main>

  <footer class="install-footer"><span>© DAG STUDIO CMS · Создано с уважением к традициям</span><span>Технологии развиваются. Ценности остаются.</span></footer>
</div>
</body>
</html>
