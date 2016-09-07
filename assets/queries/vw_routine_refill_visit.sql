CREATE OR REPLACE VIEW vw_routine_refill_visit AS
	SELECT 
		p.patient_number_ccc AS patient_number,
		rst.name AS type_of_service,
		s.Name AS client_support,
		CONCAT_WS(' ', p.first_name, p.other_name, p.last_name) AS patient_name,
		FLOOR(DATEDIFF(CURDATE(),p.dob)/365) as current_age,
		g.name AS sex,
		CONCAT_WS(' | ', r.regimen_code, r.regimen_desc) AS regimen,
		pv.dispensing_date AS visit_date,
		pv.current_weight AS current_weight,
	    CASE 
	    WHEN pv.pill_count > 0 AND (pv.missed_pills - pv.pill_count) >= pv.pill_count THEN 0
	    WHEN pv.pill_count > 0 AND (pv.missed_pills - pv.pill_count) > 0 THEN ROUND(((pv.missed_pills - pv.pill_count)/pv.pill_count)*100,2)
	    WHEN pv.missed_pills NOT REGEXP '[0-9]+' OR pv.missed_pills IS NULL THEN '-'
	    ELSE 100 END AS missed_pill_adherence,
	    CASE 
	    WHEN pv.pill_count > 0 AND (pv.months_of_stock - pv.pill_count) >= pv.pill_count THEN 0
	    WHEN pv.pill_count > 0 AND (pv.months_of_stock - pv.pill_count) > 0 THEN ROUND(((pv.months_of_stock - pv.pill_count)/pv.pill_count)*100,2)
	    WHEN pv.months_of_stock NOT REGEXP '[0-9]+' OR pv.months_of_stock IS NULL THEN '-'
	    ELSE 100 END AS pill_count_adherence,
	    CASE 
	    WHEN REPLACE(pv.adherence, '%', '') > 100 THEN 100
	    WHEN REPLACE(pv.adherence, '%', '') < 0 THEN 0
	    WHEN REPLACE(pv.adherence, '%', '') = 'Infinity' THEN ''
	    ELSE REPLACE(pv.adherence, '%', '')
	    END AS appointment_adherence,
		ps.name AS source
	FROM patient_visit pv
	LEFT JOIN patient p ON p.patient_number_ccc = pv.patient_id
	LEFT JOIN regimen_service_type rst ON rst.id = p.service
	LEFT JOIN supporter s ON s.id = p.supported_by
	LEFT JOIN gender g ON g.id = p.gender
	LEFT JOIN regimen r ON r.id = pv.regimen
	LEFT JOIN patient_source ps on ps.id = p.source
	LEFT JOIN visit_purpose v ON v.id = pv.visit_purpose
	WHERE pv.active = 1
	AND v.name LIKE '%routine%'//