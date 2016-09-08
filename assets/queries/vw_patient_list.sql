CREATE OR REPLACE VIEW vw_patient_list AS 
	SELECT 
		p.patient_number_ccc AS ccc_number,
		p.first_name AS first_name,
		p.other_name AS other_name,
		p.last_name AS last_name,
		p.dob AS date_of_birth,
		ROUND(((to_days(curdate()) - to_days(p.dob)) / 360),0) AS age,
		IF(ROUND(((to_days(curdate()) - to_days(p.dob)) / 360),0) >= 14,'Adult','Paediatric') AS maturity,
		p.pob AS pob,
		IF(p.gender = 1,'MALE','FEMALE') AS gender,
		IF(p.pregnant = 1,'YES','NO') AS pregnant,
		p.weight AS current_weight,
		p.height AS current_height,
		p.sa AS current_bsa,p.phone AS phone_number,
		p.physical AS physical_address,
		p.alternate AS alternate_address,
		p.other_illnesses AS other_illnesses,
		p.other_drugs AS other_drugs,
		p.adr AS drug_allergies,
		IF(p.tb = 1,'YES','NO') AS tb,
		IF(p.smoke = 1,'YES','NO') AS smoke,
		IF(p.alcohol = 1,'YES','NO') AS alcohol,
		p.date_enrolled AS date_enrolled,
		ps.name AS patient_source,
		s.Name AS supported_by,
		rst.name AS service,
		r1.regimen_desc AS start_regimen,
		p.start_regimen_date AS start_regimen_date,
		pst.Name AS current_status,
		IF(p.sms_consent = 1,'YES','NO') AS sms_consent,
		p.fplan AS family_planning,
		p.tbphase AS tbphase,
		p.startphase AS startphase,
		p.endphase AS endphase,
		IF(p.partner_status = 1,'Concordant',IF(p.partner_status = 2,'Discordant','')) AS partner_status,
		p.status_change_date AS status_change_date,
		IF(p.partner_type = 1,'YES','NO') AS disclosure,
		p.support_group AS support_group,
		r.regimen_desc AS current_regimen,
		p.nextappointment AS nextappointment,
		(to_days(p.nextappointment) - to_days(curdate())) AS days_to_nextappointment,
		p.start_height AS start_height,
		p.start_weight AS start_weight,
		p.start_bsa AS start_bsa,
		IF(p.transfer_FROM <> '',f.name,'N/A') AS transfer_from,
		dp.name AS prophylaxis 
	FROM patient p 
	LEFT JOIN regimen r ON r.id = p.current_regimen 
	LEFT JOIN regimen r1 ON r1.id = p.start_regimen 
	LEFT JOIN patient_source ps ON ps.id = p.source 
	LEFT JOIN supporter s ON s.id = p.supported_by 
	LEFT JOIN regimen_service_type rst ON rst.id = p.service 
	LEFT JOIN patient_status pst ON pst.id = p.current_status 
	LEFT JOIN facilities f ON f.facilitycode = p.transfer_FROM 
	LEFT JOIN drug_prophylaxis dp ON dp.id = p.drug_prophylaxis 
	WHERE p.active = 1//
