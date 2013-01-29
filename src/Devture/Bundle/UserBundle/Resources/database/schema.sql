CREATE TABLE `user` (
 `_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `username` varchar(255) NOT NULL,
 `password` varchar(255) NOT NULL,
 `name` varchar(255) NOT NULL,
 `roles` text NOT NULL COMMENT 'JSON',
 PRIMARY KEY (`_id`),
 UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
