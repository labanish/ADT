CREATE TABLE IF NOT EXISTS dossing_chart(
  	id int(11) NOT NULL AUTO_INCREMENT,
	min_weight float NOT NULL,
	max_weight float NOT NULL,
	dose_id int(11) NOT NULL,
	is_active tinyint(4) NOT NULL DEFAULT '1',
	drug_id int(11) NOT NULL,
	PRIMARY KEY (id)
)//
