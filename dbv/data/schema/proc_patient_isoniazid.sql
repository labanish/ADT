
-- a query to select the...
	
-- 	patient_number_ccc
-- 	first_name
-- 	last_name
-- 	gender
-- 	current_status
-- 	active
-- 	isoniazid_start_date
-- 	isoniazid_end_date
-- 	status_change_date

-- status = 1 is "Active" 
-- change the dates as "YYYY-MM-DD"


SELECT 
	`patient_number_ccc`,
	`first_name`,
	`last_name`,
	`active`,
	`current_status`
	`status_change_date`,
	`isoniazid_start_date`,
	`isoniazid_end_date`
FROM `testadt_`.`patient`

WHERE 
	`isoniazid_start_date` IS NOT NULL ORDER BY `isoniazid_start_date` ASC;
 