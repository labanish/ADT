CREATE TABLE `regimen_change_purpose` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `active` varchar(2) NOT NULL DEFAULT '1',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1