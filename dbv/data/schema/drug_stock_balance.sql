CREATE TABLE `drug_stock_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `drug_id` varchar(255) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `expiry_date` varchar(100) NOT NULL,
  `stock_type` int(4) NOT NULL,
  `facility_code` varchar(50) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT '0' COMMENT 'Keeps balance of commodity',
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  UNIQUE KEY `drug_id` (`drug_id`,`batch_number`,`expiry_date`,`stock_type`,`facility_code`),
  UNIQUE KEY `drug_id_2` (`drug_id`,`batch_number`,`expiry_date`,`stock_type`,`facility_code`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  CONSTRAINT `drug_stock_balance_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1