<?php
	$booker = Db::getInstance()->execute(
		"CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booker`(
		`id_booker` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`name` VARCHAR (255),
		`active` tinyint(1) unsigned NOT NULL DEFAULT '1',		
		`google_account` VARCHAR (255),
		PRIMARY KEY (`id_booker`)
		) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
		ALTER TABLE `"._DB_PREFIX_."booker` ADD INDEX(`id_booker`);
		ALTER TABLE `"._DB_PREFIX_."booker` ADD INDEX(`active`);
		"
	);
	$booker_lang = Db::getInstance()->execute(
		"CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booker_lang`(
		`id_booker` int(10) unsigned NOT NULL,
		`id_lang` tinyint(1) unsigned NOT NULL DEFAULT '1',		
		`description` VARCHAR (255),
		PRIMARY KEY (`id_booker`)
		) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
		ALTER TABLE `"._DB_PREFIX_."booker_lang` ADD INDEX(`id_booker`);
		"
	);	
?>