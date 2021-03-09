<?php

class shopLenscalcPluginBackendSaveconfController extends waJsonController
{
    

    public function execute()
    {
		
		$data = waRequest::post();
		$csModel = new shopLenscalcPluginCalcSettingsModel();
		$csModel->query("DELETE FROM ".$csModel->getTableName());
		foreach($data as $key => $value){
			$csModel->insert($value);
		}
		
		$rspns =  [1,2,3,4];
		$this->response = $data;
    }
}