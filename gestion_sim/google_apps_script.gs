/** CGP TANA SUD - Récepteur Google Sheets
 *  Déploiement : Déployer > Nouveau déploiement > Application Web > Exécuter en tant que moi > Toute personne.
 */
function doGet(e) {
  // Simple test de vie du déploiement : ouvrir l'URL /exec dans le navigateur
  // doit afficher ce message si tout est bien déployé et autorisé.
  return json_({ok:true, message:'Le déploiement fonctionne. La synchronisation réelle se fait en POST depuis le site.'});
}
function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents || '{}');
    if (data.action !== 'replace_all') throw new Error('Action inconnue');
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    writeSheet_(ss, 'Vendeurs', ['ID','Identifiant','Nom vendeur','Téléphone','Actif','Créé le'], data.vendors || [], ['id','username','full_name','seller_phone','active','created_at']);
    writeSheet_(ss, 'Souscriptions', ['ID','Date','Créé le','Vendeur','Téléphone vendeur','Numéro SIM','Opérateur','Réf. crédit 1er dépôt','Réf. 1er achat offre','Réf. Mobile Money','Observation'], data.subscriptions || [], ['id','subscription_date','created_at','seller_name','seller_phone','sim_number','operator','first_deposit_reference','first_bundle_reference','mobile_money_reference','notes']);
    writeSheet_(ss, 'Stock SIM', ['ID','Opérateur','Date entrée','Quantité entrée','Quantité disponible','Quantité utilisée','Observation'], data.stock || [], ['id','operator','received_date','quantity_initial','quantity_remaining','quantity_used','notes']);
    return json_({ok:true, generated_at:new Date().toISOString()});
  } catch(err) { return json_({ok:false,error:String(err)}); }
}
function writeSheet_(ss,name,headers,rows,keys){
  var sh=ss.getSheetByName(name) || ss.insertSheet(name);
  sh.clearContents();
  var values=[headers];
  rows.forEach(function(r){ values.push(keys.map(function(k){ return r[k] == null ? '' : r[k]; })); });
  if(values.length) sh.getRange(1,1,values.length,headers.length).setValues(values);
  sh.setFrozenRows(1);
  if(sh.getLastColumn()>0) sh.autoResizeColumns(1,sh.getLastColumn());
}
function json_(obj){ return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON); }
