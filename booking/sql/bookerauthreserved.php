<?php
	$bookerauthreserved = Db::getInstance()->execute(
		"CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booker_auth_reserved`(
		`id_reserved` int(10) unsigned NOT NULL AUTO_INCREMENT,		
		`id_booker` int(10) unsigned NOT NULL,		
		`date_reserved` DATE NOT NULL,
		`date_to` DATE NULL,
		`hour_from` tinyint(4) unsigned NOT NULL,
		`hour_to` tinyint(4) unsigned NOT NULL,
		`status` tinyint(4) unsigned NOT NULL DEFAULT '0',
		`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
		`date_add` DATETIME NOT NULL,
		`date_upd` DATETIME NOT NULL,
		PRIMARY KEY (`id_reserved`),
		INDEX `idx_booker` (`id_booker`),
		INDEX `idx_date_reserved` (`date_reserved`),
		INDEX `idx_status` (`status`),
		INDEX `idx_active` (`active`)
		) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
		"
	);	
?>