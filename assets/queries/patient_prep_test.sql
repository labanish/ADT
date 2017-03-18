CREATE TABLE IF NOT EXISTS `patient_prep_test` (
  `patient_id` int(11) NOT NULL,
  `is_tested` tinyint(1) NOT NULL DEFAULT '0',
  `test_date` date DEFAULT NULL,
  `test_result` tinyint(1) DEFAULT '0',
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_prep_test_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patient` (`id`)
)//