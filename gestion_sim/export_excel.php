<?php
require_once __DIR__.'/config/database.php';
require_login();
$q=trim($_GET['q']??''); $date=trim($_GET['date']??'');
$sql="SELECT s.subscription_date,u.full_name,u.seller_phone,s.sim_number,s.operator,s.first_deposit_reference,s.first_bundle_reference,s.mobile_money_reference,s.notes FROM subscriptions s JOIN users u ON u.id=s.seller_id WHERE 1";
$params=[];
if(!is_admin()){ $sql.=' AND s.seller_id=?'; $params[]=(int)$_SESSION['user']['id']; }
if($date!==''){ $sql.=' AND s.subscription_date=?'; $params[]=$date; }
if($q!==''){ $sql.=' AND (s.sim_number LIKE ? OR u.full_name LIKE ? OR u.seller_phone LIKE ? OR s.first_deposit_reference LIKE ? OR s.first_bundle_reference LIKE ?)'; $x='%'.$q.'%'; array_push($params,$x,$x,$x,$x,$x,$x); }
$sql.=' ORDER BY s.subscription_date DESC,s.id DESC';
$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll();
function xe($v){$v=(string)($v??'');$v=str_replace(["\r\n","\r","\n"],' ',$v);return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
$filename='souscriptions_SIM_'.date('Y-m-d_H-i').'.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Cache-Control: max-age=0');
echo '<?xml version="1.0" encoding="UTF-8"?>';echo '<?mso-application progid="Excel.Sheet"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Styles><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#FF7900" ss:Pattern="Solid"/></Style></Styles><Worksheet ss:Name="Souscriptions"><Table>
<Row ss:StyleID="Header"><Cell><Data ss:Type="String">Date</Data></Cell><Cell><Data ss:Type="String">Vendeur</Data></Cell><Cell><Data ss:Type="String">Téléphone vendeur</Data></Cell><Cell><Data ss:Type="String">Numéro SIM</Data></Cell><Cell><Data ss:Type="String">Opérateur</Data></Cell><Cell><Data ss:Type="String">Référence première dépôt</Data></Cell><Cell><Data ss:Type="String">Référence premier achat offre</Data></Cell><Cell><Data ss:Type="String">Référence Mobile Money</Data></Cell><Cell><Data ss:Type="String">Observation</Data></Cell></Row>
<?php foreach($rows as $r): ?><Row><Cell><Data ss:Type="String"><?=xe($r['subscription_date'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['full_name'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['seller_phone'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['sim_number'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['operator'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['first_deposit_reference'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['first_bundle_reference'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['mobile_money_reference'])?></Data></Cell><Cell><Data ss:Type="String"><?=xe($r['notes'])?></Data></Cell></Row><?php endforeach; ?>
</Table></Worksheet></Workbook>
