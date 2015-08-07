CREATE TABLE `regimen_drug` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `regimen` text NOT NULL,
  `drugcode` text NOT NULL,
  `source` varchar(10) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1',
  `Merged_From` varchar(50) NOT NULL,
  `Regimen_Merged_From` varchar(20) NOT NULL,
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `ccc_store_sp` (`ccc_store_sp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1