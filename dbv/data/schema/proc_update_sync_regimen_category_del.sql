
-- Add new rows to the regimens' table [update_sync_regimen_category_del]

-- To call- call update_sync_regimen_category_del(17,'Adult Third Line','1',2);

DELIMITER $$

DROP PROCEDURE IF EXISTS `update_sync_regimen_category_del` $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_sync_regimen_category_del`(
  IN loc_id INT(11),
  IN loc_Name VARCHAR(50),
  IN loc_Active VARCHAR(2),
  IN loc_ccc_store_sp INT(2)
)
BEGIN

  INSERT INTO `update_sync_regimen_category_del` (id, Name, Active, ccc_store_sp)
  	VALUES(loc_id, loc_Name, loc_Active, loc_ccc_store_sp);

  DELETE FROM `update_sync_regimen_category_del` WHERE id = loc_id

END $$

DELIMITER ;