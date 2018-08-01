<?php


use Modules\IcommerceCredibanco\Entities\Configcredibanco;

if (! function_exists('icommercecredibanco_get_configuration')) {

    function icommercecredibanco_get_configuration()
    {

    	$configuration = new Configcredibanco();
    	return $configuration->getData();

    }

}

if (! function_exists('icommercecredibanco_get_entity')) {

	function icommercecredibanco_get_entity()
    {
    	$entity = new Configcredibanco;
    	return $entity;	
    }

}
