CREATE TABLE IF NOT EXISTS dependants(
	id int(11) NOT NULL AUTO_INCREMENT,
	parent varchar(30),
	child varchar(30),
	PRIMARY KEY (id)
)//