GESTION SIM - Installation XAMPP

1. Installer XAMPP et démarrer Apache + MySQL.
2. Copier le dossier "gestion_sim" dans :
   C:\xampp\htdocs\
3. Ouvrir phpMyAdmin :
   http://localhost/phpmyadmin/
4. Importer le fichier :
   database/gestion_sim.sql
5. Ouvrir :
   http://localhost/gestion_sim/login.php

Compte administrateur initial :
Identifiant : admin
Mot de passe : admin123

IMPORTANT :
Changez ce mot de passe après installation dans une version de production.
La connexion MySQL est configurée pour XAMPP par défaut :
serveur localhost / utilisateur root / mot de passe vide.

Fonctions :
- Tableau de bord
- Connexion administrateur
- Création des vendeurs
- Connexion vendeur
- Saisie des souscriptions
- Numéro SIM unique
- Référence première dépôt
- Référence premier achat de forfait
- Recherche et historique
- Responsive mobile

THÈME VISUEL
Le projet utilise désormais un thème noir/orange inspiré de l'image fournie, sans intégrer ni afficher le logo Orange TM.

MISE À JOUR
- Mot de passe admin corrigé : admin123 fonctionne après import de la nouvelle base SQL.
- Page « Mon profil » ajoutée pour modifier nom, téléphone et mot de passe.
- Export Excel ajouté depuis la page Souscriptions. Le fichier .xls est directement ouvrable dans Microsoft Excel et LibreOffice.
- Filtre par date ajouté.

SÉCURITÉ - PROFIL ADMINISTRATEUR
- La page « Mon profil » est accessible uniquement au compte administrateur.
- L'administrateur peut modifier son nom et son téléphone.
- Pour changer le mot de passe, il doit saisir son mot de passe actuel.
- Le nouveau mot de passe doit contenir au moins 6 caractères et être confirmé.
- Le nouveau mot de passe est enregistré avec password_hash() (bcrypt/PASSWORD_DEFAULT).

CORRECTION VENDEUR - IMPORTANT
La version corrigée règle une erreur SQL du tableau de bord qui provoquait une erreur fatale lors de la connexion d'un compte vendeur. Le compteur "Aujourd'hui" utilise désormais une requête valide pour les vendeurs.

Les comptes vendeurs créés par l'administrateur peuvent maintenant se connecter normalement et accéder à leur propre tableau de bord et à leurs propres souscriptions.

MODULE FICHE VENDEUR
- Dans Vendeurs, cliquer sur « Voir la fiche » pour consulter le profil détaillé d’un vendeur.
- La fiche affiche le téléphone, l’identifiant, le statut, le total des souscriptions, les souscriptions du jour, la première SIM, la première référence de dépôt et la première référence de forfait.
- L’administrateur peut modifier le nom, le téléphone, l’identifiant et le mot de passe du vendeur.
- Le nouveau mot de passe est optionnel et doit contenir au moins 6 caractères.
- Les 50 dernières souscriptions et la répartition par opérateur sont visibles dans la fiche.

GESTION DU STOCK SIM
--------------------
- Menu admin : Stock SIM
- Ajouter plusieurs SIM au stock (une par ligne ou séparées par virgules)
- Référence de lot, opérateur, date d'entrée et observations
- Affecter des SIM disponibles à un vendeur
- Suivre les statuts : disponible, affectée, souscrite
- Lorsqu'une SIM présente dans le stock est utilisée pour une souscription, elle passe automatiquement à "souscrite"
- Une SIM affectée à un autre vendeur ne peut pas être utilisée par un vendeur différent.
- Les vendeurs n'accèdent pas au module de stock.

IMPORTANT : si la base existe déjà, exécuter dans phpMyAdmin uniquement la partie "Gestion du stock SIM" du fichier database/gestion_sim.sql (CREATE TABLE sim_stock...).


MISE À JOUR DES SOUSCRIPTIONS
----------------------------
Pour un vendeur, le formulaire demande uniquement :
- Numéro souscrit
- Référence crédit 1er dépôt
- Référence 1er achat offre
- Référence Mobile Money
La date est définie automatiquement par le serveur. Le vendeur n'a pas à saisir son nom ni la date.

BASE EXISTANTE
Si la base gestion_sim existe déjà, exécuter une seule fois dans phpMyAdmin le fichier :
database/migration_mobile_money.sql

SYNCHRONISATION GOOGLE SHEETS
-----------------------------
Le projet peut synchroniser automatiquement les données vers un classeur Google Sheets via Google Apps Script.
1. Créez un Google Sheets.
2. Extensions > Apps Script.
3. Copiez le contenu de google_apps_script.gs dans l'éditeur Apps Script.
4. Déployez > Nouveau déploiement > Application Web.
5. Exécuter en tant que : Moi. Accès : Toute personne.
6. Copiez l'URL /exec et connectez-vous comme administrateur sur le site.
7. Ouvrez « Google Sheets », collez l'URL et enregistrez.
8. Cliquez sur « Synchroniser maintenant » pour tester.

Trois feuilles sont alimentées : Vendeurs, Souscriptions et Stock SIM.
Les nouvelles souscriptions, ajouts au stock et affectations déclenchent automatiquement une synchronisation si l'URL est configurée.
Pour une base existante, exécuter database/migration_google_sheets.sql une seule fois.

CORRECTION CONNEXION (13/09/2026)
- La connexion admin/vendeur ne dépend plus de Google Sheets.
- Le tableau de bord vendeur reste accessible même si la migration du stock n'a pas encore été exécutée.
- Si la base existante est ancienne, exécuter les migrations fournies dans database/.


STOCK SIM PAR QUANTITE
- L'ajout au stock demande uniquement la quantité, l'opérateur et éventuellement une référence de lot.
- Aucun numéro SIM individuel n'est enregistré dans le stock.
- Le vendeur saisit uniquement le numéro souscrit et les références demandées.
- La date de souscription est automatiquement définie par le serveur.
- Chaque souscription consomme automatiquement 1 SIM du stock disponible.
- Pour une base existante, exécuter database/migration_stock_quantite.sql une seule fois.
