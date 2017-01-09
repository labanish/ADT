DELIMITER //
CREATE PROCEDURE `proc_check_patient`(

  IN fname VARCHAR(50), 
  IN lname VARCHAR(50),
  IN oname VARCHAR(50),
  IN dob1 VARCHAR(32),
  IN phone1 VARCHAR(30),
  IN ptnpk1 INT(11)
  )
BEGIN
  DECLARE patient_id INT DEFAULT NULL;
        SELECT id INTO patient_id FROM testadt.patient WHERE first_name = fname AND (last_name = lname OR other_name = oname) AND (dob = dob1 OR phone = phone1);
  IF (patient_id IS NOT NULL) 
  THEN
    UPDATE testadt.patient SET medical_record_number=ptnpk1 WHERE id=patient_id;
    /*SELECT 'updated'; */
  ELSE
    INSERT INTO testadt.patient(medical_record_number,patient_number_ccc,first_name,last_name,other_name,dob,pob,gender,pregnant,weight,height,phone,physical,
    other_illnesses,other_drugs,smoke,alcohol,date_enrolled,source,supported_by,timestamp,facility_code,service,start_regimen,start_regimen_date,  
    current_status, current_regimen,start_height,start_weight,start_bsa,transfer_from,active,drug_allergies,who_stage)
    (SELECT ptnpk,patient_number_ccc,first_name,last_name,other_name,dob,pob,gender,pregnant,weight,height,phone,physical,
    other_illnesses,other_drugs,smoke,alcohol,date_enrolled,source,supported_by,timestamp,facility_code,service,start_regimen,start_regimen_date,  
    current_status, current_regimen,start_height,start_weight,start_bsa,transfer_from,active,drug_allergies,who_stage
    FROM patient_iqcare
    where patient_iqcare.ptnpk=ptnpk1);
    /*SELECT 'inserted'; */
  END IF;
END//
DELIMITER ;