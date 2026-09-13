<?php
require_once __DIR__.'/config/database.php';
$page_title='Souscriptions';
$q=trim($_GET['q']??'');
$date=trim($_GET['date']??'');
$sql="SELECT s.*,u.full_name FROM subscriptions s JOIN users u ON u.id=s.seller_id WHERE 1";
$params=[];
if(!is_admin()){ $sql.=" AND s.seller_id=?"; $params[]=$_SESSION['user']['id']; }
if($date!==''){ $sql.=' AND s.subscription_date=?'; $params[]=$date; }
if($q!==''){ $sql.=" AND (s.sim_number LIKE ? OR u.full_name LIKE ? OR s.first_deposit_reference LIKE ? OR s.first_bundle_reference LIKE ? OR s.mobile_money_reference LIKE ?)"; $like="%$q%"; array_push($params,$like,$like,$like,$like,$like); }
$sql.=" ORDER BY s.id DESC";
$stmt=$pdo->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll();
include __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>Souscriptions</h1><p class="muted"><?=count($rows)?> résultat(s)</p></div><a class="btn primary" href="ajouter_souscription.php">＋ Ajouter</a></div>
<form class="toolbar" method="get"><input name="q" value="<?=e($q)?>" placeholder="Rechercher numéro, vendeur, référence..."><input type="date" name="date" value="<?=e($date)?>"><button class="btn secondary">Rechercher</button><?php if($q || $date): ?><a class="btn secondary" href="souscriptions.php">Réinitialiser</a><?php endif; ?><a class="btn primary" href="export_excel.php?q=<?=urlencode($q)?>&date=<?=urlencode($date)?>">⬇ Exporter Excel</a></form>
<div class="card table-card"><table class="table"><thead><tr><th>Date</th><th>Vendeur</th><th>SIM</th><th>Opérateur</th><th>1er dépôt</th><th>1er achat offre</th><th>Mobile Money</th><th>Notes</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=e($r['subscription_date'])?></td><td><?=e($r['full_name'])?></td><td><b><?=e($r['sim_number'])?></b></td><td><span class="badge"><?=e($r['operator'])?></span></td><td><?=e($r['first_deposit_reference'])?></td><td><?=e($r['first_bundle_reference'])?></td><td><?=e($r['mobile_money_reference'] ?? '')?></td><td><?=e($r['notes'])?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="8" class="empty">Aucun résultat.</td></tr><?php endif; ?>
</tbody></table></div>
<?php include __DIR__.'/includes/footer.php'; ?>
