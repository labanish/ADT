CREATE TABLE `dependants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` varchar(30) DEFAULT NULL,
  `child` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1