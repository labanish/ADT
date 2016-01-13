
-- Add new rows to the regimens' table [regimen_category]

DELIMITER $$

DROP PROCEDURE IF EXISTS `regimen_category` $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `regimen_category`(
  IN loc_id INT(11),
  IN loc_Name VARCHAR(50),
  IN loc_Active VARCHAR(2),
  IN loc_ccc_store_sp INT(2)
)
BEGIN

  INSERT INTO `regimen_category` (id, Name, Active, ccc_store_sp)
  	VALUES(loc_id, loc_Name, loc_Active, loc_ccc_store_sp);

END $$

DELIMITER ;