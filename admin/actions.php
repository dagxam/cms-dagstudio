<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
header('Cache-Control: no-store');
require_account();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Метод не поддерживается');
}
verify_token();
$action = (string)($_POST['action'] ?? '');
$back = '/admin/index.php';

try {
    if ($action === 'save_content') {
        $kind = (string)($_POST['kind'] ?? '');
        if (!array_key_exists($kind, kinds())) throw new RuntimeException('Неизвестный раздел');
        require_module($kind);
        if (!module_enabled($kind)) throw new RuntimeException('Модуль отключён в настройках');
        $id = max(0, (int)($_POST['id'] ?? 0));
        if ($id) {
            $q = database()->prepare('SELECT kind FROM content WHERE id=?');
            $q->execute([$id]);
            $oldKind = $q->fetchColumn();
            if (!$oldKind) throw new RuntimeException('Запись не найдена');
            require_module((string)$oldKind);
        }
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = slugify(trim((string)($_POST['slug'] ?? '')) ?: $title);
        $summary = trim((string)($_POST['summary'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $status = (string)($_POST['status'] ?? 'draft');
        if (mb_strlen($title) < 2 || mb_strlen($title) > 250 || strlen($slug) > 190 ||
            $slug === '' || mb_strlen($summary) > 3000 || mb_strlen($body) > 100000 ||
            !in_array($status,['draft','published'],true)) {
            throw new RuntimeException('Проверьте поля записи');
        }
        $price = null;
        if ($kind === 'product') {
            $value = (string)($_POST['price'] ?? '');
            if ($value !== '' && (!is_numeric($value) || (float)$value < 0 || (float)$value > 9999999999)) {
                throw new RuntimeException('Неверная цена');
            }
            if ($value !== '') $price = number_format((float)$value, 2, '.', '');
        }
        $fields = [$kind,$title,$slug,$summary,$body,$price,$status];
        if ($id) {
            $fields[] = $id;
            $q = database()->prepare('UPDATE content SET kind=?,title=?,slug=?,summary=?,body=?,price=?,status=? WHERE id=?');
            $q->execute($fields);
        } else {
            $fields[] = (int)account()['id'];
            $q = database()->prepare('INSERT INTO content(kind,title,slug,summary,body,price,status,created_by) VALUES (?,?,?,?,?,?,?,?)');
            $q->execute($fields);
            $id = (int)database()->lastInsertId();
        }
        log_action('content.save', $kind . ':' . $id);
        $_SESSION['flash'] = 'Материал сохранён.';
        $back = '/admin/index.php?section=' . urlencode($kind);
    } elseif ($action === 'delete_content') {
        $id = max(0,(int)($_POST['id'] ?? 0));
        $q = database()->prepare('SELECT kind FROM content WHERE id=?');
        $q->execute([$id]);
        $kind = $q->fetchColumn();
        if (!$kind) throw new RuntimeException('Материал не найден');
        require_module((string)$kind);
        if (!module_enabled((string)$kind)) throw new RuntimeException('Модуль отключён');
        database()->prepare('DELETE FROM content WHERE id=?')->execute([$id]);
        log_action('content.delete', $kind . ':' . $id);
        $_SESSION['flash'] = 'Материал удалён.';
        $back = '/admin/index.php?section=' . urlencode((string)$kind);
    } elseif ($action === 'save_settings') {
        require_module('settings');
        $modules = $_POST['modules'] ?? [];
        if (!is_array($modules)) throw new RuntimeException('Неверный список модулей');
        $modules = array_values(array_intersect(array_keys(kinds()), array_map('strval',$modules)));
        if (!$modules) throw new RuntimeException('Включите хотя бы один модуль');
        $values = [
            'enabled_modules'=>json_encode($modules),
            'site_name'=>trim((string)($_POST['site_name'] ?? '')),
            'site_description'=>trim((string)($_POST['site_description'] ?? '')),
            'contact_email'=>trim((string)($_POST['contact_email'] ?? '')),
            'privacy_url'=>trim((string)($_POST['privacy_url'] ?? '')),
            'site_type'=>(string)($_POST['site_type'] ?? ''),
        ];
        if ($values['site_name'] === '' || mb_strlen($values['site_name']) > 150 ||
            mb_strlen($values['site_description']) > 300 ||
            !filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL) ||
            mb_strlen($values['privacy_url']) > 500 ||
            ($values['privacy_url'] !== '' && !str_starts_with($values['privacy_url'], '/?p=') &&
             !str_starts_with($values['privacy_url'], 'https://')) ||
            !in_array($values['site_type'], ['government','company','organization','store'],true)) {
            throw new RuntimeException('Проверьте настройки сайта');
        }
        $q = database()->prepare('INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        foreach ($values as $key=>$value) $q->execute([$key,$value]);
        log_action('settings.save', 'site');
        $_SESSION['flash'] = 'Настройки обновлены.';
        $back = '/admin/index.php?section=settings';
    } elseif ($action === 'create_user') {
        require_module('users');
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $name = trim((string)($_POST['name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'editor');
        $permissions = $_POST['permissions'] ?? [];
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($name) < 2 ||
            mb_strlen($name) > 120 || strlen($password) < 12 ||
            !in_array($role,['admin','editor'],true) || !is_array($permissions)) {
            throw new RuntimeException('Проверьте поля пользователя');
        }
        $permissions = array_values(array_intersect(array_keys(kinds()),array_map('strval',$permissions)));
        $q = database()->prepare('INSERT INTO users(email,name,password_hash,role,permissions) VALUES (?,?,?,?,?)');
        $q->execute([$email,$name,password_hash($password,PASSWORD_DEFAULT),$role,json_encode($permissions)]);
        log_action('users.create', $email);
        $_SESSION['flash'] = 'Пользователь добавлен.';
        $back = '/admin/index.php?section=users';
    } elseif ($action === 'read_message') {
        require_module('messages');
        $id = max(0,(int)($_POST['id'] ?? 0));
        database()->prepare('UPDATE messages SET is_read=1 WHERE id=?')->execute([$id]);
        $_SESSION['flash'] = 'Обращение отмечено как прочитанное.';
        $back = '/admin/index.php?section=messages';
    } else {
        throw new RuntimeException('Неизвестная операция');
    }
} catch (PDOException $exception) {
    error_log('[DAG CMS] ' . $exception->getMessage());
    $_SESSION['flash_error'] = 'Ошибка сохранения. Проверьте, не заняты ли почта или адрес страницы.';
} catch (RuntimeException $exception) {
    $_SESSION['flash_error'] = $exception->getMessage();
}
go($back);
