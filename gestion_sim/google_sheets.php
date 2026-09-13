<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/google_sheets.php';
require_login(); if(!is_admin()){header('Location: dashboard.php');exit;}
$page_title='Google Sheets'; $error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'';
  if($action==='save'){
    $url=trim($_POST['webapp_url']??'');
    if($url!=='' && !filter_var($url,FILTER_VALIDATE_URL)) $error='Veuillez saisir une URL valide de Google Apps Script.';
    else { set_setting($pdo,'google_sheets_webapp_url',$url); $success='Configuration Google Sheets enregistrée.'; }
  } elseif($action==='sync') { $r=sync_google_sheets($pdo); if ($r['ok']) { $success=$r['message']; } else { $error=$r['message']; } }
}
$url=google_sheets_url($pdo); $last=get_setting($pdo,'google_sheets_last_sync');
include __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>Google Sheets</h1><p class="muted">Synchronisez les vendeurs, souscriptions et stock SIM vers Google Sheets.</p></div></div>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?><?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>
<div class="form-card">
<h2>Connexion</h2>
<p class="muted">Collez ici l’URL de votre déploiement Google Apps Script. Le site envoie les données sans exposer votre mot de passe Google.</p>
<form method="post"><input type="hidden" name="action" value="save"><label>URL Web App Google Apps Script<input type="url" name="webapp_url" value="<?=e($url)?>" placeholder="https://script.google.com/macros/s/.../exec"></label><div class="actions"><button class="btn primary">Enregistrer</button></div></form>
</div>
<div class="form-card"><h2>Synchronisation</h2><p>Dernière synchronisation : <strong><?=e($last ?: 'Jamais')?></strong></p><p class="muted">La synchronisation remplace le contenu des trois feuilles par l’état actuel du site : <strong>Vendeurs</strong>, <strong>Souscriptions</strong> et <strong>Stock SIM</strong>.</p><form method="post"><input type="hidden" name="action" value="sync"><button class="btn primary" <?=!$url?'disabled':''?>>🔄 Synchroniser maintenant</button></form></div>
<div class="form-card"><h2>Configuration Google Apps Script</h2><p class="muted">Dans Google Sheets : Extensions → Apps Script, collez le script fourni dans <code>google_apps_script.gs</code>, déployez-le comme application Web avec accès « Toute personne », puis copiez l’URL /exec ci-dessus.</p></div>
<?php include __DIR__.'/includes/footer.php'; ?>
