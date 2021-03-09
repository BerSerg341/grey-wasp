<?php

class shopLenscalcPluginCalcSettingsModel extends waModel
{
    protected $table = 'shop_lenscalc_calc_settings';
	
	public function getAllSorted(){
		return $this->query("SELECT * FROM ".$this->table." ORDER BY sort ASC")->fetchAll();		
	}
	
	public function getIndexes(){
		return $this->query("SELECT DISTINCT ind FROM ".$this->table." ORDER BY sort")->fetchAll('ind');
	}
	
	public function getMaterialByIndex($index){
		return $this->query("SELECT DISTINCT material FROM ".$this->table." WHERE ind='".$index."' ORDER BY sort")->fetchAll();
	}
	
	public function getDesignByParams($index,$material){
		return $this->query("SELECT DISTINCT design FROM ".$this->table." WHERE ind='".$index."' AND material='".$material."' ORDER BY sort")->fetchAll();
	}
	
	public function getFeaturesByParams($index,$material,$design){
		return $this->query("SELECT feature, val_from, val_to, val_step, price FROM ".$this->table." WHERE ind='".$index."' AND material='".$material."' AND design='".$design."' ORDER BY sort")->fetchAll();
	}
	
	public function getPriceByParams($index,$material,$design){
		return $this->query("SELECT price FROM ".$this->table." WHERE ind='".$index."' AND material='".$material."' AND design='".$design."'")->fetchField('price');
	}
}