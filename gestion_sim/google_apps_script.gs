/** CGP TANA SUD - Google Sheets
 * Ce script crée/actualise automatiquement les feuilles :
 * Dashboard, Stock général, Stock vendeurs, Souscriptions, Vendeurs.
 * Déploiement : Déployer > Nouveau déploiement > Application Web
 * Exécuter en tant que : Moi / Accès : Toute personne.
 */
function doPost(e) {
  try {
    var data = JSON.parse((e.postData && e.postData.contents) || '{}');
    if (data.action !== 'replace_all') throw new Error('Action inconnue');
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var dashboard = data.dashboard || {};
    writeSheet_(ss, 'Dashboard',
      ['Indicateur','Valeur'],
      [
        ['Dernière synchronisation', data.generated_at || ''],
        ['SIM disponibles stock général', dashboard.general_available || 0],
        ['SIM affectées aux vendeurs', dashboard.seller_available || 0],
        ['SIM utilisées / souscriptions', dashboard.used || 0],
        ['SIM totales suivies', dashboard.total || 0],
        ['Alertes stock', dashboard.alert_count || 0]
      ]);

    writeSheet_(ss, 'Stock général',
      ['ID','Opérateur','Date entrée','Quantité entrée','Quantité disponible','Quantité utilisée','Observation'],
      data.stock || []);
    writeSheet_(ss, 'Stock vendeurs',
      ['Vendeur','Téléphone','Opérateur','Quantité affectée','Quantité disponible','Quantité utilisée','Date affectation','Observation'],
      data.seller_stock || []);
    writeSheet_(ss, 'Souscriptions',
      ['ID','Date','Créé le','Vendeur','Téléphone vendeur','Numéro SIM','Opérateur','Réf. crédit 1er dépôt','Réf. 1er achat offre','Réf. Mobile Money','Observation'],
      data.subscriptions || []);
    writeSheet_(ss, 'Vendeurs',
      ['ID','Identifiant','Nom vendeur','Téléphone','Actif','Créé le'],
      data.vendors || []);

    formatWorkbook_(ss);
    return json_({ok:true, generated_at:new Date().toISOString()});
  } catch(err) { return json_({ok:false,error:String(err)}); }
}

function writeSheet_(ss, name, headers, rows) {
  var sh = ss.getSheetByName(name) || ss.insertSheet(name);
  sh.clearContents();
  var values = [headers];
  rows.forEach(function(r) {
    if (Array.isArray(r)) values.push(r);
    else values.push(headers.map(function(_, i) { return r[i] == null ? '' : r[i]; }));
  });
  sh.getRange(1,1,values.length,headers.length).setValues(values);
  sh.setFrozenRows(1);
  if (sh.getFilter()) sh.getFilter().remove();
  sh.getRange(1,1,values.length,headers.length).createFilter();
  sh.autoResizeColumns(1,headers.length);
}

function formatWorkbook_(ss) {
  ['Dashboard','Stock général','Stock vendeurs','Souscriptions','Vendeurs'].forEach(function(name) {
    var sh=ss.getSheetByName(name); if(!sh) return;
    var lastCol=sh.getLastColumn(), lastRow=sh.getLastRow();
    if(lastCol>0) sh.getRange(1,1,1,lastCol).setFontWeight('bold');
    if(lastRow>1) sh.getRange(1,1,lastRow,lastCol).setVerticalAlignment('middle');
  });
  var dash=ss.getSheetByName('Dashboard');
  if(dash) dash.getRange('A1:B1').setFontWeight('bold');
}
function json_(obj){ return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON); }
