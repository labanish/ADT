CREATE TABLE `maps_item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `total` int(11) NOT NULL,
  `regimen_id` int(11) unsigned NOT NULL,
  `maps_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8