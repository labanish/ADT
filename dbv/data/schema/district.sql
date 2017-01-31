CREATE TABLE `district` (
  `id` int(14) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `active` int(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `ID` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1