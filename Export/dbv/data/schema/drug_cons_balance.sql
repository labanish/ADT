CREATE TABLE `drug_cons_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `drug_id` int(11) NOT NULL,
  `stock_type` int(11) NOT NULL,
  `period` varchar(15) NOT NULL COMMENT 'conside only month and year, day is only for formating purposes',
  `amount` int(11) NOT NULL,
  `facility` varchar(30) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  UNIQUE KEY `drug_id` (`drug_id`,`stock_type`,`period`),
  KEY `period` (`period`),
  KEY `drug_id_2` (`drug_id`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  CONSTRAINT `drug_cons_balance_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1