<?php
$bookerauthreserved = Db::getInstance()->execute(
    "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booker_auth_reserved`(
    `id_reserved` int(10) unsigned NOT NULL AUTO_INCREMENT,		
    `id_booker` int(10) unsigned NOT NULL,		
    `date_reserved` DATE NOT NULL,
    `date_to` DATE NULL,
    `hour_from` tinyint() NOT NULL,
    `hour_to` tinyint() NOT NULL,
    `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
    `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    
    -- Nouvelles colonnes pour le système complet
    `booking_reference` VARCHAR(20) NULL,
    `customer_firstname` VARCHAR(100) NULL,
    `customer_lastname` VARCHAR(100) NULL,
    `customer_email` VARCHAR(150) NULL,
    `customer_phone` VARCHAR(20) NULL,
    `customer_message` TEXT NULL,
    `total_price` DECIMAL(10,2) NULL DEFAULT 0.00,
    `deposit_amount` DECIMAL(10,2) NULL DEFAULT 0.00,
    `id_order` INT(10) NULL,
    `payment_status` TINYINT(1) DEFAULT 0,
    `cancellation_reason` TEXT NULL,
    
    PRIMARY KEY (`id_reserved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
    
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_reserved`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_booker`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`date_reserved`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`status`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`active`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`booking_reference`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`customer_email`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_order`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`payment_status`);
    "
);

// Créer la table pour les sessions Stripe
$stripe_sessions = Db::getInstance()->execute(
    "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booking_stripe_sessions`(
    `id_session` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_reservation` int(10) unsigned NOT NULL,
    `session_id` VARCHAR(255) NOT NULL,
    `payment_intent_id` VARCHAR(255) NULL,
    `status` VARCHAR(50) DEFAULT 'pending',
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    
    PRIMARY KEY (`id_session`)
    ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
    
    ALTER TABLE `"._DB_PREFIX_."booking_stripe_sessions` ADD INDEX(`id_reservation`);
    ALTER TABLE `"._DB_PREFIX_."booking_stripe_sessions` ADD INDEX(`session_id`);
    ALTER TABLE `"._DB_PREFIX_."booking_stripe_sessions` ADD INDEX(`payment_intent_id`);
    ALTER TABLE `"._DB_PREFIX_."booking_stripe_sessions` ADD INDEX(`status`);
    "
);

// Mise à jour de la table booker avec nouvelles colonnes
$booker_update = Db::getInstance()->execute(
    "ALTER TABLE `"._DB_PREFIX_."booker` 
     ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT 50.00 AFTER `google_account`,
     ADD COLUMN IF NOT EXISTS `deposit_required` TINYINT(1) DEFAULT 1 AFTER `price`,
     ADD COLUMN IF NOT EXISTS `auto_confirm` TINYINT(1) DEFAULT 0 AFTER `deposit_required`,
     ADD COLUMN IF NOT EXISTS `booking_duration` INT(3) DEFAULT 60 AFTER `auto_confirm`,
     ADD COLUMN IF NOT EXISTS `max_advance_days` INT(3) DEFAULT 30 AFTER `booking_duration`,
     ADD COLUMN IF NOT EXISTS `min_advance_hours` INT(2) DEFAULT 24 AFTER `max_advance_days`"
);

// Migration des données existantes si nécessaire
try {
    // Ajouter les références de réservation manquantes
    $reservations_without_ref = Db::getInstance()->executeS('
        SELECT id_reserved FROM `'._DB_PREFIX_.'booker_auth_reserved` 
        WHERE booking_reference IS NULL OR booking_reference = ""
    ');
    
    foreach ($reservations_without_ref as $reservation) {
        $reference = 'BK' . date('Y') . str_pad($reservation['id_reserved'], 5, '0', STR_PAD_LEFT);
        Db::getInstance()->update(
            'booker_auth_reserved',
            ['booking_reference' => pSQL($reference)],
            'id_reserved = ' . (int)$reservation['id_reserved']
        );
    }
    
    // Mettre à jour les prix par défaut
    Db::getInstance()->execute('
        UPDATE `'._DB_PREFIX_.'booker_auth_reserved` 
        SET total_price = 50.00 
        WHERE total_price IS NULL OR total_price = 0
    ');
    
} catch (Exception $e) {
    PrestaShopLogger::addLog('Erreur migration données réservation: ' . $e->getMessage());
}

// Créer la table des logs d'activité (optionnel)
$activity_logs = Db::getInstance()->execute(
    "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booking_activity_log`(
    `id_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_reservation` int(10) unsigned NULL,
    `id_booker` int(10) unsigned NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `id_employee` int(10) unsigned NULL,
    `date_add` DATETIME NOT NULL,
    
    PRIMARY KEY (`id_log`)
    ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
    
    ALTER TABLE `"._DB_PREFIX_."booking_activity_log` ADD INDEX(`id_reservation`);
    ALTER TABLE `"._DB_PREFIX_."booking_activity_log` ADD INDEX(`id_booker`);
    ALTER TABLE `"._DB_PREFIX_."booking_activity_log` ADD INDEX(`action`);
    ALTER TABLE `"._DB_PREFIX_."booking_activity_log` ADD INDEX(`id_employee`);
    ALTER TABLE `"._DB_PREFIX_."booking_activity_log` ADD INDEX(`date_add`);
    "
);
?>