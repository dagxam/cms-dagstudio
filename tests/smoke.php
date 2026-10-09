<?php
declare(strict_types=1);

// Запускать только в изолированной тестовой базе (CI), никогда на боевой БД.
if (getenv('DAG_CMS_TEST_MODE') !== '1') {
    fwrite(STDERR, "Тестовый режим не включён.\n");
    exit(1);
}
$host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
$name = getenv('TEST_DB_NAME') ?: 'dagcms_test';
$user = getenv('TEST_DB_USER') ?: 'root';
$pass = getenv('TEST_DB_PASS') ?: '';
$db = new PDO("mysql:host=$host;port=3306;dbname=$name;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
require dirname(__DIR__) . '/app/core.php';

function check(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] $message\n");
        exit(1);
    }
    echo "[OK] $message\n";
}
check(slugify('Привет, мир!') === 'privet-mir', 'Транслитерация');
check(h('<script>') === '&lt;script&gt;', 'HTML-экранирование');
check(strlen(token()) === 64, 'Токен CSRF');
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
check($schema !== false, 'SQL-схема доступна');
foreach (explode(';', $schema) as $statement) {
    if (trim($statement) !== '') $db->exec($statement);
}
check((int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0, 'Пустая БД');
$passHash = password_hash('ExamplePassword_2026!', PASSWORD_DEFAULT);
$db->prepare("INSERT INTO users(email,name,password_hash,role,permissions) VALUES (?,?,?,?,?)")
    ->execute(['test@example.test','Тестовый редактор',$passHash,'editor','["news"]']);
check(password_verify('ExamplePassword_2026!', $passHash), 'Проверка пароля');
check(!password_verify('wrong', $passHash), 'Неверный пароль');
$db->prepare('INSERT INTO settings(name,value) VALUES (?,?)')->execute(['site_name','Тестовый сайт']);
$q = $db->prepare('SELECT value FROM settings WHERE name=?');
$q->execute(['site_name']);
check($q->fetchColumn() === 'Тестовый сайт', 'Настройки сайта');
$db->prepare('INSERT INTO content(kind,title,slug,status,created_by) VALUES (?,?,?,?,?)')
    ->execute(['news','Проверочная новость','test-news','published',1]);
$q=$db->prepare('SELECT title FROM content WHERE slug=? AND status=?');
$q->execute(['test-news','published']);
check($q->fetchColumn() === 'Проверочная новость', 'Публикация и получение материала');
$db->prepare('INSERT INTO messages(name,email,body) VALUES (?,?,?)')
    ->execute(['Тест','test@example.test','Проверка обратной связи']);
check((int)$db->query('SELECT COUNT(*) FROM messages')->fetchColumn() === 1, 'Сообщения');
check((int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='cms_media'")->fetchColumn() === 1,'Таблица медиатеки создана');
$db->prepare('INSERT INTO cms_media(category,title,description,alt_text,age_rating,status,filename,original_name,mime,size_bytes) VALUES(?,?,?,?,?,?,?,?,?,?)')
  ->execute(['document','Тестовый документ','Описание PDF','','6+','published',str_repeat('a',32).'.pdf','test.pdf','application/pdf',12345]);
check((int)$db->query('SELECT COUNT(*) FROM cms_media WHERE status="published"')->fetchColumn()===1,'Публикация файла в базе');
echo "DAG STUDIO CMS: smoke-тест успешно завершён.\n";
