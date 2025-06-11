<?php
	$bookerauth = Db::getInstance()->execute(
		"CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booker_auth_reserved`(
		`id_reserved` int(10) unsigned NOT NULL AUTO_INCREMENT,		
		`id_booker` int(10) unsigned NOT NULL,		
		`date_reserved` DATE NOT NULL,		
		`hour_from` tinyint() NOT NULL,
		`hour_to` tinyint() NOT NULL,
		`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`id_reserved`)
		) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
		ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_reserved`);
		ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_auth`);
		ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`id_booker`);
		ALTER TABLE `"._DB_PREFIX_."booker_auth_reserved` ADD INDEX(`active`);
		"
	);	
?>