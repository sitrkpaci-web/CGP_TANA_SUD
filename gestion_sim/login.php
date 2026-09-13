<?php
require_once __DIR__.'/config/database.php';

if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        header('Location: dashboard.php'); exit;
    }
    $error = 'Identifiant ou mot de passe incorrect.';
}
?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion - Gestion SIM</title><link rel="stylesheet" href="assets/css/style.css">
</head><body class="login-page">
<div class="login-card">
<h1>CGP TANA SUD</h1><p class="muted">Connexion à votre espace</p>
<?php if($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
<form method="post">
<label>Identifiant<input name="username" required autofocus></label>
<label>Mot de passe<input type="password" name="password" required></label>
<button class="btn primary full">Se connecter</button>
</form>
<div class="hint">Compte initial : <b>admin</b> / <b>admin123</b></div>
</div></body></html>
