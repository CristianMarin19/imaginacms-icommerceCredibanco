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

/**
* Delete _es URL 
* @param
* @return newurl
*/
if (!function_exists('icommercecredibanco_processUrl')) {
    function icommercecredibanco_processUrl($url){

        $caracter = "_es";
        $newUrl = str_replace($caracter,"", $url);
        return $newUrl;

    }
}


/**
* Format total value
* @param $total
* @return Total
*/
if (!function_exists('icommercecredibanco_formatTotal')) {

    function icommercecredibanco_formatTotal($total){
       
        $newTotal = number_format($total, 0, '.', '');
        $newTotal2 = $newTotal ."00";

        return $newTotal2;

    }

}