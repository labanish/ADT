CREATE OR REPLACE VIEW vw_patient_pill_adherence AS
    SELECT 
        CASE 
        WHEN rst.name LIKE '%art%' THEN 'art'
        ELSE 'non_art' END AS service,
        CASE 
        WHEN FLOOR(DATEDIFF(pv.dispensing_date,p.dob)/365) > 24  THEN '>24'
        WHEN FLOOR(DATEDIFF(pv.dispensing_date,p.dob)/365) >= 15 AND FLOOR(DATEDIFF(pv.dispensing_date,p.dob)/365) < 25 THEN '15_25'
        ELSE '<15' END AS age,
        LCASE(g.name) AS gender,
        pv.dispensing_date AS visit_date,
        CASE 
        WHEN pv.pill_count > 0 AND (pv.missed_pills - pv.pill_count) >= pv.pill_count THEN 0
        WHEN pv.pill_count > 0 AND (pv.missed_pills - pv.pill_count) > 0 THEN ROUND(((pv.missed_pills - pv.pill_count)/pv.pill_count)*100,2)
        WHEN pv.missed_pills NOT REGEXP '[0-9]+' OR pv.missed_pills IS NULL THEN '-'
        ELSE 100 END AS missed_pill_adherence,
        CASE 
        WHEN pv.pill_count > 0 AND (pv.months_of_stock - pv.pill_count) >= pv.pill_count THEN 0
        WHEN pv.pill_count > 0 AND (pv.months_of_stock - pv.pill_count) > 0 THEN ROUND(((pv.months_of_stock - pv.pill_count)/pv.pill_count)*100,2)
        WHEN pv.months_of_stock NOT REGEXP '[0-9]+' OR pv.months_of_stock IS NULL THEN '-'
        ELSE 100 END AS pill_count_adherence
    FROM patient_visit pv
    LEFT JOIN patient p ON p.patient_number_ccc = pv.patient_id
    LEFT JOIN regimen_service_type rst ON rst.id = p.service
    LEFT JOIN gender g ON g.id = p.gender
    WHERE pv.active = 1
    AND p.dob IS NOT NULL
    AND g.name IS NOT NULL;