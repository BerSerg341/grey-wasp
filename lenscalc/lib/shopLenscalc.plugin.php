<?php

class shopLenscalcPlugin extends shopPlugin
{
	
	public static function displayCalc(){
		/*get selected product from settings*/
		$p_id = waSystem::getInstance('shop')->getPlugin('lenscalc')->getSettings('productid');
		if(empty($p_id)){return array();}
		$product = new shopProduct($p_id, true);
		$csModel = new shopLenscalcPluginCalcSettingsModel();
		$indexes = $csModel->getIndexes();
				
		/*return feat+vals array and push througn view*/
		$output['values'] = $indexes;
		$output['product'] = $product;
		$lc_plugin = waSystem::getInstance('shop')->getPlugin('lenscalc');	

		list($services, $skus_services) = $lc_plugin->getServiceVars($product);
        		
		$view = wa()->getView();		
		$view->assign('output', $output);
		$view->assign('sku_services', $skus_services);
        $view->assign('services', $services);
		return $view->fetch(dirname(__FILE__).'/../templates/FrontendForm.html');
	}
	
	public function calcLense($data, $req="calculate"){
		
		$response = array();
		if($req == 'order'){
			$csModel = new shopLenscalcPluginCalcSettingsModel();
			$leftPrice = $csModel->getPriceByParams($data['L_index'],$data['L_material'],$data['L_design']);
			$rightPrice = $csModel->getPriceByParams($data['R_index'],$data['R_material'],$data['R_design']);
			$returnPrice = intval($leftPrice) + intval($rightPrice);
			
			return $returnPrice;
		}
		if(!isset($data['interact'])){return $response;}
		$csModel = new shopLenscalcPluginCalcSettingsModel();
		if($data['interact'] == 'index'){
			if(!empty($data['L_index'])){
				$response['L_material'] = $csModel->getMaterialByIndex($data['L_index']);
			}
			if(!empty($data['R_index'])){
				$response['R_material'] = $csModel->getMaterialByIndex($data['R_index']);
			}			
		}
		if($data['interact'] == 'material'){
			if(!empty($data['L_index']) && !empty($data['L_material'])){
				$response['L_design'] = $csModel->getDesignByParams($data['L_index'],$data['L_material']);
			}
			if(!empty($data['R_index']) && !empty($data['R_material'])){
				$response['R_design'] = $csModel->getDesignByParams($data['R_index'],$data['R_material']);
			}
			wa()->getStorage()->remove('lenscalc_vars');
		}
		
		if($data['interact'] == 'design'){
			if(!empty($data['L_index']) && !empty($data['L_material']) && !empty($data['L_design'])){
				$response['L_features'] = $csModel->getFeaturesByParams($data['L_index'],$data['L_material'],$data['L_design']);
			}
			if(!empty($data['R_index']) && !empty($data['R_material']) && !empty($data['R_design'])){
				$response['R_features'] = $csModel->getFeaturesByParams($data['R_index'],$data['R_material'],$data['R_design']);
			}
			
			
		}
		wa()->getStorage()->remove('lenscalc_vars');
		
		$sess_params = array();
		if(!empty($data['L_index']) && !empty($data['L_material']) && !empty($data['L_design'])){			
			$sess_params['L_index'] = $data['L_index'];
			$sess_params['L_material'] = $data['L_material'];
			$sess_params['L_design'] = $data['L_design'];
		}
		if(!empty($data['R_index']) && !empty($data['R_material']) && !empty($data['R_design'])){
			$sess_params['R_index'] = $data['R_index'];
			$sess_params['R_material'] = $data['R_material'];
			$sess_params['R_design'] = $data['R_design'];
		}
		
		$session_params = wa()->getStorage()->write('lenscalc_vars',$sess_params);
		return $response;

	}
	
	public function backendMenu()
	{
		$action = waRequest::get('action', '', 'string');
		$plugin = waRequest::get('plugin', '', 'string');
		
		$class = 'no-tab';
		if($plugin == 'lenscalc' && $action == 'config') {$class = 'selected';}
		
		$view = wa()->getView();
		$view->assign('pt_class', $class);
		return array('core_li' => $view->fetch(wa()->getAppPath(null, 'shop').'/plugins/lenscalc/templates/BackendMenu.html'));
	}


    /*show order parameters*/
    public function backendOrder($order_data)
    {
        if ( isset($order_data['params']['plugin_storequickorder'])  )
            $pl = true;
        else
            $pl = false;
        $return = array();
        if ($pl){
			$view = wa()->getView();
			/*get oreder params from db*/
			$calcParamsModel = new shopLenscalcPluginCalcParamsModel();
			$data = $calcParamsModel->getOrderData($order_data['id']);
			$view->assign('calc_params', json_decode($data[0]['serialized_data']));
			/*get order api flag and push to view*/
			$data = $calcParamsModel->getOrderFlag($order_data['id']);
			$flag = $data[0]['api_flag'];
			$view->assign('flag', $flag);
			$apiFlag = $calcParamsModel->getOrderFlag($order_data['id']);
			return array('info_section' => $view->fetch(wa()->getAppPath(null, 'shop').'/plugins/lenscalc/templates/OrderData.html'));
		}	
        return $return;
    }
	
	public function calcProductPrice(&$data){
		foreach ($data['products'] as &$p) {
			/*check for discount product*/
			$p_id = waSystem::getInstance('shop')->getPlugin('lenscalc')->getSettings('productid');
			if($p['id'] != $p_id){break;}
			/*collect storage params for calcLense (L/R index, material, design)*/
			$session_params = wa()->getStorage()->read('lenscalc_vars');
			/*set new price*/			
			$new_price = $this->calcLense($session_params,"order");
		
			$p['price'] = $new_price;
			
			if (isset($data['skus'])) {
				foreach ($data['skus'] as &$s) {
					if($s['id'] == $p['sku_id']){
						$s['price'] = $new_price;						
					}
				}
				unset($s);
			}			
        }
        unset($p);
    }
	
	/*duplicate of core protected function*/
	protected function getServiceVars($product)
    {
        $type_services_model = new shopTypeServicesModel();
        $type_service_ids = $type_services_model->getServiceIds($product['type_id']);

        // Fetch services
        $service_model = new shopServiceModel();
        $product_services_model = new shopProductServicesModel();

        $product_service_ids = $product_services_model->getServiceIds($product['id'], array(
            'ignore' => array(
                'status' => shopProductServicesModel::STATUS_FORBIDDEN
            )
        ));

        $services = array_merge($type_service_ids, $product_service_ids);
        $services = array_unique($services);

        $services = $service_model->getById($services);

        $need_round_services = wa()->getSetting('round_services');
        if ($need_round_services) {
            shopRounding::roundServices($services);
        }

        // Convert service.price from default currency to service.currency
        foreach ($services as &$s) {
            $s['price'] = shop_currency($s['price'], null, $s['currency'], false);
        }
        unset($s);

        $enable_by_type = array_fill_keys($type_service_ids, true);

        // Fetch service variants
        $variants_model = new shopServiceVariantsModel();
        $rows = $variants_model->getByField('service_id', array_keys($services), true);

        if ($need_round_services) {
            shopRounding::roundServiceVariants($rows, $services);
        }

        foreach ($rows as $row) {
            if (!$row['price']) {
                $row['price'] = $services[$row['service_id']]['price'];
            } elseif ($services[$row['service_id']]['variant_id'] == $row['id']) {
                $services[$row['service_id']]['price'] = $row['price'];
            }
            $row['status'] = !empty($enable_by_type[$row['service_id']]);
            $services[$row['service_id']]['variants'][$row['id']] = $row;
        }

        // Fetch service prices for specific products and skus
        $rows = $product_services_model->getByField('product_id', $product['id'], true);

        if ($need_round_services) {
            shopRounding::roundServiceVariants($rows, $services);
        }

        // re-define statuses of service variants for that product
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                $services[$row['service_id']]['variants'][$row['service_variant_id']]['status'] = $row['status'];
            }
        }

        // Remove disable service variants
        foreach ($services as $service_id => $service) {
            if (isset($service['variants'])) {
                foreach ($service['variants'] as $variant_id => $variant) {
                    if (!$variant['status']) {
                        unset($services[$service_id]['variants'][$variant_id]);
                    }
                }
            }
        }

        // sku_id => [service_id => price]
        $skus_services = array();
        foreach ($product['skus'] as $sku) {
            $skus_services[$sku['id']] = array();
        }

        foreach ($rows as $row) {
            if (!$row['sku_id']) {

                if ($row['status'] && $row['price'] !== null) {
                    // update price for service variant, when it is specified for this product
                    $services[$row['service_id']]['variants'][$row['service_variant_id']]['price'] = $row['price'];
                    // !!! also set other keys related to price
                }
                if ($row['status'] == shopProductServicesModel::STATUS_DEFAULT) {
                    // default variant is different for this product
                    $services[$row['service_id']]['variant_id'] = $row['service_variant_id'];
                }
            } else {
                if (!$row['status']) {
                    $skus_services[$row['sku_id']][$row['service_id']][$row['service_variant_id']] = false;
                } else {
                    $skus_services[$row['sku_id']][$row['service_id']][$row['service_variant_id']] = $row['price'];
                }
            }
        }

        // Fill in gaps in $skus_services
        foreach ($skus_services as $sku_id => &$sku_services) {
            if (!isset($product['skus'][$sku_id])) {
                continue;
            }
            $sku_price = $product['skus'][$sku_id]['price'];
            foreach ($services as $service_id => $service) {
                if (isset($sku_services[$service_id])) {
                    if ($sku_services[$service_id]) {
                        foreach ($service['variants'] as $v) {
                            if (!isset($sku_services[$service_id][$v['id']]) || $sku_services[$service_id][$v['id']] === null) {
                                $sku_services[$service_id][$v['id']] = array(
                                    $v['name'],
                                    $this->getPrice($v['price'], $service['currency'], $sku_price, $product['currency']),
                                );
                            } elseif ($sku_services[$service_id][$v['id']]) {
                                $sku_services[$service_id][$v['id']] = array(
                                    $v['name'],
                                    $this->getPrice($sku_services[$service_id][$v['id']], $service['currency'], $sku_price, $product['currency']),
                                );
                            }
                        }
                    }
                } else {
                    foreach ($service['variants'] as $v) {
                        $sku_services[$service_id][$v['id']] = array(
                            $v['name'],
                            $this->getPrice($v['price'], $service['currency'], $sku_price, $product['currency']),
                        );
                    }
                }
            }
        }
        unset($sku_services);

        // disable service if all variants are disabled
        foreach ($skus_services as $sku_id => $sku_services) {
            foreach ($sku_services as $service_id => $service) {
                if (is_array($service)) {
                    $disabled = true;
                    foreach ($service as $v) {
                        if ($v !== false) {
                            $disabled = false;
                            break;
                        }
                    }
                    if ($disabled) {
                        $skus_services[$sku_id][$service_id] = false;
                    }
                }
            }
        }

        // Calculate prices for %-based services,
        // and disable variants selector when there's only one value available.
        foreach ($services as $s_id => &$s) {
            if (!$s['variants']) {
                unset($services[$s_id]);
                continue;
            }
            if ($s['currency'] == '%') {
                $item = array(
                    'price'    => $product['skus'][$product['sku_id']]['price'],
                    'currency' => $product['currency'],
                );
                shopProductServicesModel::workupItemServices($s, $item);
            }


            if (count($s['variants']) == 1) {
                $v = reset($s['variants']);
                if ($v['name']) {
                    $s['name'] .= ' '.$v['name'];
                }
                $s['variant_id'] = $v['id'];
                $s['price'] = $v['price'];
                unset($s['variants']);
                foreach ($skus_services as $sku_id => $sku_services) {
                    if (isset($sku_services[$s_id]) && isset($sku_services[$s_id][$v['id']])) {
                        $skus_services[$sku_id][$s_id] = $sku_services[$s_id][$v['id']][1];
                    }
                }
            }
        }
        unset($s);

        uasort($services, array('shopServiceModel', 'sortServices'));

        return array($services, $skus_services);
    }
	
	protected function getPrice($price, $currency, $product_price, $product_currency)
    {
        if ($currency == '%') {
            $round_services = wa()->getSetting('round_services');
            if ($round_services) {
                return shopRounding::roundCurrency($price * $product_price / 100, $product_currency);
            } else {
                return shop_currency($price * $product_price / 100, $product_currency, null, 0);
            }
        } else {
            return shop_currency($price, $currency, null, 0);
        }
    }
}
