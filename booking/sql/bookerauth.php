<?php
	$bookerauth = Db::getInstance()->execute(
		"CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."booker_auth`(
		`id_auth` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`id_booker` int(10) unsigned NOT NULL,		
		`date_from` DATETIME NOT NULL,		
		`date_to` DATETIME NOT NULL,
		`active` tinyint(1) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`id_auth`)
		) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
		ALTER TABLE `"._DB_PREFIX_."booker_auth` ADD INDEX(`id_auth`);
		ALTER TABLE `"._DB_PREFIX_."booker_auth` ADD INDEX(`active`);
		"
	);	
?>