<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$cfg=['cms_home_blocks_by_type'=>'{}'];
function config_value(string $key,string $default=''):string{global $cfg;return $cfg[$key]??$default;}
function h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check_home(bool $ok,string $label):void {
 if(!$ok){fwrite(STDERR,"[FAIL] $label\n");exit(1);}echo "[OK] $label\n";
}
function reject_home(callable $work,string $label):void {
 try{$work();}catch(RuntimeException $e){check_home(true,$label);return;}
 check_home(false,$label);
}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/home-blocks.php';
check_home(count(cms_home_types())===4,'Четыре типа блоков доступны');
check_home(count(cms_home_blocks('organization'))===0,'Старые сайты не получают лишних блоков');
$data=['type'=>'cards','placement'=>'top','title'=>'Проверка <script>','text'=>"Описание\nстрока",
       'kicker'=>'Важно','button'=>'Подробнее','url'=>'/?kind=news','enabled'=>'1',
       'items'=>[['title'=>'Карточка 1','text'=>'Текст карточки','url'=>'/?kind=page'],
                 ['title'=>'Карточка 2','text'=>'Описание','url'=>'https://example.org']]];
$one=cms_home_normalize($data);
check_home(preg_match('/^[a-f0-9]{16}$/D',$one['id'])===1&&count($one['items'])===2,'Добавление и нормализация карточек');
$updated=cms_home_normalize([...$data,'title'=>'Новая редакция'],$one);
check_home($updated['id']===$one['id']&&$updated['title']==='Новая редакция','Редактирование сохраняет ID');
reject_home(fn()=>cms_home_normalize([...$data,'url'=>'javascript:alert(1)']),'Запрещены опасные ссылки');
reject_home(fn()=>cms_home_normalize([...$data,'title'=>'']),'Пустой заголовок запрещён');
reject_home(fn()=>cms_home_normalize([...$data,'items'=>array_fill(0,4,['title'=>'x'])]),'Максимум три карточки');
reject_home(fn()=>cms_home_normalize([...$data,'items'=>[]]),'Нельзя сохранить пустой набор карточек');
reject_home(fn()=>cms_home_normalize([...$data,'type'=>'stats','items'=>[['title'=>'7+','text'=>'лет','url'=>'/?p=one']]]),'Показатель не может иметь ссылку');
$other=cms_home_normalize(['type'=>'banner','placement'=>'bottom','title'=>'Баннер','text'=>'Текст баннера','enabled'=>'0']);
$cfg['cms_home_blocks_by_type']=json_encode(['company'=>[$one,$other], 'government'=>[$updated]],JSON_UNESCAPED_UNICODE);
check_home(count(cms_home_blocks('company'))===2&&count(cms_home_blocks('government'))===1,'Темы хранят индивидуальные блоки');
check_home(count(cms_home_blocks('store'))===0,'Новая тема не наследует чужие блоки');
check_home(cms_home_index(cms_home_blocks('company'),$other['id'])===1,'Поиск блока для редактирования и удаления');
reject_home(fn()=>cms_home_index(cms_home_blocks('company'),'missing'),'Нельзя менять несуществующий блок');
ob_start();cms_home_render('company','top');$html=ob_get_clean();
check_home(str_contains($html,'cms-custom-home-cards')&&str_contains($html,'Карточка 1'),'Карточки отрисовываются');
check_home(str_contains($html,'&lt;script&gt;')&&!str_contains($html,'<script>'),'Текст блоков экранируется');
ob_start();cms_home_render('company','bottom');$html=ob_get_clean();
check_home(!str_contains($html,'Баннер'),'Скрытые блоки не выводятся');
ob_start();cms_home_render('government','top');$html=ob_get_clean();
check_home(str_contains($html,'Новая редакция')&&!str_contains($html,'Проверка '),'Чужие блоки на сайт администрации не попадают');
echo "Блоки главной протестированы.\n";
