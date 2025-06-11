<?php
$bookerauth = Db::getInstance()->execute(
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
    PRIMARY KEY (`id_reserved`)
    ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
    
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_reserved`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_booker`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`date_reserved`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`status`);
    ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`active`);
    "
);

// Migration des données existantes si la table existe déjà
try {
    // Vérifier si les nouvelles colonnes existent déjà
    $check_date_to = Db::getInstance()->executeS("SHOW COLUMNS FROM `"._DB_PREFIX_."booker_auth_reserved` LIKE 'date_to'");
    if (empty($check_date_to)) {
        Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD COLUMN `date_to` DATE NULL AFTER `date_reserved`");
    }
    
    $check_status = Db::getInstance()->executeS("SHOW COLUMNS FROM `"._DB_PREFIX_."booker_auth_reserved` LIKE 'status'");
    if (empty($check_status)) {
        Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD COLUMN `status` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `hour_to`");
    }
    
    $check_date_add = Db::getInstance()->executeS("SHOW COLUMNS FROM `"._DB_PREFIX_."booker_auth_reserved` LIKE 'date_add'");
    if (empty($check_date_add)) {
        Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD COLUMN `date_add` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `active`");
        // Mettre à jour les enregistrements existants
        Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."booker_auth_reserved` SET `date_add` = NOW() WHERE `date_add` = '0000-00-00 00:00:00'");
    }
    
    $check_date_upd = Db::getInstance()->executeS("SHOW COLUMNS FROM `"._DB_PREFIX_."booker_auth_reserved` LIKE 'date_upd'");
    if (empty($check_date_upd)) {
        Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD COLUMN `date_upd` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `date_add`");
        // Mettre à jour les enregistrements existants
        Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."booker_auth_reserved` SET `date_upd` = NOW() WHERE `date_upd` = '0000-00-00 00:00:00'");
    }
    
    // Ajouter les index manquants
    Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX IF NOT EXISTS `idx_date_reserved` (`date_reserved`)");
    Db::getInstance()->execute("ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX IF NOT EXISTS `idx_status` (`status`)");
    
} catch (Exception $e) {
    // Log l'erreur mais ne pas faire échouer l'installation
    PrestaShopLogger::addLog('Erreur lors de la migration de la table booker_auth_reserved: ' . $e->getMessage());
}
?>