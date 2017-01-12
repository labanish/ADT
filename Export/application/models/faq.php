<?php

class Faq extends Doctrine_Record{
    public function setTableDefinition() {
        $this->hasColumn('modules', 'varchar', 100);
        $this->hasColumn('questions','varchar', 255);
        $this->hasColumn('answers', 'varchar', 255);
        $this->hasColumn('active', 'int', 5);
        
    }
    public function setUp() {
        $this->setTableName('faq');
    }
    public function getAll() {
        $query= Doctrine_Query::create()->select("*")->from("faq");
        $faqs= $query->execute(array(), Doctrine::HYDRATE_ARRAY);
        return $faqs;   
    }
    public function getAllActive() {
        $query = Doctrine_Query::create() -> select("*") -> from("faq")->where("active='1'");
        $faqs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
        return $faqs;
	}
    public function getAllHydrated(){
        $query= Doctrine_Query::create()->select("modules,questions, answers")->from("faq")->where("active='1'") ->groupBy("modules");
        $faqs=$query->execute(array(), Doctrine::HYDRATE_ARRAY);
        return $faqs;
    }
}

