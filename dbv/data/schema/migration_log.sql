CREATE TABLE `migration_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `source` varchar(150) NOT NULL,
  `last_index` int(100) NOT NULL DEFAULT '0',
  `count` int(50) NOT NULL DEFAULT '0',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1