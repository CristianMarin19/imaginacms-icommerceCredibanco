<?php

namespace Modules\Icommercecredibanco\Http\Controllers;

// Requests & Response
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// Base
use Modules\Core\Http\Controllers\BasePublicController;
use Modules\Icommercecredibanco\Http\Controllers\Api\IcommerceCredibancoApiController;

// Repositories
use Modules\Icommerce\Repositories\PaymentMethodRepository;
use Modules\Icommerce\Repositories\OrderRepository;
use Modules\Icommerce\Repositories\TransactionRepository;

//Others
use Modules\Setting\Contracts\Setting;


class PublicController extends BasePublicController
{
  
    private $paymentMethod;
    private $order;
    private $transaction;
    private $setting;
    private $credibancoApiController;

    public function __construct(
        PaymentMethodRepository $paymentMethod,
        OrderRepository $order,
        TransactionRepository $transaction,
        Setting $setting,
        IcommerceCredibancoApiController $credibancoApiController
    )
    {
        $this->paymentMethod = $paymentMethod;
        $this->order = $order;
        $this->transaction = $transaction;
        $this->setting = $setting;
        $this->credibancoApiController = $credibancoApiController;
    }


     /**
     * Show Voucher
     * @param  $request
     * @return view
     */
    public function voucherShow(Request $request){
       
        \Log::info('Module Icommercecredibanco: VoucherShow - '.time());

        try{
            $response = $this->credibancoApiController->getUpdateOrder($request);

            $data = ($response->getData())->data;
            $commerceName  = $this->setting->get('core::site-name');
            $tpl ='icommercecredibanco::frontend.voucher';
 
            return view($tpl,compact('data','commerceName'));
    
        }catch(\Exception $e){

            echo "Ooops, ha ocurrido un error al mostrar el voucher, comuniquese con el administrador";

            \Log::error('Module Icommercecredibanco - voucherShow: Message: '.$e->getMessage());
            \Log::error('Module Icommercecredibanco - voucherShow: Code: '.$e->getCode());

        }
       
    }

}