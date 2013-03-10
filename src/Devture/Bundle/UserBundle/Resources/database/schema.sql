CREATE TABLE `usrrrer` (
 `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `username` varchar(255) NOT NULL,
 `email` varchar(255) NULL,
 `password` varchar(255) NOT NULL,
 `name` varchar(255) NOT NULL,
 `roles` text NOT NULL COMMENT 'JSON',
 PRIMARY KEY (`_id`),
 UNIQUE KEY `username` (`username`),
 UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
