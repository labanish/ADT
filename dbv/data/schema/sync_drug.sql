CREATE TABLE `sync_drug` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) DEFAULT NULL,
  `strength` varchar(255) NOT NULL,
  `packsize` int(7) DEFAULT NULL,
  `formulation` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `weight` int(4) DEFAULT '999',
  `category_id` int(11) unsigned DEFAULT NULL,
  `regimen_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8