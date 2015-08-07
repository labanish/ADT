CREATE TABLE `counties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `county` varchar(30) NOT NULL,
  `active` int(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1