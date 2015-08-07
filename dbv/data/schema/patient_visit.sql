CREATE TABLE `patient_visit` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(100) NOT NULL,
  `visit_purpose` varchar(50) NOT NULL,
  `current_height` varchar(10) NOT NULL,
  `current_weight` varchar(100) NOT NULL,
  `regimen` varchar(100) NOT NULL,
  `regimen_change_reason` varchar(100) NOT NULL,
  `drug_id` varchar(255) NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `indication` varchar(10) NOT NULL,
  `pill_count` varchar(10) NOT NULL,
  `comment` text NOT NULL,
  `timestamp` varchar(32) NOT NULL,
  `user` varchar(10) NOT NULL,
  `facility` varchar(10) NOT NULL,
  `dose` varchar(20) NOT NULL,
  `dispensing_date` varchar(20) NOT NULL,
  `dispensing_date_timestamp` varchar(32) NOT NULL,
  `migration_id` varchar(10) NOT NULL,
  `quantity` varchar(100) NOT NULL,
  `machine_code` varchar(10) NOT NULL DEFAULT '0',
  `last_regimen` varchar(100) NOT NULL,
  `duration` varchar(10) NOT NULL,
  `months_of_stock` varchar(10) NOT NULL,
  `adherence` varchar(10) NOT NULL,
  `missed_pills` varchar(10) NOT NULL,
  `non_adherence_reason` varchar(255) NOT NULL,
  `merged_from` varchar(50) NOT NULL,
  `regimen_merged_from` varchar(20) NOT NULL,
  `last_regimen_merged_from` varchar(20) NOT NULL,
  `active` int(5) NOT NULL DEFAULT '1',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `patient_visit_index` (`patient_id`),
  KEY `facility_index` (`facility`),
  KEY `ccc_pharmacy` (`ccc_store_sp`),
  KEY `dispensing_date_2` (`dispensing_date`),
  KEY `dispensing_date_3` (`dispensing_date`),
  KEY `dispensing_date_4` (`dispensing_date`),
  KEY `dispensing_date` (`dispensing_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1