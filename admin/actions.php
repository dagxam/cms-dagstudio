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
    if ($action === 'save_template_menu') {
        require_module('settings');
        $template=(string)($_POST['template']??'');
        if(!isset(template_catalog()[$template]))throw new RuntimeException('Неизвестный шаблон.');
        if(!is_string($_POST['menu_lines']??null))throw new RuntimeException('Некорректный список ссылок.');
        $links=cms_menu_parse($_POST['menu_lines']);
        $maps=json_decode(config_value('cms_menus_by_type','{}'),true);
        if(!is_array($maps))$maps=[];
        $maps[$template]=$links;
        database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')
            ->execute(['cms_menus_by_type',json_encode($maps,JSON_UNESCAPED_UNICODE)]);
        log_action('menu.save',$template);
        $_SESSION['flash']='Главное меню шаблона сохранено.';
        $back='/admin/index.php?section=menus&tpl='.rawurlencode($template);
    } elseif ($action === 'save_video_link') {
        require_module('media');
        if(!cms_module_enabled('videos'))throw new RuntimeException('Видеогалерея отключена.');
        $title=trim((string)($_POST['title']??''));
        $description=trim((string)($_POST['description']??''));
        $url=trim((string)($_POST['video_url']??''));
        $age=(string)($_POST['age_rating']??'0+');
        $status=(string)($_POST['status']??'draft');
        $parsed=cms_video_embed($url);
        if($title===''||mb_strlen($title)>190||mb_strlen($description)>2000||
           strlen($url)>500||!$parsed||!cms_media_age_valid($age)||!in_array($status,['draft','published'],true))
            throw new RuntimeException('Введите корректные название, возраст и ссылку VK Видео или Rutube.');
        cms_video_table();
        database()->prepare('INSERT INTO cms_video_links(title,description,provider,embed_url,original_url,age_rating,status) VALUES(?,?,?,?,?,?,?)')
            ->execute([$title,$description,$parsed['provider'],$parsed['embed'],$url,$age,$status]);
        log_action('video.link.add',$title);
        $_SESSION['flash']='Видео по ссылке добавлено.';
        $back='/admin/index.php?section=videos';
    } elseif ($action === 'delete_video_link') {
        require_module('media');
        cms_video_table();
        $id=max(0,(int)($_POST['video_id']??0));
        database()->prepare('DELETE FROM cms_video_links WHERE id=?')->execute([$id]);
        log_action('video.link.delete',(string)$id);
        $_SESSION['flash']='Ссылка на видео удалена.';
        $back='/admin/index.php?section=videos';
    } elseif ($action === 'update_user') {
        require_module('users');
        $uid=max(0,(int)($_POST['user_id']??0));
        if($uid===(int)account()['id'])throw new RuntimeException('Изменить собственную роль или активность здесь нельзя.');
        $role=(string)($_POST['role']??'');
        $permissions=$_POST['permissions']??[];
        $password=(string)($_POST['new_password']??'');
        $active=isset($_POST['active'])?1:0;
        if(!in_array($role,['admin','editor'],true)||!is_array($permissions)||
           count($permissions)>count(kinds())+1||($password!==''&&strlen($password)<12))
            throw new RuntimeException('Недопустимые роль, права или пароль.');
        $q=database()->prepare('SELECT id FROM users WHERE id=?');$q->execute([$uid]);
        if(!$q->fetch())throw new RuntimeException('Сотрудник не найден.');
        $permissions=array_values(array_intersect([...array_keys(kinds()),'media'],array_map('strval',$permissions)));
        if($password===''){
            database()->prepare('UPDATE users SET role=?,permissions=?,active=? WHERE id=?')
                ->execute([$role,json_encode($permissions),$active,$uid]);
        }else{
            database()->prepare('UPDATE users SET role=?,permissions=?,active=?,password_hash=? WHERE id=?')
                ->execute([$role,json_encode($permissions),$active,password_hash($password,PASSWORD_DEFAULT),$uid]);
        }
        log_action('users.update',(string)$uid);
        $_SESSION['flash']='Роль и права сотрудника обновлены.';
        $back='/admin/index.php?section=users';
    } elseif ($action === 'save_cms_modules') {
        require_module('settings');
        $keys=$_POST['enabled']??[];
        if(!is_array($keys) || count($keys)>count(cms_modules())) {
            throw new RuntimeException('Некорректный перечень модулей.');
        }
        $chosen=array_values(array_intersect(array_keys(cms_modules()),array_map('strval',$keys)));
        // Должен остаться хотя бы один материал/функциональный раздел.
        if(!$chosen)throw new RuntimeException('Включите хотя бы один модуль.');
        $q=database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $q->execute(['cms_modules_enabled',json_encode($chosen,JSON_UNESCAPED_UNICODE)]);
        // Старый формат используется установщиком и прежними частями CMS.
        $legacy=array_values(array_intersect(array_keys(kinds()),$chosen));
        $q->execute(['enabled_modules',json_encode($legacy)]);
        log_action('modules.enabled',implode(',',$chosen));
        $_SESSION['flash']='Состояние модулей сохранено. Контент отключённых модулей не удалён.';
        $back='/admin/index.php?section=modules';
    } elseif ($action === 'save_cms_layout') {
        require_module('settings');
        $template=(string)($_POST['template']??'');
        if(!isset(template_catalog()[$template]))throw new RuntimeException('Неизвестный шаблон.');
        $posted=$_POST['position']??null;
        if(!is_array($posted) || array_diff(array_keys($posted),array_keys(cms_modules())) ||
            count($posted)!==count(cms_modules()))throw new RuntimeException('Некорректная таблица модулей.');
        $result=[];
        foreach(cms_modules() as $id=>$def) {
            $item=$posted[$id]??null;
            if(!is_array($item))throw new RuntimeException('Отсутствуют настройки модуля '.$id);
            $area=$item['area']??null;
            $order=$item['order']??null;
            if(!is_string($area) || !isset(cms_module_areas()[$area]) ||
               !is_string($order) || !ctype_digit($order) ||
               (int)$order<1 || (int)$order>99) {
               throw new RuntimeException('Недопустимое расположение или порядок для '.$id);
            }
            $result[$id]=['area'=>$area,'order'=>(int)$order];
        }
        $maps=json_decode(config_value('cms_module_layouts','{}'),true);
        if(!is_array($maps))$maps=[];
        $maps[$template]=$result;
        database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')
            ->execute(['cms_module_layouts',json_encode($maps,JSON_UNESCAPED_UNICODE)]);
        log_action('modules.layout',$template);
        $_SESSION['flash']='Расположение модулей для шаблона «'.template_catalog()[$template]['label'].'» обновлено.';
        $back='/admin/index.php?section=modules&tpl='.rawurlencode($template);
    } elseif ($action === 'save_compliance') {
        require_module('settings');
        $age=(string)($_POST['site_age_rating']??'');
        if(!cms_media_age_valid($age))throw new RuntimeException('Недопустимая возрастная категория.');
        $opts=[];
        $opts['enabled']=isset($_POST['vision_enabled'])?'1':'0';
        foreach([
            'font_scale'=>['125','150','175','200'],
            'contrast'=>['high','blackwhite','yellowblack'],
            'line_spacing'=>['normal','wide'],
            'letter_spacing'=>['normal','wide'],
        ] as $key=>$options){
            $v=(string)($_POST[$key]??'');
            if(!in_array($v,$options,true))throw new RuntimeException('Недопустимый параметр доступности: '.$key);
            $opts[$key]=$v;
        }
        foreach(['show_images','underlines','grayscale'] as $key)$opts[$key]=isset($_POST[$key])?'1':'0';
        $q=database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $q->execute(['site_age_rating',$age]);
        $q->execute(['accessibility_options',json_encode($opts,JSON_UNESCAPED_UNICODE)]);
        log_action('compliance.update',$age);
        $_SESSION['flash']='Возрастная маркировка и настройки доступности сохранены.';
        $back='/admin/index.php?section=accessibility';
    } elseif ($action === 'upload_media') {
        require_module('media');
        cms_media_table();
        $category=(string)($_POST['category']??'');
        if(!isset(['document'=>'documents','photo'=>'photos','video'=>'videos'][$category]) ||
           !cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$category]))
            throw new RuntimeException('Этот медиараздел отключён.');

        $title=trim((string)($_POST['title']??''));
        $description=trim((string)($_POST['description']??''));
        $alt=trim((string)($_POST['alt_text']??''));
        $age=(string)($_POST['age_rating']??'0+');
        if($title===''||mb_strlen($title)>190||mb_strlen($description)>2000||
            mb_strlen($alt)>300||!cms_media_age_valid($age))throw new RuntimeException('Проверьте описание и возрастную маркировку.');
        if($category==='photo'&&$alt==='')throw new RuntimeException('Для фотографии нужен альтернативный текст.');
        $file=$_FILES['media_file']??[];
        if(!is_array($file))throw new RuntimeException('Выберите файл.');
        [$mime,$ext,$size,$original]=cms_media_upload_validation($file,$category);
        $dir=dirname(__DIR__).'/storage/media';
        if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))
            throw new RuntimeException('Не удалось создать каталог медиатеки.');
        if(!is_writable($dir))throw new RuntimeException('Каталог медиатеки недоступен для записи.');
        $filename=bin2hex(random_bytes(16)).'.'.$ext;
        $path=$dir.'/'.$filename;
        if(!move_uploaded_file((string)$file['tmp_name'],$path))
            throw new RuntimeException('Не удалось сохранить файл.');
        @chmod($path,0600);
        try {
            database()->prepare('INSERT INTO cms_media(category,title,description,alt_text,age_rating,status,filename,original_name,mime,size_bytes) VALUES(?,?,?,?,?,?,?,?,?,?)')
                ->execute([$category,$title,$description,$alt,$age,isset($_POST['publish'])?'published':'draft',$filename,$original,$mime,$size]);
        } catch(Throwable $e){@unlink($path);throw $e;}
        log_action('media.upload',$title);
        $_SESSION['flash']='Файл загружен в медиатеку.';
        $back='/admin/index.php?section='.(['document'=>'documents','photo'=>'photos','video'=>'videos'][$category??($item['category']??'document')]??'documents');
    } elseif ($action === 'update_media') {
        require_module('media');
        $id=max(0,(int)($_POST['media_id']??0));
        $item=cms_media_get($id,false);
        if(!$item)throw new RuntimeException('Файл не найден.');
        $title=trim((string)($_POST['title']??''));
        $description=trim((string)($_POST['description']??''));
        $alt=trim((string)($_POST['alt_text']??''));
        $age=(string)($_POST['age_rating']??'');
        $status=(string)($_POST['status']??'');
        if($title===''||mb_strlen($title)>190||mb_strlen($description)>2000||mb_strlen($alt)>300||
            !cms_media_age_valid($age)||!in_array($status,['draft','published'],true)||
            ($item['category']==='photo'&&$alt===''))throw new RuntimeException('Проверьте данные медиа.');
        database()->prepare('UPDATE cms_media SET title=?,description=?,alt_text=?,age_rating=?,status=? WHERE id=?')
            ->execute([$title,$description,$alt,$age,$status,$id]);
        log_action('media.update',(string)$id);
        $_SESSION['flash']='Карточка медиа обновлена.';
        $back='/admin/index.php?section='.(['document'=>'documents','photo'=>'photos','video'=>'videos'][$category??($item['category']??'document')]??'documents');
    } elseif ($action === 'delete_media') {
        require_module('media');
        $id=max(0,(int)($_POST['media_id']??0));
        $item=cms_media_get($id,false);
        if(!$item)throw new RuntimeException('Файл не найден.');
        database()->prepare('DELETE FROM cms_media WHERE id=?')->execute([$id]);
        $base=(string)$item['filename'];
        if(preg_match('/^[a-f0-9]{32}\\.(jpg|png|webp|pdf|docx|xlsx|pptx|mp4|webm)$/D',$base))
            @unlink(dirname(__DIR__).'/storage/media/'.$base);
        log_action('media.delete',(string)$id);
        $_SESSION['flash']='Файл удалён.';
        $back='/admin/index.php?section='.(['document'=>'documents','photo'=>'photos','video'=>'videos'][$category??($item['category']??'document')]??'documents');
    } elseif ($action === 'save_government_layout') {
        require_module('settings');
        if (site_template()!=='government') {
            throw new RuntimeException('Настройки доступны только для шаблона «Администрация».');
        }
        $defaults=government_defaults();
        $updated=government_layout();
        $fields=[
            'name'=>120, 'name_detail'=>240, 'location'=>240,
            'working_hours'=>150, 'office_phone'=>80, 'top_note'=>180,
            'banner_title'=>180, 'banner_text'=>360,
            'search_placeholder'=>120, 'search_button'=>55,
            'quick_title'=>100, 'quick_url'=>300, 'left_title'=>120,
            'center_title'=>150, 'center_intro'=>5000,
            'leader_title'=>120, 'leader_name'=>160, 'leader_initials'=>100, 'leader_description'=>1000,
            'schedule_title'=>150, 'schedule_text'=>2000,
            'announcements_title'=>150, 'announcements_text'=>2000,
            'links_title'=>150, 'footer_note'=>240,
        ];
        foreach($fields as $key=>$limit) {
            if (!isset($_POST[$key]) || !is_string($_POST[$key]) ||
                mb_strlen($_POST[$key])>$limit || str_contains($_POST[$key], "\x00")) {
                throw new RuntimeException('Проверьте поле: '.$key);
            }
            $updated[$key]=trim($_POST[$key]);
        }
        if ($updated['quick_url']!=='' && !safe_template_url($updated['quick_url'])) {
            throw new RuntimeException('Некорректный адрес интернет-приёмной.');
        }
        $layout=(string)($_POST['layout']??'');
        if (!in_array($layout,['both','swap','left','right','none'],true)) {
            throw new RuntimeException('Неверное расположение колонок.');
        }
        $updated['layout']=$layout;
        foreach(['left_width','right_width'] as $key) {
            $width=(string)($_POST[$key]??'');
            if (!in_array($width,['200','220','240','260','280','300'],true)) {
                throw new RuntimeException('Неверная ширина колонки.');
            }
            $updated[$key]=$width;
        }
        foreach(['show_topbar','show_banner','show_search','show_date','show_accessibility',
            'show_leader','show_schedule','show_announcements','show_links'] as $key) {
            $updated[$key]=isset($_POST[$key]) && $_POST[$key]==='1'?'1':'0';
        }
        foreach(['left_menu'=>24,'right_links'=>12,'quick_links'=>12] as $key=>$max) {
            if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
                throw new RuntimeException('Ошибка редактирования списка: '.$key);
            }
            $updated[$key]=government_parse_link_text($_POST[$key],$max);
        }
        if (!empty($_POST['remove_leader_photo'])) $updated['leader_image']='';
        if (isset($_FILES['leader_photo']) && is_array($_FILES['leader_photo']) &&
            (int)($_FILES['leader_photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
            $file=$_FILES['leader_photo'];
            if ((int)$file['error']!==UPLOAD_ERR_OK ||
                !is_uploaded_file((string)($file['tmp_name']??'')) ||
                (int)$file['size']<1 || (int)$file['size']>2*1024*1024) {
                throw new RuntimeException('Фотография руководителя: максимальный размер 2 МБ.');
            }
            $fileInfo=new finfo(FILEINFO_MIME_TYPE);
            $type=$fileInfo->file((string)$file['tmp_name']);
            $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$type]??null;
            $dimensions=@getimagesize((string)$file['tmp_name']);
            if (!$ext || !$dimensions || ($dimensions[0]??0)<1 ||
                ($dimensions[1]??0)<1 || ($dimensions[0]??0)>2800 || ($dimensions[1]??0)>2800) {
                throw new RuntimeException('Нужно изображение JPEG, PNG или WebP до 2800 пикселей.');
            }
            $dir=dirname(__DIR__).'/assets/uploads';
            if (!is_dir($dir) || !is_writable($dir)) throw new RuntimeException('Каталог assets/uploads недоступен для записи.');
            $filename='leader-'.bin2hex(random_bytes(16)).'.'.$ext;
            if (!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$filename)) {
                throw new RuntimeException('Не удалось сохранить фотографию руководителя.');
            }
            @chmod($dir.'/'.$filename,0644);
            $updated['leader_image']='/assets/uploads/'.$filename;
        }
        database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')
            ->execute(['government_layout',json_encode($updated,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
        log_action('government.layout','saved');
        $_SESSION['flash']='Шапка, боковые колонки и содержимое администрации сохранены.';
        $back='/admin/index.php?section=templates&view=edit&tab=government#government-editor';
    } elseif ($action === 'select_template') {
        require_module('settings');
        $id = (string)($_POST['template'] ?? '');
        if (!array_key_exists($id, template_catalog())) throw new RuntimeException('Шаблон не найден');
        // Персональные настройки сохраняются отдельно для КАЖДОГО шаблона.
        $pdo = database();
        $previous = site_template();
        $query = $pdo->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $designMaps = json_decode(config_value('template_design_by_type','{}'),true);
        $contentMaps = json_decode(config_value('template_content_by_type','{}'),true);
        $sectionMaps = json_decode(config_value('template_sections_by_type','{}'),true);
        if (!is_array($designMaps)) $designMaps=[];
        if (!is_array($contentMaps)) $contentMaps=[];
        if (!is_array($sectionMaps)) $sectionMaps=[];
        if (!array_key_exists($previous,$designMaps)) $designMaps[$previous] = json_decode(config_value('template_design','{}'),true) ?: [];
        if (!array_key_exists($previous,$contentMaps)) $contentMaps[$previous] = json_decode(config_value('template_content','{}'),true) ?: [];
        if (!array_key_exists($previous,$sectionMaps)) {
            $prior = json_decode(config_value('template_sections',''),true);
            if (is_array($prior)) $sectionMaps[$previous] = $prior;
        }
        $pdo->beginTransaction();
        try {
            foreach ([
                'site_template'=>$id,
                'site_type'=>$id,
                'template_design'=>'{}',
                'template_content'=>'{}',
                'template_sections'=>'',
                'template_design_by_type'=>json_encode($designMaps,JSON_UNESCAPED_UNICODE),
                'template_content_by_type'=>json_encode($contentMaps,JSON_UNESCAPED_UNICODE),
                'template_sections_by_type'=>json_encode($sectionMaps,JSON_UNESCAPED_UNICODE),
            ] as $key=>$value) {
                $query->execute([$key,$value]);
            }
            $required = match($id) {
                'store'=>['page','product','news'],
                'government'=>['page','news','service'],
                'organization'=>['page','news','service'],
                default=>['page','service','news'],
            };
            $modules = json_decode(config_value('enabled_modules','[]'),true);
            if (!is_array($modules)) $modules = [];
            $query->execute(['enabled_modules', json_encode(array_values(array_unique(array_merge($modules,$required))),JSON_UNESCAPED_UNICODE)]);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
        log_action('template.change',$id);
        $_SESSION['flash'] = 'Шаблон «' . template_catalog()[$id]['label'] . '» применён. Материалы и персональные настройки сохранены.';
        $back='/admin/index.php?section=templates&view=edit&tab=appearance';
    } elseif ($action === 'save_template_design') {
        require_module('settings');
        $options = [
            'font'=>['montserrat','manrope','system','georgia'],
            'hero'=>['split','banner','official','centered'],
            'cards'=>['soft','outlined','elevated'],
            'header'=>['classic','catalog','official'],
            'radius'=>['0','6','12','16','18','24'],
            'width'=>['1120','1240','1320','1380','1480'],
        ];
        $design = [];
        $colors = $_POST['palette'] ?? null;
        if (!is_array($colors) || array_diff(array_keys($colors), ['light','dark'])) {
            throw new RuntimeException('Настройки цветовых тем некорректны.');
        }
        $design['palettes'] = [];
        foreach (['light','dark'] as $mode) {
            if (!isset($colors[$mode]) || !is_array($colors[$mode])) {
                throw new RuntimeException('Не найдены цвета для светлого или тёмного режима.');
            }
            foreach (['accent','background','ink','surface','border'] as $key) {
                $value = $colors[$mode][$key] ?? '';
                if (!is_string($value) || !preg_match('/^#[a-fA-F0-9]{6}$/D', $value)) {
                    throw new RuntimeException('Введите цвет в формате #RRGGBB для каждого поля.');
                }
                $design['palettes'][$mode][$key] = strtolower($value);
            }
        }
        // Обратная совместимость для старого однопалитрового редактора.
        $defaultMode = template_default_mode(site_template());
        foreach (['accent','background','ink'] as $key) {
            $design[$key] = $design['palettes'][$defaultMode][$key];
        }
        foreach ($options as $key=>$allowedValues) {
            $value = (string)($_POST[$key] ?? '');
            if (!in_array($value,$allowedValues,true)) throw new RuntimeException('Недопустимое значение параметра оформления.');
            $design[$key]=$value;
        }
        $maps=json_decode(config_value('template_design_by_type','{}'),true);
        if (!is_array($maps)) $maps=[];
        $maps[site_template()]=$design;
        $q=database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $q->execute(['template_design_by_type',json_encode($maps,JSON_UNESCAPED_UNICODE)]);
        $q->execute(['template_design',json_encode($design,JSON_UNESCAPED_UNICODE)]);
        log_action('template.design', site_template());
        $_SESSION['flash']='Оформление сохранено. Цвета, размеры и стиль обновлены на сайте.';
        $back='/admin/index.php?section=templates&view=edit&tab=appearance';
    } elseif ($action === 'save_template_content') {
        require_module('settings');
        $limits = [
            'eyebrow'=>140,'title'=>190,'description'=>700,
            'cta'=>70,'cta_url'=>300,'secondary'=>70,'secondary_url'=>300,
            'features_title'=>140,'news_title'=>140,'contact_title'=>140,
            'footer_text'=>220,'phone'=>45,'address'=>240,
            'feature_1_title'=>90,'feature_1_text'=>240,
            'feature_2_title'=>90,'feature_2_text'=>240,
            'feature_3_title'=>90,'feature_3_text'=>240,
        ];
        $values = [];
        foreach ($limits as $key=>$max) {
            $value = trim((string)($_POST[$key]??''));
            if (mb_strlen($value)>$max || str_contains($value, "\x00")) {
                throw new RuntimeException('Некорректное поле: '.$key);
            }
            $values[$key]=$value;
        }
        if ($values['title']==='') throw new RuntimeException('Заголовок первого экрана не может быть пустым.');
        foreach (['cta_url','secondary_url'] as $key) {
            if (!safe_template_url($values[$key])) throw new RuntimeException('Недопустимый адрес кнопки. Используйте /, # или https://.');
        }
        $labels=$_POST['nav_label']??[];
        $urls=$_POST['nav_url']??[];
        if (!is_array($labels) || !is_array($urls) || count($labels)>4 || count($urls)>4) {
            throw new RuntimeException('Превышен лимит пунктов меню.');
        }
        $nav=[];
        for ($i=0;$i<4;$i++) {
            $label=trim((string)($labels[$i]??''));
            $url=trim((string)($urls[$i]??''));
            if (mb_strlen($label)>50 || mb_strlen($url)>300 || !safe_template_url($url)) {
                throw new RuntimeException('Проверьте дополнительные ссылки меню.');
            }
            if ($label!=='' && $url!=='') $nav[]=['label'=>$label,'url'=>$url];
        }
        $values['header_links']=$nav;
        $values['logo_path']=template_content()['logo_path'];
        if (!empty($_POST['remove_logo'])) $values['logo_path']='';
        if (isset($_FILES['site_logo']) && is_array($_FILES['site_logo']) &&
            (int)($_FILES['site_logo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
            $file=$_FILES['site_logo'];
            if ((int)$file['error']!==UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name']) ||
                (int)$file['size']<1 || (int)$file['size']>2*1024*1024) {
                throw new RuntimeException('Не удалось загрузить логотип. Максимум 2 МБ.');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type=$finfo->file((string)$file['tmp_name']);
            $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$type]??null;
            $image=@getimagesize((string)$file['tmp_name']);
            if (!$ext || !$image || ($image[0]??0)<1 || ($image[1]??0)<1 ||
                ($image[0]??0)>2400 || ($image[1]??0)>2400) {
                throw new RuntimeException('Логотип должен быть настоящим изображением PNG, JPG или WebP до 2400 px.');
            }
            $dir=dirname(__DIR__).'/assets/uploads';
            if (!is_dir($dir) || !is_writable($dir)) throw new RuntimeException('Папка assets/uploads недоступна для записи.');
            $filename='logo-'.bin2hex(random_bytes(16)).'.'.$ext;
            if (!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$filename)) {
                throw new RuntimeException('Ошибка при сохранении логотипа.');
            }
            @chmod($dir.'/'.$filename,0644);
            $values['logo_path']='/assets/uploads/'.$filename;
        }
        $values['hero_image_path']=template_content()['hero_image_path'];
        if (!empty($_POST['remove_hero_image'])) $values['hero_image_path']='';
        if (isset($_FILES['hero_image']) && is_array($_FILES['hero_image']) &&
            (int)($_FILES['hero_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
            $file=$_FILES['hero_image'];
            if ((int)$file['error']!==UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name']) ||
                (int)$file['size']<1 || (int)$file['size']>5*1024*1024) {
                throw new RuntimeException('Не удалось загрузить изображение первого экрана (не более 5 МБ).');
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type=$finfo->file((string)$file['tmp_name']);
            $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$type]??null;
            $image=@getimagesize((string)$file['tmp_name']);
            if (!$ext || !$image || ($image[0]??0)<1 || ($image[1]??0)<1 ||
                ($image[0]??0)>4500 || ($image[1]??0)>4500) {
                throw new RuntimeException('Первый экран поддерживает PNG, JPG или WebP размером до 4500 px.');
            }
            $dir=dirname(__DIR__).'/assets/uploads';
            if (!is_dir($dir) || !is_writable($dir)) throw new RuntimeException('Папка assets/uploads недоступна для записи.');
            $filename='hero-'.bin2hex(random_bytes(16)).'.'.$ext;
            if (!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$filename)) {
                throw new RuntimeException('Не удалось сохранить изображение первого экрана.');
            }
            @chmod($dir.'/'.$filename,0644);
            $values['hero_image_path']='/assets/uploads/'.$filename;
        }
        $maps=json_decode(config_value('template_content_by_type','{}'),true);
        if (!is_array($maps)) $maps=[];
        $maps[site_template()]=$values;
        $q=database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $q->execute(['template_content_by_type',json_encode($maps,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
        $q->execute(['template_content',json_encode($values,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
        log_action('template.content',site_template());
        $_SESSION['flash']='Тексты, бренд и меню обновлены.';
        $back='/admin/index.php?section=templates&view=edit&tab=content';
    } elseif ($action === 'save_template_sections') {
        require_module('settings');
        $enabled=$_POST['active_sections']??[];
        $orders=$_POST['section_order']??[];
        if (!is_array($enabled) || !is_array($orders)) throw new RuntimeException('Неверный список разделов.');
        $allowed = array_keys(template_sections());
        $enabled=array_values(array_unique(array_intersect($allowed,array_map('strval',$enabled))));
        usort($enabled,static function($a,$b) use($orders,$allowed): int {
            $pa=min(6,max(1,(int)($orders[$a]??6)));
            $pb=min(6,max(1,(int)($orders[$b]??6)));
            return ($pa<=>$pb) ?: (array_search($a,$allowed,true)<=>array_search($b,$allowed,true));
        });
        $maps=json_decode(config_value('template_sections_by_type','{}'),true);
        if (!is_array($maps)) $maps=[];
        $maps[site_template()]=$enabled;
        $q=database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        $q->execute(['template_sections_by_type',json_encode($maps)]);
        $q->execute(['template_sections',json_encode($enabled)]);
        log_action('template.sections',implode(',',$enabled));
        $_SESSION['flash']='Порядок и видимость блоков сохранены.';
        $back='/admin/index.php?section=templates&view=edit&tab=blocks';
    } elseif ($action === 'save_content') {
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
        if($kind==='page') {
            $opt=['before'=>'','after'=>'','left'=>[],'right'=>[]];
            foreach(['before','after'] as $slot){
                $v=(string)($_POST['page_'.$slot]??'');
                if($v!==''&&!isset(cms_modules()[$v]))throw new RuntimeException('Недопустимый модуль страницы.');
                $opt[$slot]=$v;
            }
            foreach(['left','right'] as $side){
                $raw=$_POST['page_'.$side]??[];
                if(!is_array($raw)||count($raw)>count(cms_modules()))
                    throw new RuntimeException('Недопустимые боковые блоки.');
                foreach($raw as $mod){
                    if(!is_string($mod)||!isset(cms_modules()[$mod]))throw new RuntimeException('Недопустимый боковой модуль.');
                    if(!in_array($mod,$opt[$side],true))$opt[$side][]=$mod;
                }
            }
            $maps=json_decode(config_value('cms_page_options','{}'),true);
            if(!is_array($maps))$maps=[];
            $maps[(string)$id]=$opt;
            database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')
                ->execute(['cms_page_options',json_encode($maps,JSON_UNESCAPED_UNICODE)]);
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
        $values = [
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
        $permissions = array_values(array_intersect([...array_keys(kinds()),'media'],array_map('strval',$permissions)));
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
