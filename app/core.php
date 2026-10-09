<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
session_name('dagcms');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true, 'samesite' => 'Lax'
]);
session_start();

function h(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function go(string $url): never {
    header('Location: ' . $url, true, 303);
    exit;
}
function token(): string {
    return $_SESSION['token'] ??= bin2hex(random_bytes(32));
}
function csrf(): string {
    return '<input type="hidden" name="token" value="' . h(token()) . '">';
}
function verify_token(): void {
    if (!hash_equals(token(), (string)($_POST['token'] ?? ''))) {
        http_response_code(419);
        exit('Срок действия формы истёк. Обновите страницу.');
    }
}
function installed(): bool {
    return is_file(dirname(__DIR__) . '/storage/config.php');
}
function database(): PDO {
    static $pdo;
    if (isset($pdo)) return $pdo;
    if (!installed()) go('/install.php');
    $c = require dirname(__DIR__) . '/storage/config.php';
    $pdo = new PDO('mysql:host=' . $c['host'] . ';port=' . (int)$c['port'] .
        ';dbname=' . $c['name'] . ';charset=utf8mb4', $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    return $pdo;
}
function config_value(string $key, string $default = ''): string {
    $query = database()->prepare('SELECT value FROM settings WHERE name=?');
    $query->execute([$key]);
    $result = $query->fetchColumn();
    return $result === false ? $default : (string)$result;
}
function account(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $q = database()->prepare('SELECT id,name,email,role,permissions FROM users WHERE id=? AND active=1');
    $q->execute([(int)$_SESSION['user_id']]);
    $row = $q->fetch();
    return $row ?: null;
}
function require_account(): array {
    $user = account();
    if (!$user) go('/admin/login.php');
    return $user;
}
function allowed(string $module): bool {
    $user = account();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;
    if (in_array($module, ['settings','users','messages'], true)) return false;
    $sections = json_decode((string)$user['permissions'], true);
    return is_array($sections) && in_array($module, $sections, true);
}
function require_module(string $module): void {
    require_account();
    if (!allowed($module)) {
        http_response_code(403);
        exit('Нет прав для этого раздела.');
    }
}
function slugify(string $value): string {
    $value = mb_strtolower($value);
    $value = strtr($value, [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e',
        'ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m',
        'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
        'ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'',
        'ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'
    ]);
    return trim((string)preg_replace('/[^a-z0-9]+/', '-', $value), '-');
}
function kinds(): array {
    return ['page'=>'Страницы','news'=>'Новости','service'=>'Услуги','product'=>'Товары'];
}
function log_action(string $action, string $value): void {
    database()->prepare('INSERT INTO audit_log(user_id,action,target) VALUES (?,?,?)')
        ->execute([account()['id'] ?? null, $action, mb_substr($value, 0, 190)]);
}
