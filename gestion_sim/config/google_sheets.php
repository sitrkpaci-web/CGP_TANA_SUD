<?php
// Synchronisation Google Sheets via Google Apps Script Web App.
// L'URL est enregistrée dans la table app_settings depuis google_sheets.php.
function get_setting(PDO $pdo, string $key, string $default = ''): string {
    try {
        $st = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key=? LIMIT 1');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        return $v === false ? $default : (string)$v;
    } catch (Throwable $e) { return $default; }
}
function set_setting(PDO $pdo, string $key, string $value): void {
    $st = $pdo->prepare('INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    $st->execute([$key,$value]);
}
function google_sheets_url(PDO $pdo): string { return get_setting($pdo, 'google_sheets_webapp_url'); }
function sync_google_sheets(PDO $pdo): array {
    $url = google_sheets_url($pdo);
    if ($url === '') return ['ok'=>false,'message'=>'Google Sheets n’est pas configuré.'];
    if (!filter_var($url, FILTER_VALIDATE_URL)) return ['ok'=>false,'message'=>'URL Google Apps Script invalide.'];
    $vendors = $pdo->query("SELECT id,username,full_name,seller_phone,active,created_at FROM users WHERE role='vendeur' ORDER BY full_name")->fetchAll();
    $subs = $pdo->query("SELECT s.id,s.subscription_date,s.created_at,u.full_name seller_name,u.seller_phone,s.sim_number,s.operator,s.first_deposit_reference,s.first_bundle_reference,s.mobile_money_reference,s.notes FROM subscriptions s JOIN users u ON u.id=s.seller_id ORDER BY s.id")->fetchAll();
    $stock = $pdo->query("SELECT id,operator,received_date,quantity_initial,quantity_remaining,notes FROM sim_stock_lots ORDER BY id")->fetchAll();
    foreach($stock as &$sr){ $sr['quantity_allocated']=(int)$pdo->query('SELECT COALESCE(SUM(quantity_initial-quantity_remaining),0) FROM sim_stock_affectations WHERE operator='.$pdo->quote($sr['operator']))->fetchColumn(); $sr['quantity_used']=max(0,(int)$sr['quantity_initial']-(int)$sr['quantity_remaining']); } unset($sr);
    $payload = json_encode(['action'=>'replace_all','generated_at'=>date('c'),'vendors'=>$vendors,'subscriptions'=>$subs,'stock'=>$stock], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json; charset=utf-8'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>5]);
    $body=curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if($err) return ['ok'=>false,'message'=>'Échec de connexion à Google Sheets : '.$err];
    $resp=json_decode((string)$body,true);
    if($code<200||$code>=300||!is_array($resp)||empty($resp['ok'])) {
        $detail = is_array($resp) && !empty($resp['error']) ? (string)$resp['error'] : substr(strip_tags((string)$body), 0, 300);
        return ['ok'=>false,'message'=>'Google Sheets a refusé la synchronisation (HTTP '.$code.'). Détail : '.$detail];
    }
    try { set_setting($pdo,'google_sheets_last_sync',date('Y-m-d H:i:s')); } catch(Throwable $e) {}
    return ['ok'=>true,'message'=>'Synchronisation terminée : '.count($subs).' souscriptions, '.array_sum(array_map(fn($r)=>(int)$r['quantity_remaining'],$stock)).' SIM disponibles en stock, '.count($vendors).' vendeurs.'];
}
