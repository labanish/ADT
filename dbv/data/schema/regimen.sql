CREATE TABLE `regimen` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `regimen_code` varchar(20) NOT NULL,
  `regimen_desc` text NOT NULL,
  `category` varchar(30) NOT NULL,
  `line` varchar(4) NOT NULL,
  `type_of_service` varchar(20) NOT NULL,
  `remarks` varchar(30) NOT NULL,
  `enabled` varchar(4) NOT NULL DEFAULT '1',
  `source` varchar(10) NOT NULL DEFAULT '0',
  `optimality` varchar(10) NOT NULL DEFAULT '1',
  `Merged_To` varchar(50) NOT NULL,
  `map` int(11) NOT NULL,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1