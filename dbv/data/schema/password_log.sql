CREATE TABLE `password_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(150) NOT NULL,
  `password` varchar(150) NOT NULL,
  `date_changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1