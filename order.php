<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
header('Cache-Control: private, no-store, max-age=0');
if(!installed()||site_template()!=='store'||!cms_module_enabled('product')){http_response_code(404);exit('Магазин недоступен.');}
cms_shop_tables();
$id=filter_input(INPUT_GET,'product',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
if($_SERVER['REQUEST_METHOD']==='POST')$id=filter_input(INPUT_POST,'product',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$q=database()->prepare("SELECT id,title,slug,price FROM content WHERE id=? AND kind='product' AND status='published'");
$q->execute([$id]);$product=$q->fetch();
if(!$product || $product['price']===null){http_response_code(404);exit('Товар не найден или цена не указана.');}
try{$unit=cms_shop_price_cents((string)$product['price']);}catch(RuntimeException $e){http_response_code(404);exit('Заказ товара временно недоступен.');}
$settings=cms_shop_settings();
$ready=cms_privacy()['ready'];
$error='';$complete='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_token();
    if(!empty($_POST['website'])){http_response_code(400);exit('Недопустимая отправка.');}
    if(!$ready){http_response_code(400);$error='Для приёма заказа должна быть опубликована политика обработки персональных данных.';}
    elseif(time()-(int)($_SESSION['last_shop_order']??0)<45){http_response_code(429);$error='Вы недавно отправили заказ. Попробуйте через минуту.';}
    else{
        try{
            $data=cms_shop_validate_order($_POST,$product,$settings['methods']);
            $complete=cms_shop_create_order($product,$data,cms_privacy()['version']);
            $_SESSION['last_shop_order']=time();
        }catch(RuntimeException $e){http_response_code(422);$error=$e->getMessage();}
    }
}
?><!doctype html><html lang="ru" data-theme-storage-key="dagstudio-template-store" data-theme-default="light">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Оформление заказа — <?=h(config_value('site_name','Магазин'))?></title>
<script>try{document.documentElement.dataset.theme=localStorage.getItem("dagstudio-template-store")||"light"}catch(e){document.documentElement.dataset.theme="light"}</script>
<link rel="stylesheet" href="/assets/style.css?v=fa672-local2">
<link rel="stylesheet" href="/assets/templates.css?v=shop1"><link rel="stylesheet" href="/assets/media.css?v=menus2">
<script src="/assets/theme.js" defer></script></head>
<body class="site-page site-template-store cms-shop-checkout-page" style="<?=h(template_style(template_design('store'),'store'))?>">
<a class="cms-skip-link" href="#cms-checkout-main">Перейти к оформлению заказа</a>
<?php cms_render_public_header('store');?>
<main class="container cms-shop-checkout" id="cms-checkout-main">
<a class="cms-shop-back" href="/?p=<?=rawurlencode($product['slug'])?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Назад к товару</a>
<?php if($complete!==''):?>
<section class="cms-shop-success box" role="status">
<i class="fa-solid fa-circle-check" aria-hidden="true"></i>
<h1>Заказ получен</h1>
<p>Номер вашей заявки: <strong><?=h($complete)?></strong>. Сотрудник магазина свяжется с вами, чтобы подтвердить заказ и согласовать оплату.</p>
<p>Заказ не считается оплаченным. На этой странице банковские карты не обрабатываются.</p>
<a class="button" href="/">На главную магазина</a>
</section>
<?php else:?>
<div class="cms-shop-checkout-head"><span class="eyebrow">МАГАЗИН / ЗАКАЗ</span><h1>Оформление заказа</h1>
<p>Заполните данные для связи. Заявка попадёт в раздел «Заказы» администрации магазина.</p></div>
<div class="cms-shop-checkout-layout">
<form class="cms-shop-checkout-form box" method="post" action="/order.php" autocomplete="on">
<?=csrf()?><input type="hidden" name="product" value="<?=(int)$product['id']?>">
<div class="honeypot" aria-hidden="true"><label>Сайт<input name="website" tabindex="-1" autocomplete="off"></label></div>
<?php if($error!==''):?><div class="error" role="alert"><?=h($error)?></div><?php endif;?>
<h2>Контактная информация</h2>
<label>Фамилия и имя *<input name="customer_name" required minlength="2" maxlength="120" autocomplete="name" value="<?=h(is_string($_POST['customer_name']??null)?$_POST['customer_name']:'')?>"></label>
<label>Номер телефона *<input name="customer_phone" type="tel" required maxlength="40" autocomplete="tel" placeholder="+7 900 000-00-00" value="<?=h(is_string($_POST['customer_phone']??null)?$_POST['customer_phone']:'')?>"></label>
<label>Электронная почта *<input name="customer_email" type="email" required maxlength="190" autocomplete="email" value="<?=h(is_string($_POST['customer_email']??null)?$_POST['customer_email']:'')?>"></label>
<label>Адрес доставки (если требуется)<input name="customer_address" maxlength="500" autocomplete="street-address" value="<?=h(is_string($_POST['customer_address']??null)?$_POST['customer_address']:'')?>"></label>
<label>Комментарий к заказу<textarea name="customer_note" maxlength="1500" rows="3"><?=h(is_string($_POST['customer_note']??null)?$_POST['customer_note']:'')?></textarea></label>
<label>Количество *<input type="number" name="quantity" min="1" max="99" value="<?=h((string)(is_scalar($_POST['quantity']??null)?$_POST['quantity']:'1'))?>" required></label>
<h2>Способ оплаты</h2>
<fieldset class="cms-shop-payment-choices"><legend>Выберите вариант</legend>
<?php foreach(cms_shop_payment_methods() as $key=>$label):if(!in_array($key,$settings['methods'],true))continue;?>
<label class="cms-shop-payment-choice"><input type="radio" name="payment_method" value="<?=h($key)?>" <?=($_POST['payment_method']??$settings['methods'][0])===$key?'checked':''?> required><span><?=h($label)?></span></label>
<?php endforeach;?></fieldset>
<?php if(in_array('transfer',$settings['methods'],true)&&$settings['transfer_text']!==''):?>
<p class="cms-shop-transfer-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> При выборе перевода: <?=nl2br(h($settings['transfer_text']))?></p><?php endif;?>
<p class="cms-shop-payment-notice">Отправка заявки не означает оплату. Интернет-эквайринг и ввод реквизитов карты здесь не используются.</p>
<?php if($ready):?>
<label class="check privacy-check"><input type="checkbox" name="consent" value="1" required>
 <span><?=h(cms_shop_consent_text())?> <a href="/privacy.php" target="_blank" rel="noopener">Политика обработки персональных данных</a>.</span></label>
<button class="button cms-shop-submit" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Отправить заказ</button>
<?php else:?><div class="error" role="alert">Приём заказов отключён до настройки и публикации политики персональных данных.</div><?php endif;?>
</form>
<aside class="cms-shop-order-summary box"><h2>Ваш заказ</h2><strong><?=h($product['title'])?></strong>
<p>Цена за 1 шт.</p><span class="cms-shop-order-price"><?=h(cms_shop_money($unit))?></span>
<p>Итоговая сумма зависит от выбранного количества. Доставка и условия оплаты согласовываются отдельно.</p>
</aside></div>
<?php endif;?></main>
<footer class="site-footer"><div class="container footer-inner"><span><?=h(config_value('site_name','Магазин'))?></span><a href="/privacy.php">Политика конфиденциальности</a></div></footer>
</body></html>