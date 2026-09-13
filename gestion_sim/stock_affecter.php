<?php
require_once __DIR__.'/config/database.php'; require_once __DIR__.'/config/google_sheets.php'; require_login();
if(!is_admin()){header('Location: dashboard.php');exit;}
$page_title='Affecter des SIM';$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $seller=(int)($_POST['seller_id']??0);$operator=trim($_POST['operator']??'');$qty=(int)($_POST['quantity']??0);$notes=trim($_POST['notes']??'');
 if(!$seller||!$operator||$qty<1)$error='Veuillez sélectionner le vendeur, l’opérateur et une quantité valide.';
 else{
  try{$pdo->beginTransaction();$st=$pdo->prepare('SELECT id,quantity_remaining FROM sim_stock_lots WHERE operator=? AND quantity_remaining>0 ORDER BY received_date ASC,id ASC LIMIT 1 FOR UPDATE');$st->execute([$operator]);$lot=$st->fetch();if(!$lot)throw new RuntimeException('NO_STOCK');$available=(int)$pdo->query("SELECT COALESCE(SUM(quantity_remaining),0) FROM sim_stock_lots WHERE operator=".$pdo->quote($operator))->fetchColumn();if($available<$qty)throw new RuntimeException('NOT_ENOUGH');
   $remain=$qty;while($remain>0){$st=$pdo->prepare('SELECT id,quantity_remaining FROM sim_stock_lots WHERE operator=? AND quantity_remaining>0 ORDER BY received_date ASC,id ASC LIMIT 1 FOR UPDATE');$st->execute([$operator]);$lot=$st->fetch();if(!$lot)throw new RuntimeException('NOT_ENOUGH');$take=min($remain,(int)$lot['quantity_remaining']);$pdo->prepare('UPDATE sim_stock_lots SET quantity_remaining=quantity_remaining-? WHERE id=?')->execute([$take,$lot['id']]);$remain-=$take;}
   $pdo->prepare('INSERT INTO sim_stock_affectations(seller_id,operator,quantity_initial,quantity_remaining,assigned_date,notes) VALUES(?,?,?,?,?,?)')->execute([$seller,$operator,$qty,$qty,date('Y-m-d'),$notes]);$pdo->commit();$success=$qty.' SIM affectée(s) au vendeur.';if(google_sheets_url($pdo)!=='')sync_google_sheets($pdo);
  }catch(RuntimeException $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage()==='NO_STOCK'||$e->getMessage()==='NOT_ENOUGH'?'Stock '.$operator.' insuffisant pour cette affectation.':'Erreur lors de l’affectation.';}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Erreur lors de l’affectation.';}
 }
}
$sellers=$pdo->query("SELECT id,full_name,seller_phone FROM users WHERE role='vendeur' AND active=1 ORDER BY full_name")->fetchAll();
$stock=$pdo->query('SELECT operator,SUM(quantity_remaining) qty FROM sim_stock_lots WHERE quantity_remaining>0 GROUP BY operator ORDER BY operator')->fetchAll();
include __DIR__.'/includes/header.php';?>
<div class="page-head"><div><h1>Affecter des SIM à un vendeur</h1><p class="muted">L’affectation se fait par quantité. Aucun numéro de SIM du stock n’est affiché au vendeur.</p></div><a class="btn secondary" href="stock.php">← Retour au stock</a></div>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=e($success)?></div><?php endif;?>
<div class="cards"><?php foreach($stock as $s):?><div class="card"><div class="muted">Stock <?=e($s['operator'])?></div><div class="stat"><?=e((string)$s['qty'])?></div></div><?php endforeach;?></div>
<div class="form-card"><form method="post"><div class="grid2"><label>Vendeur *<select name="seller_id" required><option value="">Choisir un vendeur</option><?php foreach($sellers as $s):?><option value="<?=$s['id']?>"><?=e($s['full_name'])?><?= $s['seller_phone']?' — '.e($s['seller_phone']):''?></option><?php endforeach;?></select></label><label>Opérateur *<select name="operator" required><option value="">Choisir</option><option>Yas</option><option>Orange</option><option>Airtel</option></select></label><label>Quantité *<input type="number" name="quantity" min="1" required></label></div><label>Observation<textarea name="notes" placeholder="Observation facultative"></textarea></label><div class="actions"><button class="btn primary">Affecter au vendeur</button><a class="btn secondary" href="stock.php">Annuler</a></div></form></div>
<?php include __DIR__.'/includes/footer.php';?>
