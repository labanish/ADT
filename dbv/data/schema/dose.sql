CREATE TABLE `dose` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(10) DEFAULT NULL,
  `value` float DEFAULT NULL,
  `frequency` varchar(1) DEFAULT NULL,
  `Active` int(11) NOT NULL DEFAULT '1',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  CONSTRAINT `dose_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8