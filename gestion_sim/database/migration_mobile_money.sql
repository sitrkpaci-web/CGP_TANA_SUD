USE gestion_sim;

-- Exécuter une seule fois sur une base existante.
ALTER TABLE subscriptions
    ADD COLUMN mobile_money_reference VARCHAR(100) NULL AFTER first_bundle_reference;
