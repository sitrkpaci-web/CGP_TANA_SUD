<?php
require_once __DIR__.'/config/database.php';
require_login();

// Cette page est réservée à l'administrateur.
if (!is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$page_title='Mon profil';
$error='';
$success='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim($_POST['full_name']??'');
    $phone=trim($_POST['seller_phone']??'');
    $current=$_POST['current_password']??'';
    $new=$_POST['new_password']??'';
    $confirm=$_POST['confirm_password']??'';

    $st=$pdo->prepare('SELECT * FROM users WHERE id=? AND role=\'admin\' LIMIT 1');
    $st->execute([(int)$_SESSION['user']['id']]);
    $u=$st->fetch();

    if (!$u) {
        $error='Compte administrateur introuvable.';
    } elseif ($name==='') {
        $error='Le nom est obligatoire.';
    } elseif ($new!=='' && $current==='') {
        $error='Saisissez votre mot de passe actuel pour changer le mot de passe.';
    } elseif ($new!=='' && !password_verify($current,$u['password_hash'])) {
        $error='Le mot de passe actuel est incorrect.';
    } elseif ($new!=='' && strlen($new)<6) {
        $error='Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } elseif ($new!=='' && $new!==$confirm) {
        $error='La confirmation du nouveau mot de passe ne correspond pas.';
    } else {
        if ($new!=='') {
            $st=$pdo->prepare('UPDATE users SET full_name=?, seller_phone=?, password_hash=? WHERE id=? AND role=\'admin\'');
            $st->execute([$name,$phone,password_hash($new,PASSWORD_DEFAULT),(int)$u['id']]);
        } else {
            $st=$pdo->prepare('UPDATE users SET full_name=?, seller_phone=? WHERE id=? AND role=\'admin\'');
            $st->execute([$name,$phone,(int)$u['id']]);
        }

        $_SESSION['user']['full_name']=$name;
        $_SESSION['user']['seller_phone']=$phone;
        $success=$new!=='' ? 'Profil et mot de passe administrateur mis à jour avec succès.' : 'Profil administrateur mis à jour avec succès.';
    }
}

$st=$pdo->prepare('SELECT * FROM users WHERE id=? AND role=\'admin\' LIMIT 1');
$st->execute([(int)$_SESSION['user']['id']]);
$u=$st->fetch();

include __DIR__.'/includes/header.php';
?>
<div class="page-head">
    <div>
        <h1>Mon profil administrateur</h1>
        <p class="muted">Modifiez vos informations et votre mot de passe directement depuis le site.</p>
    </div>
</div>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>

<div class="form-card">
<form method="post" autocomplete="off">
    <div class="grid2">
        <label>Nom complet
            <input name="full_name" value="<?=e($u['full_name'])?>" required>
        </label>
        <label>Téléphone vendeur
            <input name="seller_phone" value="<?=e($u['seller_phone'])?>">
        </label>
    </div>

    <hr style="border:0;border-top:1px solid #30363d;margin:8px 0 20px">
    <h3>Changer le mot de passe administrateur</h3>
    <p class="muted">Pour des raisons de sécurité, le mot de passe actuel est obligatoire lorsque vous définissez un nouveau mot de passe.</p>

    <div class="grid2">
        <label>Mot de passe actuel
            <input type="password" name="current_password" autocomplete="current-password">
        </label>
        <label>Nouveau mot de passe
            <input type="password" name="new_password" minlength="6" autocomplete="new-password">
        </label>
        <label>Confirmer le nouveau mot de passe
            <input type="password" name="confirm_password" minlength="6" autocomplete="new-password">
        </label>
    </div>

    <div class="actions">
        <button type="submit" class="btn primary">Enregistrer les modifications</button>
        <a class="btn secondary" href="dashboard.php">Retour</a>
    </div>
</form>
</div>
<?php include __DIR__.'/includes/footer.php'; ?>
