
-- Add new rows to the regimens' table [update_sync_regimen_category]

-- To call- call update_sync_regimen_category(17,'Adult Third Line','1',2);


DROP PROCEDURE IF EXISTS `proc_update_sync_regimen_category`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_update_sync_regimen_category`(
  IN loc_id INT(11),
  IN loc_Name VARCHAR(50),
  IN loc_Active VARCHAR(2),
  IN loc_ccc_store_sp INT(2)
)
BEGIN

  INSERT INTO `sync_regimen_category` (id, Name, Active, ccc_store_sp)
  	VALUES(loc_id, loc_Name, loc_Active, loc_ccc_store_sp);


END