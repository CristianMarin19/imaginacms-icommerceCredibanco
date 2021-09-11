<?php

namespace Modules\Icommercecredibanco\Repositories\Eloquent;

use Modules\Icommercecredibanco\Repositories\IcommerceCredibancoRepository;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;

class EloquentIcommerceCredibancoRepository extends EloquentBaseRepository implements IcommerceCredibancoRepository
{
  
  function calculate($parameters,$conf){
    

    if(isset($conf->maximumAmount) && !empty($conf->maximumAmount)) {
      if (isset($parameters["products"]["total"]))
        if($parameters["products"]["total"]>$conf->maximumAmount){
          $response["status"] = "error";
  
          // Items
          $response["items"] = trans("icommercecredibanco::icommercecredibancos.validation.maximumAmount",["maximumAmount" => formatMoney($conf->maximumAmount)]);
          $response["msj"] = trans("icommercecredibanco::icommercecredibancos.validation.maximumAmount",["maximumAmount" =>formatMoney($conf->maximumAmount)]);
          
  
          // Price
          $response["price"] = 0;
          $response["priceshow"] = false;
          
          return $response;
        }
        
    
    }
    $response["status"] = "success";
    
    // Items
    $response["items"] = null;
    
    // Price
    $response["price"] = 0;
    $response["priceshow"] = false;
    
    return $response;
    
  }

}
