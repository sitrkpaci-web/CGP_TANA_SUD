<?php
require_once __DIR__.'/config/database.php';
if(!is_admin()){header('Location: dashboard.php');exit;}
$page_title='Vendeurs'; $error=''; $success='';
if(isset($_GET['deleted'])) $success='Vendeur effacé avec succès.';
if(isset($_GET['toggle'])){$id=(int)$_GET['toggle'];$pdo->prepare("UPDATE users SET active=1-active WHERE id=? AND role='vendeur'")->execute([$id]);header('Location: vendeurs.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 $name=trim($_POST['full_name']??'');$username=trim($_POST['username']??'');$phone=trim($_POST['seller_phone']??'');$pass=$_POST['password']??'';
 if($name===''||$username===''||$pass==='')$error='Nom, identifiant et mot de passe sont obligatoires.';
 else {try{$st=$pdo->prepare("INSERT INTO users(username,password_hash,full_name,role,seller_phone) VALUES(?,?,?,?,?)");$st->execute([$username,password_hash($pass,PASSWORD_DEFAULT),$name,'vendeur',$phone]);$success='Vendeur créé.';}catch(PDOException $e){$error='Cet identifiant existe déjà.';}}
}
$rows=$pdo->query("SELECT u.*,COUNT(s.id) subscriptions_count FROM users u LEFT JOIN subscriptions s ON s.seller_id=u.id WHERE u.role='vendeur' GROUP BY u.id ORDER BY u.full_name")->fetchAll();
include __DIR__.'/includes/header.php';
?>
<div class="page-head"><div><h1>Gestion des vendeurs</h1><p class="muted">Créer et suivre les comptes vendeurs.</p></div></div>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?><?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>
<div class="form-card" style="margin-bottom:20px"><h3>Ajouter un vendeur</h3><form method="post"><div class="grid2">
<label>Nom complet *<input name="full_name" required></label>
<label>Téléphone vendeur<input name="seller_phone" placeholder="03xxxxxxxx"></label>
<label>Identifiant *<input name="username" required></label>
<label>Mot de passe *<input type="password" name="password" required></label>
</div><button class="btn primary">Créer le vendeur</button></form></div>
<div class="card table-card"><table class="table"><thead><tr><th>Vendeur</th><th>Téléphone</th><th>Identifiant</th><th>Souscriptions</th><th>Statut</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=e($r['full_name'])?></td><td><?=e($r['seller_phone'])?></td><td><?=e($r['username'])?></td><td><?=e((string)$r['subscriptions_count'])?></td><td><?= $r['active']?'<span class="badge">Actif</span>':'<span class="badge" style="background:#eee;color:#777">Inactif</span>' ?></td><td><a class="btn secondary" href="fiche_vendeur.php?id=<?=$r['id']?>">Voir la fiche</a> <a class="btn secondary" href="?toggle=<?=$r['id']?>" data-confirm="Changer le statut de ce vendeur ?"><?= $r['active']?'Désactiver':'Activer' ?></a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__.'/includes/footer.php'; ?>
