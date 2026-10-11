<?php
declare(strict_types=1);

/**
 * Настройки собственной учётной записи; роль и разрешения не изменяются.
 * Все проверки выполняются повторно на сервере.
 */
function cms_profile_identity_values(string $name, string $email): array {
    $name = trim($name);
    $email = mb_strtolower(trim($email));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 ||
        mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        preg_match('/[\x00-\x1f\x7f]/u', $name)) {
        throw new RuntimeException('Укажите имя от 2 до 120 символов и действительный адрес почты.');
    }
    return [$name, $email];
}

function cms_profile_new_password(string $new, string $confirmation): string {
    if ($new !== $confirmation) {
        throw new RuntimeException('Новый пароль и подтверждение не совпадают.');
    }
    // PASSWORD_DEFAULT в текущей версии PHP использует bcrypt (72 байта).
    if (strlen($new) < 12 || strlen($new) > 72 || str_contains($new, "\0")) {
        throw new RuntimeException('Новый пароль должен содержать от 12 до 72 байт.');
    }
    return $new;
}

function cms_profile_password_hash(PDO $db, int $userId, string $currentPassword): string {
    if ($userId < 1 || $currentPassword === '' || strlen($currentPassword) > 1024) {
        throw new RuntimeException('Текущий пароль введён неверно.');
    }
    $query = $db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
    $query->execute([$userId]);
    $hash = $query->fetchColumn();
    if (!is_string($hash) || !password_verify($currentPassword, $hash)) {
        throw new RuntimeException('Текущий пароль введён неверно.');
    }
    return $hash;
}

function cms_profile_update_identity(PDO $db, int $userId, string $name, string $email, string $currentPassword): void {
    [$name, $email] = cms_profile_identity_values($name, $email);
    cms_profile_password_hash($db, $userId, $currentPassword);
    $db->prepare('UPDATE users SET name=?,email=? WHERE id=? AND active=1')
       ->execute([$name, $email, $userId]);
}

function cms_profile_update_password(PDO $db, int $userId, string $currentPassword, string $new, string $confirmation): void {
    $new = cms_profile_new_password($new, $confirmation);
    $oldHash = cms_profile_password_hash($db, $userId, $currentPassword);
    if (password_verify($new, $oldHash)) {
        throw new RuntimeException('Новый пароль должен отличаться от текущего.');
    }
    $db->prepare('UPDATE users SET password_hash=? WHERE id=? AND active=1')
       ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
}
