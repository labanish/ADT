CREATE TABLE `cdrr_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `created` datetime NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `cdrr_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8