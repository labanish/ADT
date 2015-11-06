DELIMITER
 //
create DEFINER=`root`@`localhost` procedure Proc_patients_isoniazid()
	BEGIN
	SELECT 
		patient_number_ccc,
		current_regimen,
		status_change_date,
		cast(`isoniazid_start_date` as date ) as isoniazid_start_date,cast(`isoniazid_end_date` as date ) as isoniazid_end_date 
	FROM PATIENT 
	WHERE isoniazid_start_date <> '' AND isoniazid_end_date <> '' AND active=1;

END //
DELIMITER ;
