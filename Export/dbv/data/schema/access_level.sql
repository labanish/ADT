CREATE TABLE `access_level` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `level_name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `indicator` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1