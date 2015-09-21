CREATE TABLE `transaction_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `desc` varchar(150) NOT NULL,
  `effect` int(11) NOT NULL DEFAULT '0',
  `active` int(5) NOT NULL DEFAULT '1',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  CONSTRAINT `transaction_type_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1