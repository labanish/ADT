DELIMITER //
CREATE TRIGGER `before_insert` AFTER INSERT ON `patient_iqcare` FOR EACH ROW BEGIN
  
  CALL proc_check_patient(NEW.first_name,NEW.last_name,NEW.other_name,NEW.dob,NEW.phone,NEW.PtnPK);

END//
DELIMITER ;