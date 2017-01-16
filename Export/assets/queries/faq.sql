CREATE TABLE IF NOT EXISTS `faq` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`modules` varchar(100) NOT NULL,
	`questions` varchar(255) NOT NULL,
	`answers` varchar(255) NOT NULL,
	`active` int(5) NOT NULL DEFAULT '1',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1//