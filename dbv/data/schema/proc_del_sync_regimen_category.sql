
-- Add new rows to the regimens' table [update_sync_regimen_category_del]

-- To call- call update_sync_regimen_category_del(17,'Adult Third Line','1',2);


DROP PROCEDURE IF EXISTS `proc_del_sync_regimen_category`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `proc_del_sync_regimen_category`(
  IN loc_id INT(11),
  IN loc_Name VARCHAR(50),
  IN loc_Active VARCHAR(2),
  IN loc_ccc_store_sp INT(2)
)
BEGIN


  DELETE FROM `sync_regimen_category` WHERE id = loc_id;

END

