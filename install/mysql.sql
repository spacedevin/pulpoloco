CREATE TABLE `link` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`url` varchar(255) DEFAULT NULL,
	`hits` int(11) NOT NULL DEFAULT '0',
	`permalink` varchar(255) NULL DEFAULT NULL,
	`date` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `permalink` (`permalink`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
