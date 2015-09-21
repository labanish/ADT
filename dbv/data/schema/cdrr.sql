CREATE TABLE `cdrr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(20) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `code` varchar(15) DEFAULT NULL,
  `period_begin` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `comments` text,
  `reports_expected` int(11) DEFAULT NULL,
  `reports_actual` int(11) DEFAULT NULL,
  `services` varchar(255) DEFAULT NULL,
  `sponsors` varchar(255) DEFAULT NULL,
  `non_arv` int(11) NOT NULL,
  `delivery_note` varchar(255) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `facility_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8