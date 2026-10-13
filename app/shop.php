<?php
declare(strict_types=1);
/** Категории, витрина и заявки на покупку. Онлайн-эквайринг здесь НЕ подключается. */
function cms_shop_tables():void{
    static $ready=false;if($ready)return;
    $db=database();
    $db->exec("CREATE TABLE IF NOT EXISTS cms_shop_categories(
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL, slug VARCHAR(120) NOT NULL UNIQUE,
        sort_order INT NOT NULL DEFAULT 100,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS cms_shop_product_meta(
        product_id BIGINT UNSIGNED PRIMARY KEY,
        category_id BIGINT UNSIGNED NULL,
        featured TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 100,
        FOREIGN KEY(product_id) REFERENCES content(id) ON DELETE CASCADE,
        FOREIGN KEY(category_id) REFERENCES cms_shop_categories(id) ON DELETE SET NULL,
        KEY idx_shop_featured(featured,sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS cms_shop_orders(
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_number CHAR(16) NOT NULL UNIQUE,
        product_id BIGINT UNSIGNED NULL,
        product_title VARCHAR(250) NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        quantity SMALLINT UNSIGNED NOT NULL,
        total_price DECIMAL(14,2) NOT NULL,
        customer_name VARCHAR(120) NOT NULL,
        customer_phone VARCHAR(40) NOT NULL,
        customer_email VARCHAR(190) NOT NULL,
        customer_address VARCHAR(500) NOT NULL DEFAULT '',
        customer_note TEXT NULL,
        payment_method VARCHAR(30) NOT NULL DEFAULT 'standard',
        payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
        order_status VARCHAR(20) NOT NULL DEFAULT 'new',
        consent_version VARCHAR(50) NOT NULL,
        consent_text_hash CHAR(64) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_orders_status(order_status,created_at),
        FOREIGN KEY(product_id) REFERENCES content(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready=true;
}
function cms_shop_categories():array{
    cms_shop_tables();
    return database()->query("SELECT id,name,slug,sort_order FROM cms_shop_categories ORDER BY sort_order ASC,name ASC,id ASC")->fetchAll();
}
function cms_shop_category_save(int $id,string $name,int $order):void{
    cms_shop_tables();$name=trim($name);
    if(mb_strlen($name)<2||mb_strlen($name)>120||$order<1||$order>9999)throw new RuntimeException('Проверьте название и порядок категории.');
    if($id>0){
        $q=database()->prepare('UPDATE cms_shop_categories SET name=?,sort_order=? WHERE id=?');
        $q->execute([$name,$order,$id]);
        // Повторное сохранение без изменения данных допустимо.
    }else{
        $slug=slugify($name);
        if($slug==='')throw new RuntimeException('Укажите название категории буквами или цифрами.');
        $slug=mb_substr($slug,0,100);
        database()->prepare('INSERT INTO cms_shop_categories(name,slug,sort_order) VALUES(?,?,?)')
            ->execute([$name,$slug,$order]);
    }
}
function cms_shop_product_meta(int $id):array{
    cms_shop_tables();
    $q=database()->prepare('SELECT category_id,featured,sort_order FROM cms_shop_product_meta WHERE product_id=?');
    $q->execute([$id]);return $q->fetch()?:['category_id'=>null,'featured'=>0,'sort_order'=>100];
}
function cms_shop_update_product(int $id,mixed $category,mixed $featured,mixed $sort):void{
    cms_shop_tables();
    if($id<1||!is_string($category)||($category!==''&&!ctype_digit($category))||
       !is_string($sort)||!ctype_digit($sort)||(int)$sort<1||(int)$sort>9999)
        throw new RuntimeException('Проверьте категорию и сортировку товара.');
    $cat=$category===''?null:(int)$category;
    if($cat!==null){
        $q=database()->prepare('SELECT id FROM cms_shop_categories WHERE id=?');
        $q->execute([$cat]);if(!$q->fetchColumn())throw new RuntimeException('Категория не найдена.');
    }
    database()->prepare('INSERT INTO cms_shop_product_meta(product_id,category_id,featured,sort_order) VALUES(?,?,?,?)
      ON DUPLICATE KEY UPDATE category_id=VALUES(category_id),featured=VALUES(featured),sort_order=VALUES(sort_order)')
      ->execute([$id,$cat,$featured==='1'?1:0,(int)$sort]);
}
function cms_shop_settings():array{
    $defaults=['featured_title'=>'Лучшие товары','featured_limit'=>8,'catalog_title'=>'Каталог магазина',
        'home_categories'=>true,'home_featured'=>true,'home_catalog'=>true,'catalog_limit'=>8,
        'methods'=>['standard'],'transfer_text'=>''];
    $s=json_decode(config_value('cms_shop_settings','{}'),true);
    if(!is_array($s))return $defaults;
    foreach(['featured_title','catalog_title'] as $name)
        if(is_string($s[$name]??null)&&mb_strlen($s[$name])<=120)$defaults[$name]=trim($s[$name]);
    foreach(['featured_limit','catalog_limit'] as $name)
        if(is_int($s[$name]??null)&&$s[$name]>=1&&$s[$name]<=24)$defaults[$name]=$s[$name];
    foreach(['home_categories','home_featured','home_catalog'] as $name)
        if(is_bool($s[$name]??null))$defaults[$name]=$s[$name];
    if(is_string($s['transfer_text']??null)&&mb_strlen($s['transfer_text'])<=1500)$defaults['transfer_text']=$s['transfer_text'];
    if(is_array($s['methods']??null)){
        $active=array_values(array_intersect(['standard','arrival','transfer'],$s['methods']));
        $defaults['methods']=$active?:['standard'];
    }
    return $defaults;
}
function cms_shop_payment_methods():array{
    return ['standard'=>'Оформить заявку (без онлайн-оплаты)',
        'arrival'=>'Оплата при получении (после согласования)',
        'transfer'=>'Банковский перевод по реквизитам продавца'];
}
function cms_shop_validate_settings(array $raw):array{
    $s=cms_shop_settings();
    foreach(['featured_title','catalog_title'] as $key){
        if(!is_string($raw[$key]??null)||mb_strlen($raw[$key])>120)throw new RuntimeException('Проверьте заголовок.');
        $s[$key]=trim($raw[$key]);
    }
    foreach(['featured_limit','catalog_limit'] as $key){
        $v=$raw[$key]??null;
        if(!is_string($v)||!ctype_digit($v)||(int)$v<1||(int)$v>24)throw new RuntimeException('Количество товаров: от 1 до 24.');
        $s[$key]=(int)$v;
    }
    foreach(['home_categories','home_featured','home_catalog'] as $key)$s[$key]=isset($raw[$key])&&$raw[$key]==='1';
    $methods=$raw['methods']??[];
    if(!is_array($methods)||array_diff($methods,array_keys(cms_shop_payment_methods()))||!$methods)
        throw new RuntimeException('Выберите хотя бы один способ оформления.');
    $s['methods']=array_values(array_intersect(array_keys(cms_shop_payment_methods()),$methods));
    $text=$raw['transfer_text']??'';
    if(!is_string($text)||mb_strlen($text)>1500)throw new RuntimeException('Слишком длинное описание оплаты.');
    $s['transfer_text']=trim($text);
    return $s;
}
function cms_shop_sort_types():array{
    return ['custom'=>'По порядку в админке','new'=>'Сначала новые','price_asc'=>'Сначала дешевле',
        'price_desc'=>'Сначала дороже','name'=>'По названию'];
}
function cms_shop_sort_sql(string $sort):string{
    return match($sort){'new'=>'c.created_at DESC,c.id DESC',
        'price_asc'=>'c.price ASC,c.id DESC','price_desc'=>'c.price DESC,c.id DESC',
        'name'=>'c.title ASC,c.id ASC',default=>'COALESCE(m.sort_order,100) ASC,c.created_at DESC'};
}
function cms_shop_products(?int $category=null,string $sort='custom',bool $featured=false,int $limit=60):array{
    cms_shop_tables();
    $where="c.kind='product' AND c.status='published'";$args=[];
    if($category!==null){$where.=' AND m.category_id=?';$args[]=$category;}
    if($featured)$where.=' AND m.featured=1';
    $sql="SELECT c.*, m.category_id,COALESCE(m.featured,0) featured,COALESCE(m.sort_order,100) sort_order,
          t.name category_name,t.slug category_slug
          FROM content c LEFT JOIN cms_shop_product_meta m ON m.product_id=c.id
          LEFT JOIN cms_shop_categories t ON t.id=m.category_id WHERE ".$where.
         ' ORDER BY '.cms_shop_sort_sql($sort).' LIMIT '.max(1,min(100,$limit));
    $q=database()->prepare($sql);$q->execute($args);return $q->fetchAll();
}
function cms_shop_price_cents(string $price):int {
    if(!preg_match('/^([0-9]{1,10})(?:\.([0-9]{1,2}))?$/D',$price))throw new RuntimeException('У товара нет корректной цены.');
    [$r,$k]=array_pad(explode('.',$price,2),2,'');
    $cents=(int)$r*100+(int)str_pad($k,2,'0');
    if($cents<=0||$cents>999999999999)throw new RuntimeException('У товара нет корректной цены.');
    return $cents;
}
function cms_shop_consent_text():string{
    return 'Соглашаюсь на обработку имени, телефона, электронной почты, адреса и комментария исключительно для оформления и исполнения заказа. Ознакомлен(а) с политикой обработки персональных данных.';
}
function cms_shop_validate_order(array $raw,array $item,array $methods):array{
    $name=$raw['customer_name']??null;$phone=$raw['customer_phone']??null;
    $email=$raw['customer_email']??null;$address=$raw['customer_address']??null;
    $note=$raw['customer_note']??null;$payment=$raw['payment_method']??null;
    $count=$raw['quantity']??null;
    foreach([$name,$phone,$email,$address,$note,$payment,$count] as $v)
        if(!is_string($v))throw new RuntimeException('Проверьте поля заказа.');
    $name=trim($name);$phone=trim($phone);$email=trim($email);$address=trim($address);$note=trim($note);
    if(mb_strlen($name)<2||mb_strlen($name)>120||!preg_match('/^\+?[0-9()\s-]{7,40}$/D',$phone)||
       !filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>190||
       mb_strlen($address)>500||mb_strlen($note)>1500||!in_array($payment,$methods,true)||
       !ctype_digit($count)||(int)$count<1||(int)$count>99)
        throw new RuntimeException('Проверьте имя, телефон, почту, количество и способ оплаты.');
    if(($raw['consent']??null)!=='1')throw new RuntimeException('Необходимо отдельное согласие на обработку данных заказа.');
    $unit=cms_shop_price_cents((string)$item['price']);
    $total=$unit*(int)$count;
    return ['name'=>$name,'phone'=>$phone,'email'=>$email,'address'=>$address,'note'=>$note,
      'payment'=>$payment,'quantity'=>(int)$count,'price'=>$unit,'total'=>$total];
}
function cms_shop_money(int $cents):string{return number_format($cents/100,2,',',' ').' ₽';}
function cms_shop_create_order(array $product,array $order,string $version):string{
    cms_shop_tables();$number=strtoupper(bin2hex(random_bytes(8)));
    $db=database();
    $q=$db->prepare('INSERT INTO cms_shop_orders(public_number,product_id,product_title,unit_price,quantity,total_price,
      customer_name,customer_phone,customer_email,customer_address,customer_note,payment_method,consent_version,consent_text_hash)
      VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$number,(int)$product['id'],(string)$product['title'],
        number_format($order['price']/100,2,'.',''),$order['quantity'],
        number_format($order['total']/100,2,'.',''),$order['name'],$order['phone'],$order['email'],
        $order['address'],$order['note'],$order['payment'],$version,hash('sha256',cms_shop_consent_text())]);
    return $number;
}
function cms_shop_order_statuses():array{return ['new'=>'Новый','processing'=>'В работе','completed'=>'Выполнен','cancelled'=>'Отменён'];}
