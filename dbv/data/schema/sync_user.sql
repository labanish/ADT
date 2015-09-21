CREATE TABLE `sync_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` char(60) NOT NULL,
  `email` varchar(128) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'user',
  `status` char(1) NOT NULL DEFAULT 'A',
  `profile_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8