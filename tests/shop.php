<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
function shop_check(bool $condition,string $message):void {
 if(!$condition){fwrite(STDERR,"[FAIL] $message\n");exit(1);}
 echo "[OK] $message\n";
}
function shop_reject(callable $work,string $message):void {
 try{$work();}catch(RuntimeException $e){shop_check(true,$message);return;}
 shop_check(false,$message);
}
$configFile=dirname(__DIR__).'/storage/config.php';
if(is_file($configFile))throw new RuntimeException('Нельзя запускать тест магазина в установленной CMS.');
$testConfig=['host'=>getenv('TEST_DB_HOST')?:'127.0.0.1',
 'port'=>3306,'name'=>getenv('TEST_DB_NAME')?:'dagcms_test',
 'user'=>getenv('TEST_DB_USER')?:'root','pass'=>getenv('TEST_DB_PASS')?:''];
file_put_contents($configFile,'<?php return '.var_export($testConfig,true).';');
register_shutdown_function(static function()use($configFile):void{@unlink($configFile);});
require dirname(__DIR__).'/app/core.php';
shop_check(cms_shop_payment_methods()['standard']==='Оформить заявку (без онлайн-оплаты)',
 'Стандартная оплата создаёт заявку, не требует карты');
shop_check(cms_shop_money(cms_shop_price_cents('2450.25'))==='2 450,25 ₽','Точная цена из копеек');
shop_reject(fn()=>cms_shop_price_cents('-5.00'),'Отрицательная цена запрещена');
shop_reject(fn()=>cms_shop_price_cents('abc'),'Некорректная цена запрещена');
shop_check(cms_shop_sort_sql('price_asc')==='c.price ASC,c.id DESC','Сортировка цены определена на сервере');
shop_check(cms_shop_sort_sql('x')==='COALESCE(m.sort_order,100) ASC,c.created_at DESC',
 'Невалидная сортировка использует безопасное правило');
$example=['id'=>99,'title'=>'Тестовый товар','price'=>'1999.95'];
$data=['customer_name'=>'Тестовый клиент','customer_phone'=>'+7 999 111-22-33',
 'customer_email'=>'order@example.test','customer_address'=>'Адрес доставки','customer_note'=>'Комментарий',
 'payment_method'=>'standard','quantity'=>'3','consent'=>'1'];
$parsed=cms_shop_validate_order($data,$example,['standard']);
shop_check($parsed['quantity']===3&&$parsed['total']===599985,'Количество и итог вычисляются на сервере');
shop_reject(fn()=>cms_shop_validate_order([...$data,'payment_method'=>'unverified-provider'],$example,['standard']),
 'Неразрешённый способ оплаты отклонён');
shop_reject(fn()=>cms_shop_validate_order([...$data,'consent'=>'0'],$example,['standard']),
 'Без согласия заказ не оформляется');
shop_reject(fn()=>cms_shop_validate_order([...$data,'quantity'=>'100'],$example,['standard']),
 'Количество больше разрешённого отклонено');
$settings=cms_shop_validate_settings(['featured_title'=>'Хиты','catalog_title'=>'Каталог',
 'featured_limit'=>'4','catalog_limit'=>'8','methods'=>['standard','arrival'],
 'home_featured'=>'1','home_categories'=>'1','home_catalog'=>'1','transfer_text'=>'']);
shop_check($settings['methods']===['standard','arrival']&&$settings['featured_limit']===4,
 'Оплата и витрина имеют настраиваемые параметры');
shop_reject(fn()=>cms_shop_validate_settings(['methods'=>['fake-provider']]),
 'Неподдерживаемые способы оплаты нельзя сохранить');
cms_shop_tables();
$db=database();
$db->beginTransaction();
try{
  $db->prepare('INSERT INTO content(kind,title,slug,summary,price,status) VALUES(?,?,?,?,?,?)')
      ->execute(['product','Тестовый товар для магазина','shop-test-'.bin2hex(random_bytes(5)),
                'Описание продукта','1999.95','published']);
  $productId=(int)$db->lastInsertId();
  $db->prepare('INSERT INTO cms_shop_categories(name,slug,sort_order) VALUES(?,?,?)')
      ->execute(['Тестовая категория','test-cat-'.bin2hex(random_bytes(5)),1]);
  $category=(int)$db->lastInsertId();
  cms_shop_update_product($productId,(string)$category,'1','3');
  $meta=cms_shop_product_meta($productId);
  shop_check((int)$meta['category_id']===$category && (int)$meta['featured']===1,'Категория, лучшие товары и порядок сохраняются');
  $products=cms_shop_products($category,'custom',true,10);
  shop_check(count($products)===1&&(int)$products[0]['id']===$productId,'Каталог фильтруется по категории и лучшим товарам');
  $number=cms_shop_create_order($products[0],$parsed,'test-2026');
  $q=$db->prepare('SELECT * FROM cms_shop_orders WHERE public_number=?');
  $q->execute([$number]);$order=$q->fetch();
  shop_check($order!==false&&$order['order_status']==='new'&&$order['payment_status']==='unpaid',
    'Новый заказ попадает в базу как неоплаченная заявка');
  shop_check((float)$order['total_price']===5999.85&&$order['payment_method']==='standard'&&
    $order['customer_name']==='Тестовый клиент','Цена, покупатель и способ сохраняются в заказе');
  shop_check($order['consent_text_hash']===hash('sha256',cms_shop_consent_text()),
    'Отдельное согласие сохраняется в заказе');
  $db->prepare('UPDATE content SET price=? WHERE id=?')->execute(['2999.00',$productId]);
  $q->execute([$number]);$after=$q->fetch();
  shop_check((float)$after['total_price']===5999.85,'Изменение цены товара не меняет прошлый заказ');
}finally{if($db->inTransaction())$db->rollBack();}
$admin=file_get_contents(dirname(__DIR__).'/admin/index.php');
$checkout=file_get_contents(dirname(__DIR__).'/order.php');
$source=file_get_contents(dirname(__DIR__).'/app/site-render.php');
shop_check(str_contains($admin,"$"."section==='orders'")&&str_contains($admin,"$"."section==='shop'"),
  'Разделы заказов и категорий подключены к админке');
shop_check(str_contains($checkout,'verify_token()')&&str_contains($checkout,'cms_shop_validate_order')&&
  str_contains($checkout,'name="payment_method"')&&str_contains($checkout,'name="consent"'),
  'Форма заказа с CSRF, согласием и способом оплаты');
shop_check(str_contains($source,'cms_shop_render_home')&&str_contains($source,'cms_shop_render_catalog'),
  'Категории, лучшие товары и каталог интегрированы в главную');
echo "Магазин и заявки успешно проверены.\n";
