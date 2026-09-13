<?php
require_once __DIR__.'/config/database.php'; require_once __DIR__.'/config/google_sheets.php'; require_login();
if(!is_admin()){header('Location: dashboard.php');exit;}
$page_title='Gestion du stock SIM'; $error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete'){
 $id=(int)($_POST['id']??0);
 $st=$pdo->prepare('SELECT quantity_remaining,quantity_initial FROM sim_stock_lots WHERE id=?');$st->execute([$id]);$r=$st->fetch();
 if(!$r)$error='Lot introuvable.'; elseif((int)$r['quantity_remaining']!==(int)$r['quantity_initial'])$error='Impossible de supprimer une entrée déjà utilisée.';
 else{$pdo->prepare('DELETE FROM sim_stock_lots WHERE id=?')->execute([$id]);$success='Entrée de stock supprimée.';if(google_sheets_url($pdo)!=='')sync_google_sheets($pdo);}
}
$warehouse=(int)$pdo->query('SELECT COALESCE(SUM(quantity_remaining),0) FROM sim_stock_lots')->fetchColumn();
$entered=(int)$pdo->query('SELECT COALESCE(SUM(quantity_initial),0) FROM sim_stock_lots')->fetchColumn();
$allocated=(int)$pdo->query('SELECT COALESCE(SUM(quantity_remaining),0) FROM sim_stock_affectations')->fetchColumn();
$used=max(0,$entered-$warehouse-$allocated);
$systemAvailable=$warehouse+$allocated;
$items=$pdo->query('SELECT l.*, (l.quantity_initial-l.quantity_remaining) used_qty FROM sim_stock_lots l ORDER BY l.id DESC')->fetchAll();
$aff=$pdo->query("SELECT a.*,u.full_name FROM sim_stock_affectations a JOIN users u ON u.id=a.seller_id ORDER BY a.id DESC LIMIT 100")->fetchAll();
include __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>Gestion du stock SIM</h1><p class="muted">Stock par quantité, affectation aux vendeurs et consommation automatique.</p></div><div class="actions"><a class="btn primary" href="stock_ajouter.php">＋ Ajouter des SIM</a><a class="btn secondary" href="stock_affecter.php">👤 Affecter des SIM</a></div></div>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=e($success)?></div><?php endif;?>
<div class="cards"><div class="card"><div class="muted">Stock total système</div><div class="stat"><?=$systemAvailable?></div></div><div class="card"><div class="muted">SIM en stock général</div><div class="stat"><?=$warehouse?></div></div><div class="card"><div class="muted">SIM chez les vendeurs</div><div class="stat"><?=$allocated?></div></div><div class="card"><div class="muted">SIM utilisées</div><div class="stat"><?=$used?></div></div></div>
<div class="card table-card"><h3>Historique des entrées de stock général</h3><p class="muted">Une affectation transfère la quantité du stock général vers le stock disponible du vendeur. Une souscription consomme ensuite 1 SIM du stock du vendeur.</p><table class="table"><thead><tr><th>ID</th><th>Opérateur</th><th>Date</th><th>Quantité entrée</th><th>Disponible</th><th>Affectée</th><th>Observation</th><th>Action</th></tr></thead><tbody>
<?php foreach($items as $r):?><tr><td>#<?=e((string)$r['id'])?></td><td><span class="badge"><?=e($r['operator'])?></span></td><td><?=e($r['received_date'])?></td><td><?=e((string)$r['quantity_initial'])?></td><td><strong><?=e((string)$r['quantity_remaining'])?></strong></td><td><?=e((string)$r['used_qty'])?></td><td><?=e($r['notes']?:'—')?></td><td><?php if((int)$r['used_qty']===0):?><form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette entrée ?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger small">Effacer</button></form><?php else:?>—<?php endif;?></td></tr><?php endforeach;?>
<?php if(!$items):?><tr><td colspan="8" class="empty">Aucune entrée de stock.</td></tr><?php endif;?></tbody></table></div>
<div class="card table-card" style="margin-top:20px"><div class="page-head" style="margin-bottom:12px"><div><h3 style="margin:0">Historique des affectations</h3><p class="muted">Quantités affectées à chaque vendeur.</p></div><a class="btn primary" href="stock_affecter.php">＋ Nouvelle affectation</a></div><table class="table"><thead><tr><th>Date</th><th>Vendeur</th><th>Opérateur</th><th>Quantité affectée</th><th>Encore disponible chez vendeur</th><th>Utilisée</th></tr></thead><tbody>
<?php foreach($aff as $r):$u=(int)$r['quantity_initial']-(int)$r['quantity_remaining'];?><tr><td><?=e($r['assigned_date'])?></td><td><?=e($r['full_name'])?></td><td><span class="badge"><?=e($r['operator'])?></span></td><td><?=e((string)$r['quantity_initial'])?></td><td><strong><?=e((string)$r['quantity_remaining'])?></strong></td><td><?=e((string)$u)?></td></tr><?php endforeach;?><?php if(!$aff):?><tr><td colspan="6" class="empty">Aucune affectation.</td></tr><?php endif;?></tbody></table></div>
<?php include __DIR__.'/includes/footer.php';?>
