<?php
class shopLenscalcPluginCalcParamsModel extends waModel
{
    protected $table = 'shop_lenscalc_calc_params';
	
	public function getOrderData($id){
		$sql = "SELECT serialized_data FROM {$this->table} WHERE order_id = ".$id;
		return $this->query($sql)->fetchAll();
	}
	
	public function getOrderFlag($id){
		$sql = "SELECT api_flag FROM {$this->table} WHERE order_id = ".$id;
		return $this->query($sql)->fetchAll();
	}
	
	public function addOrderData($order_id, $order_post_data){
		$this->query("INSERT INTO {$this->table} (order_id,serialized_data) values (".$order_id.",'".$order_post_data."')");
	}
}