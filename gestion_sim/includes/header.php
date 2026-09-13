<?php require_login(); ?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($page_title ?? 'Gestion SIM')?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head><body>
<header class="topbar">
<div class="brand">CGP <span>TANA SUD</span></div>
<div class="userbox"><?=e($_SESSION['user']['full_name'])?> · <?=e($_SESSION['user']['role'])?> <a href="logout.php">Déconnexion</a></div>
</header>
<div class="layout">
<aside class="sidebar">
<a href="dashboard.php">📊 Tableau de bord</a>
<a href="souscriptions.php">📱 Souscriptions</a>
<?php if(is_admin()): ?><a href="profil.php">⚙️ Mon profil</a><?php endif; ?>
<?php if(is_admin()): ?><a href="vendeurs.php">👥 Vendeurs</a><a href="stock.php">📦 Stock SIM</a><a href="google_sheets.php">📊 Google Sheets</a><?php endif; ?>
<a href="ajouter_souscription.php" class="side-cta">＋ Nouvelle souscription</a>
</aside>
<main class="content">
<?php if(is_admin()): $stockAlerts=get_stock_alerts($pdo); ?>
<?php if($stockAlerts): ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid #f59e0b">
<h3 style="margin-top:0">🔔 Alertes stock</h3>
<?php foreach($stockAlerts as $a): ?>
<div class="alert <?=e($a['level'])?>" style="margin:8px 0">⚠️ <?=e($a['message'])?></div>
<?php endforeach; ?>
</div>
<?php endif; endif; ?>
