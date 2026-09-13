<?php
declare(strict_types=1);
session_start();

$host = 'localhost';
$db   = 'gestion_sim';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Connexion MySQL impossible. Vérifiez que MySQL est démarré et que la base 'gestion_sim' existe.");
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin(): bool {
    return !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function normalize_sim_number(string $sim): string {
    $digits = preg_replace('/\D+/', '', $sim);
    if (strpos($digits, '261') === 0 && strlen($digits) >= 11) {
        $digits = '0' . substr($digits, 3);
    }
    return $digits;
}

function find_duplicate_sim(PDO $pdo, string $sim, int $excludeId = 0): ?array {
    $normalized = normalize_sim_number($sim);
    if ($normalized === '') return null;
    $sql = $excludeId > 0
        ? 'SELECT id, seller_id, operator, subscription_date, sim_number FROM subscriptions WHERE id<>? ORDER BY id DESC'
        : 'SELECT id, seller_id, operator, subscription_date, sim_number FROM subscriptions ORDER BY id DESC';
    $q = $pdo->prepare($sql);
    if ($excludeId > 0) $q->execute([$excludeId]); else $q->execute();
    while ($row = $q->fetch()) {
        if (normalize_sim_number((string)$row['sim_number']) === $normalized) return $row;
    }
    return null;
}

function detect_operator_from_sim(string $sim): string {
    $digits = preg_replace('/\D+/', '', $sim);
    if (strpos($digits, '261') === 0) $prefix = '0'.substr($digits, 3, 2);
    else $prefix = substr($digits, 0, 3);
    $map = [
        '034'=>'Yas','036'=>'Yas','038'=>'Yas',
        '032'=>'Orange','037'=>'Orange',
        '033'=>'Airtel','035'=>'Airtel'
    ];
    return $map[$prefix] ?? 'Autre';
}


// Seuils d'alerte du stock SIM.
define('STOCK_LOW_THRESHOLD', 10);

function get_stock_alerts(PDO $pdo): array {
    $alerts = [];
    try {
        $rows = $pdo->query("SELECT operator, COALESCE(SUM(quantity_remaining),0) qty FROM sim_stock_lots GROUP BY operator ORDER BY operator")->fetchAll();
        foreach ($rows as $r) {
            $qty=(int)$r['qty'];
            if ($qty===0) $alerts[]=['level'=>'danger','type'=>'general','operator'=>$r['operator'],'qty'=>0,'message'=>'Stock général '.$r['operator'].' épuisé.'];
            elseif ($qty<=STOCK_LOW_THRESHOLD) $alerts[]=['level'=>'warning','type'=>'general','operator'=>$r['operator'],'qty'=>$qty,'message'=>'Stock général '.$r['operator'].' faible : '.$qty.' SIM restantes.'];
        }
        // Inclure les opérateurs absents du stock général comme épuisés si des vendeurs en ont besoin.
        $ops=$pdo->query("SELECT DISTINCT operator FROM sim_stock_affectations ORDER BY operator")->fetchAll(PDO::FETCH_COLUMN);
        foreach($ops as $op){
            $found=false; foreach($rows as $r){if((string)$r['operator']===(string)$op){$found=true;break;}}
            if(!$found) $alerts[]=['level'=>'danger','type'=>'general','operator'=>$op,'qty'=>0,'message'=>'Stock général '.$op.' épuisé.'];
        }
        $sellerRows=$pdo->query("SELECT a.seller_id,u.full_name,a.operator,COALESCE(SUM(a.quantity_remaining),0) qty FROM sim_stock_affectations a JOIN users u ON u.id=a.seller_id WHERE u.role='vendeur' AND u.active=1 GROUP BY a.seller_id,u.full_name,a.operator ORDER BY u.full_name,a.operator")->fetchAll();
        foreach($sellerRows as $r){
            $qty=(int)$r['qty'];
            if($qty===0) $alerts[]=['level'=>'danger','type'=>'vendeur','seller_id'=>(int)$r['seller_id'],'seller'=>$r['full_name'],'operator'=>$r['operator'],'qty'=>0,'message'=>'Stock de '.$r['full_name'].' ('.$r['operator'].') épuisé.'];
            elseif($qty<=STOCK_LOW_THRESHOLD) $alerts[]=['level'=>'warning','type'=>'vendeur','seller_id'=>(int)$r['seller_id'],'seller'=>$r['full_name'],'operator'=>$r['operator'],'qty'=>$qty,'message'=>'Stock de '.$r['full_name'].' ('.$r['operator'].') faible : '.$qty.' SIM restantes.'];
        }
    } catch (Throwable $e) {}
    return $alerts;
}
