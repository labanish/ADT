CREATE TABLE IF NOT EXISTS spouses(
	id int(11) NOT NULL AUTO_INCREMENT,
	primary_spouse varchar(30),
	secondary_spouse varchar(30),
	PRIMARY KEY (id)
)//