<?php

return array(
    'name' => _wp('Lens calculator'),
    'description' => '',
    'img' => 'img/quickorder.png',
    'vendor' => '000000',
    'version' => '1.0.0',
    'frontend' => true,
    'handlers' => array(
		'backend_order' => 'backendOrder',
		'backend_menu' => 'backendMenu',
		'frontend_products' => 'calcProductPrice',
    ),
);