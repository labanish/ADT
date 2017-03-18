CREATE TABLE IF NOT EXISTS patient_viral_load(
  patient_ccc_number varchar(30),
  test_date date,
  result varchar(30),
  justification TEXT
)//
ALTER TABLE patient_viral_load ADD id int NOT NULL AUTO_INCREMENT primary key//
