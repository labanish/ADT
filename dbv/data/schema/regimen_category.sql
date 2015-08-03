CREATE TABLE `regimen_category` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  `Active` varchar(2) NOT NULL,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1