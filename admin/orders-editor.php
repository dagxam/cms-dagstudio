<?php
declare(strict_types=1);
if(!defined('DAG_CMS_ADMIN_VIEW')){http_response_code(404);exit;}
cms_shop_tables();
$filter=(string)($_GET['status']??'');
if($filter!==''&&!isset(cms_shop_order_statuses()[$filter]))$filter='';
if($filter!==''){
 $q=database()->prepare('SELECT * FROM cms_shop_orders WHERE order_status=? ORDER BY created_at DESC,id DESC LIMIT 150');
 $q->execute([$filter]);$orders=$q->fetchAll();
}else $orders=database()->query('SELECT * FROM cms_shop_orders ORDER BY created_at DESC,id DESC LIMIT 150')->fetchAll();
?>
<div class="heading-row"><div><div class="eyebrow">МАГАЗИН / ЗАЯВКИ</div><h1>Заказы покупателей</h1>
<p class="muted">Заявки из формы магазина. Изменение статуса оплаты выполняется вручную после фактического подтверждения поступления денег.</p>
</div></div>
<nav class="cms-orders-filters" aria-label="Фильтр заказов">
<a class="<?=$filter===''?'active':''?>" href="?section=orders">Все</a>
<?php foreach(cms_shop_order_statuses() as $id=>$name):?><a class="<?=$filter===$id?'active':''?>" href="?section=orders&amp;status=<?=h($id)?>"><?=h($name)?></a><?php endforeach;?>
</nav>
<div class="cms-orders-list">
<?php foreach($orders as $order):?>
<article class="box cms-order-card">
 <div class="cms-order-top"><div><span class="eyebrow">ЗАКАЗ № <?=h($order['public_number'])?></span>
 <h2><?=h($order['product_title'])?> × <?=(int)$order['quantity']?></h2>
 <small><?=h($order['created_at'])?></small></div>
 <strong class="cms-order-total"><?=h(number_format((float)$order['total_price'],2,',',' '))?> ₽</strong></div>
 <div class="cms-order-data">
 <p><i class="fa-solid fa-user"></i> <?=h($order['customer_name'])?></p>
 <p><i class="fa-solid fa-phone"></i> <a href="tel:<?=h(preg_replace('/[^0-9+]/','',$order['customer_phone']))?>"><?=h($order['customer_phone'])?></a></p>
 <p><i class="fa-solid fa-envelope"></i> <a href="mailto:<?=h($order['customer_email'])?>"><?=h($order['customer_email'])?></a></p>
 <?php if($order['customer_address']!==''):?><p><i class="fa-solid fa-location-dot"></i> <?=h($order['customer_address'])?></p><?php endif;?>
 <?php if($order['customer_note']!==''):?><p><i class="fa-solid fa-comment"></i> <?=nl2br(h($order['customer_note']))?></p><?php endif;?>
 <p><i class="fa-solid fa-credit-card"></i> <?=h(cms_shop_payment_methods()[$order['payment_method']]??$order['payment_method'])?></p>
 </div>
 <form class="cms-order-actions" action="/admin/actions.php" method="post">
 <?=csrf()?><input type="hidden" name="action" value="shop_update_order"><input type="hidden" name="id" value="<?=(int)$order['id']?>">
 <label>Обработка<select name="order_status">
 <?php foreach(cms_shop_order_statuses() as $id=>$name):?><option value="<?=h($id)?>" <?=$order['order_status']===$id?'selected':''?>><?=h($name)?></option><?php endforeach;?>
 </select></label>
 <label>Оплата<select name="payment_status">
 <option value="unpaid" <?=$order['payment_status']==='unpaid'?'selected':''?>>Не оплачен</option>
 <option value="paid" <?=$order['payment_status']==='paid'?'selected':''?>>Оплата подтверждена</option>
 </select></label>
 <button class="button" type="submit">Сохранить статус</button>
 </form>
</article>
<?php endforeach;?>
<?php if(!$orders):?><div class="box cms-orders-empty"><i class="fa-solid fa-box-open"></i><h2>Заказов пока нет</h2><p>После отправки формы покупателем заявка появится здесь.</p></div><?php endif;?>
</div>