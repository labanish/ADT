CREATE TABLE IF NOT EXISTS `transaction_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `desc` varchar(150) NOT NULL,
  `effect` int(11) NOT NULL DEFAULT '0',
  `active` int(5) NOT NULL DEFAULT '1',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`),
  CONSTRAINT `transaction_type_ibfk_1` FOREIGN KEY (`ccc_store_sp`) REFERENCES `ccc_store_service_point` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1//
INSERT INTO `transaction_type` (`id`, `name`, `desc`, `effect`, `active`, `ccc_store_sp`) VALUES
(1,	'Received from',	'Drug Received Report',	1,	1,	2),
(2,	'Balance Forward',	'Balance Forwarded',	1,	1,	2),
(3,	'Returns from (+)',	'Returns to Clients',	1,	1,	2),
(4,	'Adjustment (+)',	'Adjustments',	1,	1,	2),
(5,	'Dispensed to Patients',	'Dispensed to Patients',	0,	1,	2),
(6,	'Issued To',	'Drug Issue Report',	0,	1,	2),
(7,	'Adjustment (-)',	'Adjustments',	0,	1,	2),
(8,	'Returns to (-)',	'Returns to Suppliers',	0,	1,	2),
(9,	'Losses(-)',	'Losses',	0,	1,	2),
(10,	'Expired(-)',	'Expiry Report',	0,	1,	2),
(11,	'Starting Stock/Physical Count',	'Physical Count',	1,	1,	2)//