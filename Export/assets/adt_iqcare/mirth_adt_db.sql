
 
CREATE TABLE IF NOT EXISTS `dtl_patientpharmacyorder` (
  `id` int(11) NOT NULL,
  `PtnPk` int(11) NOT NULL,
  `ptn_pharmacyPK` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `dose` float NOT NULL,
  `frequency` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `drug_id` int(11) NOT NULL,
  `adt_drugId` int(11) NOT NULL,
  `dosage_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `created_on` date NOT NULL,
  `created_by` tinyint(4) NOT NULL,
  `moveToADT` int(11) NOT NULL,
  `is_updated` int(11) NOT NULL
) //



CREATE TABLE IF NOT EXISTS `ord_patientpharmacyorder` (
  `id` int(11) NOT NULL,
  `PtnPk` int(11) NOT NULL,
  `Ptnpk_pharmacy_pk` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `date_of_issue` date NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `regimen_id` int(4) NOT NULL,
  `adt_regimen_id` int(4) NOT NULL,
  `regimen_change_id` tinyint(4) NOT NULL,
  `created_on` date NOT NULL,
  `created_by` tinyint(4) NOT NULL,
  `MoveToADT` tinyint(4) NOT NULL,
  `is_updated` tinyint(4) NOT NULL
)//



CREATE TABLE IF NOT EXISTS `patient_iqcare` (
  `id` int(10) NOT NULL,
  `PtnPk` varchar(30) NOT NULL,
  `patient_number_ccc` varchar(300) DEFAULT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `other_name` varchar(150) DEFAULT NULL,
  `dob` varchar(96) DEFAULT NULL,
  `pob` varchar(300) DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `pregnant` varchar(30) DEFAULT NULL,
  `weight` varchar(60) DEFAULT NULL,
  `height` varchar(60) DEFAULT NULL,
  `phone` varchar(90) DEFAULT NULL,
  `physical` text,
  `other_illnesses` text,
  `other_drugs` text,
  `smoke` varchar(30) DEFAULT NULL,
  `alcohol` varchar(30) DEFAULT NULL,
  `date_enrolled` varchar(96) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `supported_by` varchar(30) DEFAULT NULL,
  `timestamp` varchar(96) DEFAULT NULL,
  `facility_code` varchar(30) DEFAULT NULL,
  `service` varchar(150) DEFAULT NULL,
  `start_regimen` varchar(150) DEFAULT NULL,
  `start_regimen_date` varchar(45) DEFAULT NULL,
  `current_status` varchar(150) DEFAULT NULL,
  `current_regimen` varchar(765) DEFAULT NULL,
  `start_height` varchar(60) DEFAULT NULL,
  `start_weight` varchar(60) DEFAULT NULL,
  `start_bsa` varchar(60) DEFAULT NULL,
  `transfer_from` varchar(300) DEFAULT NULL,
  `active` int(11) DEFAULT '1',
  `drug_allergies` text,
  `who_stage` varchar(20) DEFAULT NULL,
  `inserted_to_adt` int(11) DEFAULT '0'
)//


CREATE TABLE `tbl_adt_dispensing` (
  `id` bigint(20) NOT NULL,
  `ptnpk` bigint(20) NOT NULL,
  `drug` varchar(300) NOT NULL,
  `dispensing_date` date NOT NULL,
  `dispensing_quantity` bigint(20) NOT NULL,
  `ptn_pharmacy_PK` bigint(20) NOT NULL,
  `next_appointment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1//