CREATE TABLE `users` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Username` varchar(30) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Access_Level` varchar(1) NOT NULL,
  `Facility_Code` varchar(10) NOT NULL,
  `Created_By` varchar(5) NOT NULL,
  `Time_Created` varchar(32) NOT NULL,
  `Phone_Number` varchar(50) NOT NULL,
  `Email_Address` varchar(50) NOT NULL,
  `Active` varchar(2) NOT NULL,
  `Signature` varchar(100) NOT NULL,
  `map` int(11) NOT NULL,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1