<?php

namespace Modules\Icommercecredibanco\Http\Controllers\Api;

// Requests & Response
use Illuminate\Http\Request;
use Modules\Icommercecredibanco\Http\Requests\InitRequest;

// Base Api
use Modules\Icommerce\Http\Controllers\Api\OrderApiController;
use Modules\Icommerce\Http\Controllers\Api\TransactionApiController;
use Modules\Ihelpers\Http\Controllers\Api\BaseApiController;

// Repositories
use Modules\Icommercecredibanco\Repositories\IcommerceCredibancoRepository;

use Modules\Icommerce\Repositories\PaymentMethodRepository;
use Modules\Icommerce\Repositories\TransactionRepository;
use Modules\Icommerce\Repositories\OrderRepository;

class IcommerceCredibancoApiController extends BaseApiController
{

    private $icommercecredibanco;
    private $paymentMethod;
    private $order;
    private $orderController;
    private $transaction;
    private $transactionController;

    protected $urlSandbox;
    protected $urlProduction;

    protected $orderStatusSandbox;
    protected $orderStatusProduction;

    public function __construct(

        IcommerceCredibancoRepository $icommercecredibanco,
        PaymentMethodRepository $paymentMethod,
        OrderRepository $order,
        OrderApiController $orderController,
        TransactionRepository $transaction,
        TransactionApiController $transactionController
    ){

        $this->icommercecredibanco = $icommercecredibanco;
        $this->paymentMethod = $paymentMethod;
        $this->order = $order;
        $this->orderController = $orderController;
        $this->transaction = $transaction;
        $this->transactionController = $transactionController;

        $this->urlSandbox = "https://ecouat.credibanco.com/payment/rest/register.do";
        $this->urlProduction = "https://eco.credibanco.com/payment/rest/register.do";

        $this->orderStatusSandbox = "https://ecouat.credibanco.com/payment/rest/getOrderStatusExtended.do";
        $this->orderStatusProduction = "https://eco.credibanco.com/payment/rest/getOrderStatusExtended.do";

    }
    
    /**
     * Init data
     * @param Requests request
     * @param Requests orderid
     * @return route
     */
    public function init(Request $request){

        try {

            $data = $request->all();
           
            $this->validateRequestApi(new InitRequest($data));

            $orderID = $request->orderID;
            \Log::info('Module Icommercecredibanco: Init-ID:'.$orderID);

            $paymentMethod = $this->getPaymentMethodConfiguration();

            // Order
            $order = $this->order->find($orderID);
            $statusOrder = 1; // Processing

            // Create Transaction
            $transaction = $this->validateResponseApi(
                $this->transactionController->create(new Request( ["attributes" => [
                    'order_id' => $order->id,
                    'payment_method_id' => $paymentMethod->id,
                    'amount' => $order->total,
                    'status' => $statusOrder
                ]]))
            );

            // Response Credibanco
            $response = $this->registerOrderCredibanco($paymentMethod,$order,$transaction); 
            $data = json_decode($response->getBody());

            if(isset($data->orderId)){
                
                $transactionUp = $this->validateResponseApi(
                    $this->transactionController->update($transaction->id,new Request([
                        'external_code' => $data->orderId
                    ]))
                );
                

                $redirectRoute = icommercecredibanco_processUrl($data->formUrl);

            }else{
                \Log::info('Module Icommercecredibanco: Credibanco Response ErrorCode: '.$data->errorCode);
                \Log::info('Module Icommercecredibanco: Credibanco Response ErrorMessage: '.$data->errorMessage);
                throw new \Exception($data->errorMessage, 204);
            }
        

            // Response
            $response = [ 'data' => [
                "redirectRoute" => $redirectRoute
            ]];

        } catch (\Exception $e) {
            //Message Error
            $status = 500;
            $response = [
              'errors' => $e->getMessage()
            ];
        }

        return response()->json($response, $status ?? 200);
        
    }

    /**
     * GetOrderStatus Api Method
     * @param Requests request
     * @return route 
     */
    public function getUpdateOrder(Request $request){

        try {

            $orderID = $request->id;
            $transactionID = $request->tid;

            \Log::info('Module Icommercecredibanco: GetUpdateOrder - orderID: '.$orderID);
            \Log::info('Module Icommercecredibanco: GetUpdateOrder - transactionID: '.$transactionID);

            $paymentMethod = $this->getPaymentMethodConfiguration();

            $order = $this->order->find($orderID);
            $transaction = $this->transaction->find($transactionID);

            // Get order data Credibanco
            $response = $this->getOrderStatusExtendedCredibanco($paymentMethod,$order,$transaction); 
            $data = json_decode($response->getBody());

            // Update Order
            $orderUP = $this->updateOrder($data->orderStatus,$paymentMethod,$order,$transaction);

            // Response
            $response = [ 'data' => [
                "dataCredibanco" => $data,
                "order" => $orderUP,
                "orderIdCredibanco" => $transaction->external_code,
                "orderRefCommerce" => $this->getOrderRefCommerce($orderUP,$transaction),
                "orderStatus" => $orderUP->status
            ]];

        } catch (\Exception $e) {

            //Message Error
            $status = 500;

            $response = [
                'errors' => $e->getMessage(),
                'code' => $e->getCode()
            ];

           //Log Error
           \Log::error('Module Icommercecredibanco - getUpdateOrder: Message: '.$e->getMessage());
           \Log::error('Module Icommercecredibanco - getUpdateOrder: '.$e->getCode());

        }

       
        return response()->json($response, $status ?? 200);

    }

    /**
     * CREDIBANCO API - Register Orderand GET URL
     * @param
     * @return collection
     */
    public function registerOrderCredibanco($paymentMethod,$order,$transaction){

        $orderRefCommerce = $this->getOrderRefCommerce($order,$transaction);

        $jParams = array(
            "installments" => 1, //Número de cuotas
            "IVA.amount" => 0 // IVA amount en unidades mínimas de divisa
        );

        $params = array(
            "userName" => $paymentMethod->options->user,
            "password" => $paymentMethod->options->password,
            "orderNumber" => $orderRefCommerce,
            "amount" => icommercecredibanco_formatTotal($order->total),
            "currency" => icommercecredibanco_currencyISO($order->currency_code),
            "returnUrl" => route("icommercecredibanco.voucher.show",[$order->id,$transaction->id]),
            "jsonParams" => json_encode($jParams)
        );

        if($paymentMethod->options->mode=="sandbox")
            $endPoint = $this->urlSandbox;
        else
            $endPoint = $this->urlProduction;

        // SEND DATA CREDIBANCO AND GET URL
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $endPoint,[
            'form_params' => $params
            ]
        );
       
        \Log::info('Module Icommercecredibanco: Credibanco Response Code: '.$response->getStatusCode());

        return $response;

    }

     /**
     * CREDIBANCO API - Get Order Extended
     * @param
     * @return collection
     */
    public function getOrderStatusExtendedCredibanco($paymentMethod,$order,$transaction){

        \Log::info('Module Icommercecredibanco: GetOrderStatusExtented');

        $orderRefCommerce = $this->getOrderRefCommerce($order,$transaction);

        $params = array(
            "userName" => $paymentMethod->options->user,
            "password" => $paymentMethod->options->password,
            "orderId" => $transaction->external_code,
            "orderNumber" => $orderRefCommerce
        );

        if($paymentMethod->options->mode=="sandbox")
            $endPoint = $this->orderStatusSandbox;
        else
            $endPoint = $this->orderStatusProduction;

        // SEND DATA CREDIBANCO AND GET URL
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $endPoint,[
            'form_params' => $params
            ]
        );
       
        \Log::info('Module Icommercecredibanco: Credibanco Response Code: '.$response->getStatusCode());

        return $response;

    }

    /**
     * Update Order Icommerce
     * @param orderStatus Credibanco
     * @return 
     */
    public function updateOrder($orderStatus,$paymentMethod,$order,$transaction){

        \Log::info('Module Icommercecredibanco: UpdateOrder - orderID: '.$order->id);

        if($orderStatus==0){
            //Pedido registrado pero no pagado;
            $newstatusOrder = 1; //Processing

        }else if($orderStatus == 1){
            //El importe preautorizado está retenido (para los pagos en dos etapas);
            $newstatusOrder = 1; //Processing

        }else if($orderStatus == 2){
            //Se ha realizado la autorización completa del importe del pedido;
            $newstatusOrder = 13; //Processed

        }else if($orderStatus == 3){
            //autorización denegada;
            $newstatusOrder = 5; //denied

        }else if($orderStatus == 4){
            //Se ha realizado la reverso de esta transacción;
            $newstatusOrder = 6; //canceledreversal
            
        }else if($orderStatus == 5){
            //Autorización iniciada a través de ACS del banco emisor;
            $newstatusOrder = 1; //Processing

        }else if($orderStatus == 6){
            //Autorización denegada.
            $newstatusOrder = 6; //denied

        }else{
            $newstatusOrder = 7; // Status Order Failed
        }

        \Log::info('Module Icommercecredibanco: New Status Order: '.$newstatusOrder);


        // Update Transaction
        $transaction = $this->validateResponseApi(
            $this->transactionController->update($transaction->id,new Request([
                'status' => $newstatusOrder
            ]))
        );
        \Log::info('Module Icommercecredibanco: Transaction Updated');

        // Update Order
        /*
        $orderUP = $this->validateResponseApi(
            $this->orderController->update($order->id,new Request(
              ["attributes" =>[
                'order_id' => $order->id,
                'status_id' => $newstatusOrder
              ]
              ]))
        ); 
        */
        
        $order->update(['status_id' => $newstatusOrder]);

        \Log::info('Module Icommercecredibanco: Order Updated');

        return $order;

    }

     /**
     * Get Payment Method Configuration
     * @param
     * @return collection
     */
    public function getPaymentMethodConfiguration(){
        $paymentName = config('asgard.icommercecredibanco.config.paymentName');
        $attribute = array('name' => $paymentName);
        $paymentMethod = $this->paymentMethod->findByAttributes($attribute); 
        
        return $paymentMethod;
    }

    /**
     * Get Order Reference Commerce
     * @param
     * @return collection
     */
    public function getOrderRefCommerce($order,$transaction){

        $orderRefCommerce = $order->id."-".$transaction->id;
        return $orderRefCommerce;

    }

}