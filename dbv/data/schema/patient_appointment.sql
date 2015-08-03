CREATE TABLE `patient_appointment` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `patient` varchar(20) NOT NULL,
  `appointment` varchar(32) NOT NULL,
  `facility` varchar(10) NOT NULL,
  `machine_code` varchar(10) NOT NULL DEFAULT '0',
  `merge` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_index` (`patient`),
  KEY `facility_index` (`facility`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1