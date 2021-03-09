<?php

class shopLenscalcPluginFrontendCalculateController extends waJsonController
{
    

    public function execute()
    {
		$plugin = waSystem::getInstance('shop')->getPlugin('lenscalc');
		$data = waRequest::post();
		$calcPrice = $plugin->calcLense($data);
		$this->response = $calcPrice;
    }
}