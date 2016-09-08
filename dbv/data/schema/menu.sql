CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_text` varchar(50) NOT NULL,
  `menu_url` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `offline` varchar(1) NOT NULL DEFAULT '0',
  `active` int(5) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1