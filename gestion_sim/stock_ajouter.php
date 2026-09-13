<?php
require_once __DIR__.'/config/database.php'; require_once __DIR__.'/config/google_sheets.php'; require_login();
if(!is_admin()){header('Location: dashboard.php');exit;}
$page_title='Ajouter au stock'; $error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $operator=trim($_POST['operator']??'Autre'); $date=date('Y-m-d'); $notes=trim($_POST['notes']??''); $quantity=(int)($_POST['quantity']??0);
 if($quantity<1) $error='Indiquez une quantité de SIM supérieure ou égale à 1.';
 elseif(!$operator) $error='Veuillez sélectionner un opérateur.';
 else {
  try{ $st=$pdo->prepare("INSERT INTO sim_stock_lots(operator,received_date,quantity_initial,quantity_remaining,notes) VALUES(?,?,?,?,?)"); $st->execute([$operator,$date,$quantity,$quantity,$notes]); if(google_sheets_url($pdo)!=='') sync_google_sheets($pdo); $success=$quantity.' SIM ajoutée(s) au stock.'; }
  catch(Throwable $e){ $error='Impossible d’enregistrer le stock. Vérifiez que la migration du stock par quantité a été exécutée dans phpMyAdmin.'; }
 }
}
include __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>Ajouter des SIM au stock</h1><p class="muted">Saisissez uniquement la quantité de SIM reçues. Les numéros individuels ne sont pas enregistrés dans le stock.</p></div></div>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?><?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>
<div class="form-card"><form method="post"><div class="grid2">
<label>Quantité de SIM *<input type="number" name="quantity" min="1" step="1" required placeholder="Ex. 100"></label>
<label>Opérateur *<select name="operator"><option>Telma</option><option>Orange</option><option>Airtel</option><option>Autre</option></select></label>
</div>
<div class="alert info">📅 La date d'entrée est définie automatiquement par le système : <?=e(date('Y-m-d'))?>.</div>
<label>Observation<textarea name="notes" placeholder="Lot, fournisseur, remarque..."></textarea></label>
<div class="actions"><button class="btn primary">Enregistrer la quantité</button><a class="btn secondary" href="stock.php">Annuler</a></div></form></div>
<?php include __DIR__.'/includes/footer.php'; ?>