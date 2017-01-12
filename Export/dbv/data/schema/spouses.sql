CREATE TABLE `spouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `primary_spouse` varchar(30) DEFAULT NULL,
  `secondary_spouse` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1