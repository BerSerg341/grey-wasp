<?php
class shopLenscalcPluginBackendConfigAction extends waViewAction
{
    public function execute()
	{
		$this->setLayout(new shopBackendLayout());
		$csModel = new shopLenscalcPluginCalcSettingsModel();
		$calcSettings = $csModel->getAllSorted();
		
		/*prepare tree*/
		$tree = array();
		foreach ($calcSettings as $key => $item) {
		   $tree[$item['ind']][$item['material']][$item['design']][$item['feature']] = $item;
		}
		
		$plugin = waSystem::getInstance('shop')->getPlugin('storequickorder');
		$this->view->assign('settings', $plugin->getSettings());
		$this->view->assign('cs', $tree);
	}
}