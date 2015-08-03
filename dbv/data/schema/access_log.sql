CREATE TABLE `access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_code` varchar(150) NOT NULL,
  `user_id` varchar(150) NOT NULL,
  `access_level` int(5) NOT NULL,
  `start_time` varchar(50) NOT NULL,
  `end_time` varchar(50) NOT NULL,
  `facility_code` varchar(150) NOT NULL,
  `access_type` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1