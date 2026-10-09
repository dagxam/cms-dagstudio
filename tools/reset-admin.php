<?php
declare(strict_types=1);

// Восстановление доступа к существующему администратору только через PHP CLI.
// Нельзя запускать по HTTP. Пароль передаётся по STDIN, а не аргументом или URL.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

if (count($argv) !== 2 || !filter_var($argv[1], FILTER_VALIDATE_EMAIL)) {
    fail('Использование: php tools/reset-admin.php admin@example.ru (новый пароль передайте через STDIN).');
}

$email = mb_strtolower(trim($argv[1]));
if (function_exists('stream_isatty') && stream_isatty(STDIN)) {
    fail('Не вводите пароль в отображаемом терминале. Передайте его через STDIN с помощью read -rs.');
}

$line = fgets(STDIN, 8194);
if ($line === false) {
    fail('Пароль не передан через STDIN.');
}
$password = rtrim($line, "\r\n");
if (strlen($password) < 12 || strlen($password) > 72) {
    fail('Новый пароль должен иметь длину от 12 до 72 байт (ограничение bcrypt).');
}
unset($line);

$configFile = dirname(__DIR__) . '/storage/config.php';
if (!is_file($configFile)) {
    fail('CMS не установлена: нет файла storage/config.php.');
}
$c = require $configFile;
if (!is_array($c) || !isset($c['host'], $c['port'], $c['name'], $c['user'], $c['pass'])) {
    fail('Файл конфигурации базы данных некорректен.');
}

try {
    $pdo = new PDO(
        'mysql:host=' . $c['host'] . ';port=' . (int)$c['port'] .
        ';dbname=' . $c['name'] . ';charset=utf8mb4',
        (string)$c['user'],
        (string)$c['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
    $q = $pdo->prepare('SELECT id, role, active FROM users WHERE email = ? LIMIT 1');
    $q->execute([$email]);
    $user = $q->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['role'] !== 'admin' || (int)$user['active'] !== 1) {
        fail('Активный администратор с указанным e-mail не найден. Проверьте таблицу users в phpMyAdmin.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    unset($password);
    $q = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=? AND role='admin' AND active=1");
    $q->execute([$hash, (int)$user['id']]);
    if ($q->rowCount() !== 1) {
        fail('Пароль не изменён. Проверьте права пользователя базы данных.');
    }

    echo 'Пароль администратора успешно обновлён. Войдите с этим e-mail и новым паролем.' . PHP_EOL;
    echo 'Если ранее было слишком много попыток входа, подождите 15 минут.' . PHP_EOL;
} catch (Throwable $exception) {
    error_log('[DAG CMS] Ошибка восстановления пароля администратора: ' . get_class($exception));
    fail('Не удалось изменить пароль. Проверьте соединение и права на базу данных.');
}
