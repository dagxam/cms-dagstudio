<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1') exit(1);
function check_profile(bool $condition,string $name):void{
    if(!$condition){fwrite(STDERR,"[FAIL] $name\n");exit(1);}
    echo "[OK] $name\n";
}
function must_reject(callable $operation,string $name):void{
    try{$operation();}catch(RuntimeException $e){check_profile(true,$name);return;}
    check_profile(false,$name);
}
require dirname(__DIR__).'/app/profile.php';
[$name,$email]=cms_profile_identity_values('  Тестовый редактор  ','  PROFILE@example.test  ');
check_profile($name==='Тестовый редактор'&&$email==='profile@example.test','Имя и почта приводятся к нормальному виду');
must_reject(fn()=>cms_profile_identity_values('','invalid'),'Некорректные данные отклонены');
must_reject(fn()=>cms_profile_identity_values("Имя\nподмена",'name@example.test'),'Управляющие символы в имени запрещены');
must_reject(fn()=>cms_profile_new_password('123456789012','different'),'Несовпадающие пароли запрещены');
must_reject(fn()=>cms_profile_new_password('short','short'),'Слишком короткие пароли запрещены');
must_reject(fn()=>cms_profile_new_password(str_repeat('a',73),str_repeat('a',73)),'Пароли длиннее 72 байт запрещены');

$host=getenv('TEST_DB_HOST')?:'127.0.0.1';
$dbname=getenv('TEST_DB_NAME')?:'dagcms_test';
$user=getenv('TEST_DB_USER')?:'root';
$pass=getenv('TEST_DB_PASS')?:'';
$db=new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
]);
$db->beginTransaction();
try {
    $old='OldStrongPassword2026!';
    $passwordHash=password_hash($old,PASSWORD_DEFAULT);
    $db->prepare('INSERT INTO users(email,name,password_hash,role,permissions) VALUES(?,?,?,?,?)')
       ->execute(['profile-initial@example.test','До изменения',$passwordHash,'editor','["news"]']);
    $id=(int)$db->lastInsertId();
    must_reject(fn()=>cms_profile_update_identity($db,$id,'Новое имя','new@example.test','wrong'),
        'Без текущего пароля изменить личные данные нельзя');
    cms_profile_update_identity($db,$id,' Новое имя ','NewPROFILE@example.test',$old);
    $q=$db->prepare('SELECT name,email,role,permissions,password_hash FROM users WHERE id=?');
    $q->execute([$id]);$saved=$q->fetch();
    check_profile($saved['name']==='Новое имя'&&$saved['email']==='newprofile@example.test',
        'Имя и email сохраняются в MySQL');
    check_profile($saved['role']==='editor'&&$saved['permissions']==='["news"]',
        'Смена профиля не меняет роль и права');
    must_reject(fn()=>cms_profile_update_password($db,$id,'wrong','NewStrongPassword2026!','NewStrongPassword2026!'),
        'Без текущего пароля установить новый нельзя');
    must_reject(fn()=>cms_profile_update_password($db,$id,$old,$old,$old),
        'Повторное использование старого пароля отклонено');
    $new='NewStrongPassword2026!';
    cms_profile_update_password($db,$id,$old,$new,$new);
    $q->execute([$id]);$saved=$q->fetch();
    check_profile(password_verify($new,$saved['password_hash'])&&!password_verify($old,$saved['password_hash']),
        'Новый пароль заменяет старый в базе');
    check_profile($saved['role']==='editor'&&$saved['permissions']==='["news"]',
        'Смена пароля не меняет разрешения');
} finally {
    if($db->inTransaction())$db->rollBack();
}
$admin=file_get_contents(dirname(__DIR__).'/admin/index.php');
$script=file_get_contents(dirname(__DIR__).'/assets/admin-profile.js');
check_profile(str_contains($admin,'<details class="cms-profile-menu"')&&str_contains($admin,'<summary class="user-pill cms-profile-trigger"'),
    'Кнопка пользователя раскрывает меню');
check_profile(str_contains($admin,'Настройки профиля')&&str_contains($admin,'cms-profile-dropdown-form'),
    'В меню есть настройки профиля и форма выхода');
check_profile(str_contains($admin,"\$section !== 'profile'")&&str_contains($admin,"'dashboard','profile','edit'"),
    'Редакторы могут открывать собственный профиль');
check_profile(str_contains($admin,'name="action" value="save_profile"')&&str_contains($admin,'name="action" value="change_profile_password"'),
    'На странице есть две работающие формы');
check_profile(str_contains($script,"event.key === 'Escape'")&&str_contains($script,'!menu.contains(event.target)'),
    'Меню закрывается по Escape и нажатию снаружи');
echo "Функции личного профиля прошли проверки.\n";
