CREATE TABLE `drug_unit` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `Name` varchar(20) NOT NULL,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  CONSTRAINT `drug_unit_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1