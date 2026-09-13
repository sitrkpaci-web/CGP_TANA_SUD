<?php
require_once __DIR__.'/config/database.php';
require_login();

$page_title='Tableau de bord';

// Construire les requêtes avec une clause WHERE toujours valide,
// aussi bien pour l'administrateur que pour un vendeur.
if (is_admin()) {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions")->fetchColumn();
    $today = (int)$pdo->query("SELECT COUNT(*) FROM subscriptions WHERE subscription_date=CURDATE()")->fetchColumn();
} else {
    $sellerId = (int)$_SESSION['user']['id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE seller_id=?");
    $stmt->execute([$sellerId]);
    $total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE seller_id=? AND subscription_date=CURDATE()");
    $stmt->execute([$sellerId]);
    $today = (int)$stmt->fetchColumn();
}

$sellers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='vendeur' AND active=1")->fetchColumn();
$stockAvailable = $stockAssigned = $sellerUsed = 0;
$myStockByOperator = [];
if (is_admin()) {
    try {
        $stockAvailable = (int)$pdo->query("SELECT COALESCE(SUM(quantity_remaining),0) FROM sim_stock_lots")->fetchColumn();
        $stockAssigned = (int)$pdo->query("SELECT COALESCE(SUM(quantity_remaining),0) FROM sim_stock_affectations")->fetchColumn();
    } catch (Throwable $e) {
        // Compatible avec une ancienne base avant migration du stock.
        $stockAvailable = 0;
        try { $stockAssigned = (int)$pdo->query("SELECT COALESCE(SUM(quantity_remaining),0) FROM sim_stock_affectations")->fetchColumn(); } catch (Throwable $e2) { $stockAssigned = 0; }
    }
} else {
    // Stock actuellement affecté au vendeur : c'est son stock disponible pour saisir des souscriptions.
    try {
        $st = $pdo->prepare("SELECT operator, COALESCE(SUM(quantity_remaining),0) AS available, COALESCE(SUM(quantity_initial),0) AS assigned FROM sim_stock_affectations WHERE seller_id=? GROUP BY operator ORDER BY operator");
        $st->execute([(int)$_SESSION['user']['id']]);
        $myStockByOperator = $st->fetchAll();
        foreach ($myStockByOperator as $row) {
            $stockAssigned += (int)$row['available'];
            $sellerUsed += max(0, (int)$row['assigned'] - (int)$row['available']);
        }
    } catch (Throwable $e) {
        $myStockByOperator = [];
    }
}

if (is_admin()) {
    $recentSql = "SELECT s.*,u.full_name FROM subscriptions s JOIN users u ON u.id=s.seller_id ORDER BY s.id DESC LIMIT 8";
    $recent = $pdo->query($recentSql)->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT s.*,u.full_name FROM subscriptions s JOIN users u ON u.id=s.seller_id WHERE s.seller_id=? ORDER BY s.id DESC LIMIT 8");
    $stmt->execute([(int)$_SESSION['user']['id']]);
    $recent = $stmt->fetchAll();
}

include __DIR__.'/includes/header.php';
$stockAlerts = is_admin() ? get_stock_alerts($pdo) : [];
?>
<div class="page-head"><div><h1>Tableau de bord</h1><p class="muted">Suivi des souscriptions SIM</p></div><a class="btn primary" href="ajouter_souscription.php">＋ Nouvelle souscription</a></div>
<div class="cards">
<div class="card"><div class="muted">Souscriptions</div><div class="stat"><?=$total?></div></div>
<div class="card"><div class="muted">Aujourd'hui</div><div class="stat"><?=$today?></div></div>
<div class="card"><div class="muted">Vendeurs actifs</div><div class="stat"><?=$sellers?></div></div>
<div class="card"><div class="muted">Votre rôle</div><div class="stat" style="font-size:20px"><?=e($_SESSION['user']['role'])?></div></div>
<?php if(is_admin()): ?>
<div class="card"><div class="muted">SIM disponibles général</div><div class="stat"><?=$stockAvailable?></div></div>
<div class="card"><div class="muted">SIM encore chez vendeurs</div><div class="stat"><?=$stockAssigned?></div></div>
<?php else: ?>
<div class="card"><div class="muted">Mes SIM disponibles</div><div class="stat"><?=$stockAssigned?></div></div>
<div class="card"><div class="muted">SIM utilisées</div><div class="stat"><?=$sellerUsed?></div></div>
<?php endif; ?>
</div>
<?php if(is_admin() && $stockAlerts): ?>
<div class="card table-card" style="margin-top:20px">
<h3>🔔 Surveillance du stock</h3>
<p class="muted">Seuil d'alerte : <?=STOCK_LOW_THRESHOLD?> SIM ou moins. À 0, le stock est considéré comme épuisé.</p>
<table class="table"><thead><tr><th>Type</th><th>Vendeur</th><th>Opérateur</th><th>Stock restant</th><th>État</th></tr></thead><tbody>
<?php foreach($stockAlerts as $a): ?>
<tr><td><?=($a['type']==='general'?'Stock général':'Stock vendeur')?></td><td><?=e($a['type']==='general'?'—':$a['seller'])?></td><td><span class="badge"><?=e($a['operator'])?></span></td><td><strong><?=e((string)$a['qty'])?></strong></td><td><?=($a['qty']===0?'<span class="badge">Épuisé</span>':'<span class="badge">Faible</span>')?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php if(!is_admin()): ?>
<div class="card table-card" style="margin-top:20px">
<h3>📦 Mon stock SIM affecté</h3>
<p class="muted">Ces SIM proviennent du stock général et sont disponibles pour vos souscriptions. Chaque souscription validée retire automatiquement 1 SIM de votre stock.</p>
<table class="table"><thead><tr><th>Opérateur</th><th>SIM affectées</th><th>SIM disponibles</th><th>SIM utilisées</th><th>État</th></tr></thead><tbody>
<?php foreach($myStockByOperator as $r): $used=max(0,(int)$r['assigned']-(int)$r['available']); ?>
<tr><td><span class="badge"><?=e($r['operator'])?></span></td><td><?=e((string)$r['assigned'])?></td><td><strong><?=e((string)$r['available'])?></strong></td><td><?=e((string)$used)?></td><td><?=((int)$r['available']>0)?'<span class="badge">Disponible</span>':'<span class="muted">Épuisé</span>'?></td></tr>
<?php endforeach; ?>
<?php if(!$myStockByOperator): ?><tr><td colspan="5" class="empty">Aucune SIM ne vous a encore été affectée par l’administrateur.</td></tr><?php endif; ?>
</tbody></table>
</div>
<?php endif; ?>
<div class="card table-card"><h3>Dernières souscriptions</h3>
<table class="table"><thead><tr><th>Date</th><th>Vendeur</th><th>N° SIM</th><th>Opérateur</th><th>Réf. dépôt</th><th>Réf. forfait</th></tr></thead><tbody>
<?php foreach($recent as $r): ?><tr><td><?=e($r['subscription_date'])?></td><td><?=e($r['full_name'])?></td><td><?=e($r['sim_number'])?></td><td><?=e($r['operator'])?></td><td><?=e($r['first_deposit_reference'])?></td><td><?=e($r['first_bundle_reference'])?></td></tr><?php endforeach; ?>
<?php if(!$recent): ?><tr><td colspan="6" class="empty">Aucune souscription enregistrée.</td></tr><?php endif; ?>
</tbody></table></div>
<?php include __DIR__.'/includes/footer.php'; ?>
