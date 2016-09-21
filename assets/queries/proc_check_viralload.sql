DROP PROCEDURE IF EXISTS proc_check_patient;
DELIMITER //
CREATE PROCEDURE proc_check_patient(

	IN in_ccc_number VARCHAR(30), 
	IN in_test_date DATE,
	IN in_result VARCHAR(30),
	IN in_justification varchar(30)
	
	)
BEGIN
	DECLARE patient_id INT DEFAULT 0;
	IF NOT EXISTS(SELECT * FROM patient_viral_load WHERE patient_ccc_number = in_ccc_number AND test_date = in_test_date AND result = in_result AND justification = in_justification)
	THEN
		INSERT INTO patient_viral_load(patient_ccc_number,test_date,result,justification)
		values(in_ccc_number,in_test_date,in_result,in_justification);
	END IF;
END //
DELIMITER ;