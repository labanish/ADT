DROP TRIGGER IF EXISTS before_insert_patient_visits;
DELIMITER //
CREATE TRIGGER `before_insert_patient_visits` BEFORE INSERT ON `patient_visit` FOR EACH ROW BEGIN
  
  CALL proc_check_drug(NEW.patient_id,NEW.drug_id,NEW.dispensing_date,NEW.quantity);

END//
DELIMITER ;
DROP PROCEDURE IF EXISTS proc_check_drug;
DELIMITER //
CREATE PROCEDURE `proc_check_drug`(
				IN pid VARCHAR(50), 
				IN drug VARCHAR(50),
				IN d_date VARCHAR(50),
				IN quantity VARCHAR(32)
			)
			BEGIN
			DECLARE ptnpk INT DEFAULT NULL;
			DECLARE pharmacy_pk INT DEFAULT NULL;
			DECLARE appointment DATE DEFAULT NULL;
			SELECT medical_record_number INTO ptnpk FROM patient WHERE patient_number_ccc = pid;
			SELECT Ptnpk_pharmacy_pk INTO pharmacy_pk FROM mirth_adt_db.ord_patientpharmacyorder WHERE PtnPk=ptnpk AND MoveToADT=0 AND is_updated=0;
			SELECT nextappointment INTO appointment FROM patient WHERE patient_number_ccc = pid;
			IF (ptnpk IS NOT NULL AND pharmacy_pk IS NOT NULL) 
			THEN
			INSERT INTO mirth_adt_db.tbl_adt_dispensing(ptnpk,drug,dispensing_date,dispensing_quantity,ptn_pharmacy_PK,next_appointment_date)values
			(ptnpk,drug,d_date,quantity,pharmacy_pk,appointment);
			END IF;
			END //
DELIMITER ;