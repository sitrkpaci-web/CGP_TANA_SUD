<?php
require_once __DIR__.'/config/database.php'; require_once __DIR__.'/config/google_sheets.php'; require_login();
$page_title='Nouvelle souscription';$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $seller_id=is_admin()?(int)($_POST['seller_id']??0):(int)$_SESSION['user']['id'];
 $sim=normalize_sim_number((string)($_POST['sim_number']??''));$date=date('Y-m-d');$deposit=trim($_POST['first_deposit_reference']??'');$bundle=trim($_POST['first_bundle_reference']??'');$mobile=trim($_POST['mobile_money_reference']??'');$notes=trim($_POST['notes']??'');
 if(!$seller_id||$sim==='')$error='Veuillez remplir les champs obligatoires.';
 else{
  $operator=detect_operator_from_sim($sim);
  if($operator==='Autre')$error='Opérateur impossible à détecter. Numéros acceptés : Yas 034/036/038, Orange 032/037, Airtel 033/035.';
  else{
   $duplicate=find_duplicate_sim($pdo,$sim);
   if($duplicate){$error='⚠️ Attention : ce numéro SIM est déjà souscrit dans le système. Veuillez vérifier le numéro avant de continuer. La souscription n’a pas été enregistrée.';}
   else try{
    $pdo->beginTransaction();
    $st=$pdo->prepare('SELECT id,quantity_remaining,operator FROM sim_stock_affectations WHERE seller_id=? AND operator=? AND quantity_remaining>0 ORDER BY assigned_date ASC,id ASC LIMIT 1 FOR UPDATE');$st->execute([$seller_id,$operator]);$aff=$st->fetch();
    if(!$aff)throw new RuntimeException('NO_ALLOCATION');
    $stmt=$pdo->prepare('INSERT INTO subscriptions(seller_id,stock_allocation_id,sim_number,operator,subscription_date,first_deposit_reference,first_bundle_reference,mobile_money_reference,notes) VALUES(?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$seller_id,$aff['id'],$sim,$operator,$date,$deposit,$bundle,$mobile,$notes]);
    $upd=$pdo->prepare('UPDATE sim_stock_affectations SET quantity_remaining=quantity_remaining-1 WHERE id=? AND quantity_remaining>0');$upd->execute([(int)$aff['id']]);
    if($upd->rowCount()!==1)throw new RuntimeException('STOCK_UPDATE');
    $pdo->commit();$success='Souscription enregistrée. Opérateur détecté : '.$operator.'. 1 SIM a été déduite de l’affectation du vendeur.';if(google_sheets_url($pdo)!=='')sync_google_sheets($pdo);
   }catch(RuntimeException $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage()==='NO_ALLOCATION'?'Ce vendeur n’a pas de SIM '.$operator.' affectée disponible. Demandez à l’administrateur d’affecter du stock.':'Erreur lors de l’enregistrement de la souscription.';}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Erreur lors de l’enregistrement de la souscription.';}
  }
 }
}
$sellers=$pdo->query("SELECT id,full_name,seller_phone FROM users WHERE role='vendeur' AND active=1 ORDER BY full_name")->fetchAll();
include __DIR__.'/includes/header.php';?>
<div class="page-head"><div><h1>Nouvelle souscription SIM</h1><p class="muted">Le vendeur saisit uniquement le numéro et les références. L’opérateur et la date sont automatiques.</p></div></div>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="alert success"><?=e($success)?></div><?php endif;?>
<div class="form-card"><form method="post">
<?php if(is_admin()):?><label>Vendeur *<select name="seller_id" required><option value="">Choisir un vendeur</option><?php foreach($sellers as $s):?><option value="<?=$s['id']?>"><?=e($s['full_name'])?><?= $s['seller_phone']?' — '.e($s['seller_phone']):''?></option><?php endforeach;?></select></label><?php endif;?>
<div class="grid2"><label>Numéro souscrit *<input name="sim_number" required placeholder="034xxxxxx / 032xxxxxx / 033xxxxxx"></label><label>Référence crédit 1er dépôt<input name="first_deposit_reference"></label><label>Référence 1er achat offre<input name="first_bundle_reference"></label><label>Référence Mobile Money<input name="mobile_money_reference"></label></div>
<div class="alert info">📡 Opérateur détecté automatiquement : <b>Yas</b> 034/036/038 · <b>Orange</b> 032/037 · <b>Airtel</b> 033/035.<br>📅 Date de souscription : automatique.</div>
<?php if(is_admin()):?><label>Observation<textarea name="notes"></textarea></label><?php endif;?><div class="actions"><button class="btn primary">Enregistrer</button><a class="btn secondary" href="souscriptions.php">Annuler</a></div>
</form></div><?php include __DIR__.'/includes/footer.php';?>
