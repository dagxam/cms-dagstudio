<?php
declare(strict_types=1);

// Интеграционная проверка CLI-сброса в отдельной базе GitHub Actions.
if (getenv('DAG_CMS_TEST_MODE') !== '1') {
    fwrite(STDERR, "Запуск разрешён только в тестовом режиме.\n");
    exit(1);
}
$host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
$name = getenv('TEST_DB_NAME') ?: 'dagcms_test';
$user = getenv('TEST_DB_USER') ?: 'root';
$pass = getenv('TEST_DB_PASS') ?: '';
$dsn = "mysql:host={$host};port=3306;dbname={$name};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$email = 'recovery-admin@example.test';
$previous = 'PreviousPassword_2026!';
$new = 'UpdatedPassword_2026!';
$pdo->prepare("INSERT INTO users(email,name,password_hash,role) VALUES(?,?,?,'admin')")
    ->execute([$email, 'Тестовый администратор', password_hash($previous, PASSWORD_DEFAULT)]);
$config = dirname(__DIR__) . '/storage/config.php';
if (file_exists($config)) {
    throw new RuntimeException('Нельзя заменять уже существующий файл конфигурации.');
}
file_put_contents($config, '<?php return ' . var_export([
    'host' => $host, 'port' => 3306, 'name' => $name, 'user' => $user, 'pass' => $pass,
], true) . ';');
try {
    $command = [PHP_BINARY, dirname(__DIR__) . '/tools/reset-admin.php', $email];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Не удалось запустить тестовый CLI-процесс.');
    }
    fwrite($pipes[0], $new . "\n");
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0) {
        throw new RuntimeException('CLI-сброс завершился ошибкой: ' . $error);
    }
    $statement = $pdo->prepare('SELECT password_hash,role,active FROM users WHERE email=?');
    $statement->execute([$email]);
    $record = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$record || !password_verify($new, $record['password_hash']) ||
        password_verify($previous, $record['password_hash']) ||
        $record['role'] !== 'admin' || (int)$record['active'] !== 1) {
        throw new RuntimeException('Пароль не обновился или изменился статус администратора.');
    }
    echo "[OK] Восстановление пароля администратора работает; роль и статус сохранены.\n";
} finally {
    unlink($config);
}
