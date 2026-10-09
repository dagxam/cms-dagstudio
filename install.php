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
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="install-page">
<div class="install-shell">
  <header class="install-header">
    <div class="brand install-brand"><span class="brand-icon" aria-hidden="true">D</span><span>DAG STUDIO <b>CMS</b></span></div>
    <span class="install-header-tag"><span class="install-header-dot" aria-hidden="true"></span> Первичная настройка системы</span>
  </header>

  <main class="install-main">
    <aside class="install-aside" aria-labelledby="install-welcome">
      <div>
        <p class="install-kicker">НАСТРОЙКА САЙТА / 01</p>
        <h1 id="install-welcome">Ваш сайт<br><span>начинается здесь.</span></h1>
        <p class="install-lead">Универсальная платформа для администраций, компаний, организаций и интернет-магазинов.</p>
      </div>
      <div class="install-roadmap" aria-label="Этапы первоначальной настройки">
        <div class="install-roadmap-item"><span class="install-roadmap-number">01</span><div><strong>Основные сведения</strong><span>Название и назначение сайта</span></div></div>
        <div class="install-roadmap-item"><span class="install-roadmap-number">02</span><div><strong>Подключение MySQL</strong><span>Безопасное хранение данных</span></div></div>
        <div class="install-roadmap-item"><span class="install-roadmap-number">03</span><div><strong>Доступ администратора</strong><span>Ваша первая учётная запись</span></div></div>
      </div>
      <p class="install-aside-note">DAG STUDIO <span>— цифровые решения с характером.</span></p>
    </aside>

    <section class="install-panel" aria-labelledby="install-form-title">
      <div class="install-panel-header">
        <div>
          <p class="install-panel-overline">МАСТЕР УСТАНОВКИ</p>
          <h2 id="install-form-title">Настройте вашу CMS</h2>
          <p class="install-panel-subtitle">Заполните параметры сайта и создайте учётную запись администратора.</p>
        </div>
        <span class="install-version">DAG CMS / SETUP</span>
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
                  <input type="password" name="db_password" autocomplete="off" placeholder="Пароль пользователя MySQL">
                </label>
              </div>
            </fieldset>
          </div>

          <div class="install-form-column">
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
                  <input required minlength="12" type="password" name="admin_password" autocomplete="new-password" placeholder="Не менее 12 символов" aria-describedby="install-password-hint">
                  <small id="install-password-hint">Пароль администратора отличается от пароля MySQL. Сохраните его для входа.</small>
                </label>
              </div>
              <div class="install-security-tip">
                <span class="install-security-symbol" aria-hidden="true">✓</span>
                <div><strong>Защищённая установка</strong><p>Пароль администратора сохраняется в виде хеша. После установки повторный запуск мастера блокируется.</p></div>
              </div>
            </fieldset>
          </div>
        </div>
        <div class="install-form-footer">
          <p>Проверьте реквизиты MySQL и данные администратора перед запуском.</p>
          <button class="button install-submit" type="submit">Создать сайт <span aria-hidden="true">↗</span></button>
        </div>
      </form>
    </section>
  </main>

  <footer class="install-footer"><span>© DAG STUDIO CMS</span><span>Первичная настройка веб-сайта</span></footer>
</div>
</body>
</html>
