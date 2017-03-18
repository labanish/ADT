UPDATE sync_drug SET Active ='0' WHERE id IN (
	SELECT *
	FROM (
		SELECT id
		FROM sync_drug
		WHERE id NOT IN (
			SELECT  min(id)
	        FROM sync_drug
	        WHERE Active = '1'
	        GROUP BY CONCAT_WS(') (',CONCAT_WS(' (', name, abbreviation),CONCAT_WS(') ', strength, formulation))
	        HAVING COUNT(id) > 1
	   	)
		AND CONCAT_WS(') (',CONCAT_WS(' (', name, abbreviation),CONCAT_WS(') ', strength, formulation)) IN (
			SELECT 
	            CONCAT_WS(') (',CONCAT_WS(' (', name, abbreviation),CONCAT_WS(') ', strength, formulation)) AS drug
	        FROM sync_drug
	        WHERE Active = '1'
	        GROUP BY drug
	        HAVING COUNT(id) > 1
	        ORDER BY drug
	    )
	) t
)//
UPDATE sync_drug SET Active = '0' WHERE id IN (
	SELECT * 
	FROM (
		SELECT id
		FROM sync_drug
		WHERE CONCAT_WS(')(',CONCAT_WS('(',name,abbreviation),CONCAT(strength, CONCAT(')',packsize))) IN ('Stavudine/Lamivudine/Nevirapine(d4T/3TC/NVP)(30/150/200mg)60', 'Stavudine/Lamivudine(d4T/3TC)(30/150mg)60','Zidovudine(AZT)(300mg)60','Darunavir(DRV)(300mg)120','Saquinavir(SQV)(200mg)270','Nevirapine(NVP)(10mg/ml)240','Raltegravir Susp(RAL)(100mg/5ml)60','Diflucan()(2mg/ml)100','Diflucan()(50mg/5ml)35','Diflucan()(200mg)28','Co-trimoxazole()(480mg)1000','Co-trimoxazole (500s) blister pack Tabs()(960mg)500')
	) t
)//
REPLACE INTO `sync_drug` (`id`, `name`, `abbreviation`, `strength`, `packsize`, `formulation`, `unit`, `note`, `weight`, `category_id`, `regimen_id`) VALUES
(245, 'Tenofovir/Emtricitabine', 'TDF/FTC', '300/200mg', 30, 'FDC Tabs', '', '', 0, 1, 0),
(246, 'Abacavir/Lamivudine', 'ABC/3TC', '600mg/300mg', 60, 'FDC Tabs', '', '', 0, 1, 0),
(247, 'Efavirenz', 'EFV', '400mg', 30, 'tabs', '', '', 0, 1, 0),
(248, 'Dolutegravir', 'DTG', '50mg', 30, 'tabs', '', '', 0, 1, 0),
(249, 'Abacavir/Lamivudine', 'ABC/3TC', '120mg/60mg', 60, 'FDC Tabs', '', '', 0, 2, 0),
(250, 'Lopinavir/ritonavir', 'LPV/r', '40/10mg', 120, 'Caps', '', '', 0, 2, 0),
(251, 'Atazanavir', 'ATV', '100mg', 60, 'Caps', '', '', 0, 2, 0),
(252, 'Fluconazole', '', '50mg', 100, 'Tabs', '', '', 0, 3, 0),
(253, 'Pyridoxine', '', '25mg', 100, 'Tabs', '', '', 0, 3, 0),
(254, 'Isoniazid', 'H', '300mg', 672, 'Tabs (for Pack of 672 tabs)', '', '', 0, 3, 0),
(255, 'Ethambutol', '', '400mg', 28, 'Tab (for Pack of 28 tabs)', '', '', 0, 4, 0),
(256, 'Pyrazinamide', '', '500mg', 28, 'Tab (for Pack of 28 tabs)', '', '', 0, 4, 0),
(257, 'Rifabutin', '', '150mg', 30, 'Tab', '', '', 0, 4, 0),
(258, 'Tenofovir/Lamivudine/Efavirenz', 'TDF/3TC/EFV', '300/300/400mg', 30, 'FDC Tabs', '', '', 0, 1, 0)//
UPDATE `sync_regimen_category` SET `Active` = '0' WHERE  `Name` IN('Other Pediatric Regimen', 'OIs Medicines {CM} and {OC} For Diflucan Donation Program ONLY')//
REPLACE INTO `sync_regimen_category` (`id`, `Name`, `Active`, `ccc_store_sp`) VALUES
(22, 'PrEP', '1', 2),
(23, 'Hepatitis B Patients who are HIV-ve', '1', 2),
(24, 'OIs Medicines [3.Fluconazole (treatment & prophylaxis)]', '1', 2)//
UPDATE `sync_regimen` SET `Active` = '0' WHERE `code` IN('AF3A','AF3B','AT1A','AT1B','AT1C','AT2A','CF3A','CF3B','CT1A','CT1B','CT1C','CT2A','PM1','PM2','PC1','PC2','PC4','PC5','PA1B','PA3B','OI4A','OI4C','OI3A','OI3C','CM3N','CM3R','OC3N','OC3R')//
REPLACE INTO `sync_regimen` (`id`, `name`, `code`, `old_code`, `description`, `category_id`) VALUES
(270, 'AZT + 3TC + DTG', 'AF1D', '', '', 4),
(271, 'TDF + 3TC + ATV/r', 'AF2D', '', '', 4),
(272, 'TDF + 3TC + DTG', 'AF2E', '', '', 4),
(273, 'TDF + 3TC + LPV/r (1L Adults <40kg)', 'AF2F', '', '', 4),
(274, 'TDF + 3TC + RAL (PWIDs intoIerant to ATV)', 'AF2G', '', '', 4),
(275, 'TDF + FTC + ATV/r', 'AF2H', '', '', 4),
(276, 'ABC + 3TC + DTG', 'AF4C', '', '', 4),
(277, 'RAL + DRV + RTV + ETV + other backbone ARVs', 'AT1D', '', '', 17),
(278, 'RAL + ETV + other backbone ARVs', 'AT1E', '', '', 17),
(279, 'RAL + DRV + RTV + other backbone ARVs', 'AT1F', '', '', 17),
(280, 'RAL + other backbone ARVs (2nd Line patients failing treatment)', 'AT1G', '', '', 17),
(281, 'ETV + other backbone ARVs', 'AT2B', '', '', 17),
(282, 'ETV + DRV + RTV + other backbone ARVs', 'AT2C', '', '', 17),
(283, 'DRV + RTV + other backbone ARVs', 'AT3A', '', '', 17),
(284, 'DTG + DRV + RTV + ETV + other backbone ARVs', 'AT4A', '', '', 17),
(285, 'DTG + ETV + other backbone ARVs', 'AT4B', '', '', 17),
(286, 'DTG + DRV + RTV + other backbone ARVs', 'AT4C', '', '', 17),
(287, 'DTG + other backbone ARVs (2nd Line patients failing treatment)', 'AT4D', '', '', 17),
(288, 'AZT + 3TC + RAL', 'CF1E', '', '', 7),
(289, 'ABC + 3TC + RAL', 'CF2F', '', '', 7),
(290, 'TDF + 3TC + NVP (children > 35kg)', 'CF4A', '', '', 7),
(291, 'TDF + 3TC + EFV', 'CF4B', '', '', 7),
(292, 'TDF + 3TC + LPV/r', 'CF4C', '', '', 7),
(293, 'TDF + 3TC + ATV/r', 'CF4D', '', '', 7),
(294, 'RAL + DRV + RTV + ETV + other backbone ARVs', 'CT1D', '', '', 18),
(295, 'RAL + ETV + other backbone ARVs', 'CT1E', '', '', 18),
(296, 'RAL + DRV + RTV + other backbone ARVs', 'CT1F', '', '', 18),
(297, 'RAL + other backbone ARVs', 'CT1G', '', '', 18),
(298, 'ETV + other backbone ARVs', 'CT2B', '', '', 18),
(299, 'ETV + DRV + RTV + other backbone ARVs', 'CT2C', '', '', 18),
(300, 'DRV + RTV + other backbone ARVs', 'CT3A', '', '', 18),
(301, 'DTG + DRV + RTV + ETV + other backbone ARVs', 'CT4A', '', '', 18),
(302, 'DTG + ETV + other backbone ARVs', 'CT4B', '', '', 18),
(303, 'DTG + DRV + RTV + other backbone ARVs', 'CT4C', '', '', 18),
(304, 'DTG + other backbone ARVs', 'CT4D', '', '', 18),
(305, 'AZT liquid BID + NVP liquid OD for 6 weeks then NVP liquid OD for 6 weeks', 'PC7', '', '', 11),
(306, 'AZT liquid BID + NVP liquid OD for 6 weeks then NVP liquid OD until 6 weeks after complete cessation of Breastfeeding (mother NOT on ART)', 'PC8', '', '', 11),
(307, 'AZT liquid BID for 12 weeks', 'PC9', '', '', 11),
(308, 'TDF + FTC (PrEP)', 'PRP1A', '', '', 22),
(309, 'TDF + 3TC (PrEP)', 'PRP1B', '', '', 22),
(310, 'TDF (PrEP)', 'PRP1C', '', '', 22),
(311, 'TDF + 3TC (HIV-ve HepB patients)', 'HPB1A', '', '', 23),
(312, 'TDF + FTC (HIV-ve HepB patients)', 'HPB1B', '', '', 23),
(313, 'Adult patients (=>15 Yrs) newly started on IPT in the month', 'OI4AN', '', '', 24),
(314, 'Paed patients (<15 Yrs) newly started on IPT in the month', 'OI4CN', '', '', 24),
(315, 'Adult patients (=>15 Yrs) on Fluconazole in the month', 'OI5A', '', '', 24),
(316, 'Paed patients (<15 Yrs) on Fluconazole in the month', 'OI5C', '', '', 24)//
UPDATE `sync_regimen` SET `category_id` = '7' WHERE `category_id` = '9'//