USE gestion_sim;

-- Anti-doublon : le code PHP normalise les numéros avant enregistrement
-- (034..., +26134..., espaces et tirets sont traités comme le même numéro).
-- Avant d'ajouter une contrainte UNIQUE en production, vérifier qu'aucun doublon
-- historique n'existe :
-- SELECT sim_number, COUNT(*) total FROM subscriptions GROUP BY sim_number HAVING COUNT(*) > 1;

-- Une contrainte UNIQUE peut être ajoutée après nettoyage des doublons historiques :
-- ALTER TABLE subscriptions ADD UNIQUE KEY uq_subscriptions_sim_number (sim_number);
