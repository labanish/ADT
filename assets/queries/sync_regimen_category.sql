CREATE TABLE IF NOT EXISTS `sync_regimen_category` (
  `id` int(2) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Active` varchar(2) NOT NULL DEFAULT '1',
  `ccc_store_sp` int(11) NOT NULL DEFAULT '2'
) ENGINE=InnoDB DEFAULT CHARSET=latin1//
ALTER TABLE `sync_regimen_category` ADD PRIMARY KEY (`id`), ADD KEY `ccc_store_sp` (`ccc_store_sp`)//
ALTER TABLE `sync_regimen_category` MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22//
ALTER TABLE `sync_regimen_category` CHANGE  `Active`  `Active` VARCHAR( 2 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  '1'//
REPLACE INTO `sync_regimen_category` (`id`, `Name`, `Active`, `ccc_store_sp`) VALUES
(4, 'Adult First Line', '1', 2),
(5, 'Adult Second Line', '1', 2),
(7, 'Paediatric First Line', '1', 2),
(8, 'Paediatric Second Line', '1', 2),
(9, 'Other Pediatric Regimen', '1', 2),
(10, 'PMTCT Mother', '1', 2),
(11, 'PMTCT Child', '1', 2),
(12, 'PEP Adult', '1', 2),
(13, 'PEP Child', '1', 2),
(17, 'Adult Third Line', '1', 2),
(18, 'Paediatric Third Line', '1', 2),
(19, 'OIs Medicines [1. Universal Prophylaxis]', '1', 2),
(20, 'OIs Medicines [2. IPT]', '1', 2),
(21, 'OIs Medicines {CM} and {OC} For Diflucan Donation Program ONLY', '1', 2)//
UPDATE `sync_regimen_category` SET `Active` = '1' WHERE `Active` = ''//