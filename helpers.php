<?php

/**
* Get Iso
* @param $currency
* @return Code
*/
if (!function_exists('icommercecredibanco_currencyISO')) {

    function icommercecredibanco_currencyISO($currency){
        $currency = strtoupper($currency);

        if($currency=="COP")
            return 170;

        if($currency=="USD")
            return 840;
    }

}