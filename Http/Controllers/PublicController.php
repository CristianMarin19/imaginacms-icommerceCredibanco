<?php

namespace Modules\IcommerceCredibanco\Http\Controllers;

use Mockery\CountValidator\Exception;

use Modules\IcommerceCredibanco\Entities\Credibanco;
use Modules\IcommerceCredibanco\Entities\Configcredibanco;
use Modules\IcommerceCredibanco\Repositories\TransactionRepository;

use Modules\Core\Http\Controllers\BasePublicController;
use Route;
use Session;

use Modules\User\Contracts\Authentication;
use Modules\User\Repositories\UserRepository;
use Modules\Icommerce\Repositories\CurrencyRepository;
use Modules\Icommerce\Repositories\ProductRepository;
use Modules\Icommerce\Repositories\OrderRepository;
use Modules\Icommerce\Repositories\Order_ProductRepository;
use Modules\Setting\Contracts\Setting;
use Illuminate\Http\Request as Requests;
use Illuminate\Support\Facades\Log;

use Modules\IcommerceCredibanco\Support\lib\mySoap\ConsultaTx;

use Modules\IcommerceCredibanco\Support\beans\VPOS_plugin_consulta;
use Modules\IcommerceCredibanco\Support\beans\VPOSConsulta;
use Modules\IcommerceCredibanco\Support\beans\VPOSConsultaResp;


class PublicController extends BasePublicController
{
  
    private $order;
    private $setting;
    private $user;
    protected $auth;
    
    protected $crediBanco;

    protected $urlSandbox;
    protected $urlProduction;
    protected $transaction;

    public function __construct(Setting $setting, Authentication $auth, UserRepository $user,  OrderRepository $order, TransactionRepository $transaction)
    {

        $this->setting = $setting;
        $this->auth = $auth;
        $this->user = $user;
        $this->order = $order;
        $this->transaction = $transaction;

        $this->urlSandbox = "https://testecommerce.credibanco.com/vpos2/MM/transactionStart20.do";
        $this->urlProduction = "https://ecommerce.credibanco.com/vpos2/MM/transactionStart20.do";

    }

    /**
     * Go to the payment
     * @param Requests request
     * @return redirect payment 
     */
    public function index(Requests $request)
    {
        
        if($request->session()->exists('orderID')) {

            $orderID = session('orderID');
            $order = $this->order->find($orderID);
            
            $restDescription = "Order:{$orderID} - {$order->email}";

            $config = new Configcredibanco();
            $config = $config->getData();

            try {

                $crediBanco = new Credibanco();

                 if($config->url_action==0){
                    $crediBanco->setUrlgate($this->urlSandbox);
                    $pathKeys = "storage/app/keys/tests/";
                }else{
                    $crediBanco->setUrlgate($this->urlProduction);
                    $pathKeys = "storage/app/keys/";
                }

                $acquirerId = "1";

                $crediBanco->setAcquirerId($acquirerId);
                $crediBanco->setMerchantid($config->merchantId);
                $crediBanco->setTerminalCode($config->nroTerminal);
                
                $VI = $config->vec;

                $gender = "M";
                $billingNationality = isset($order->payment_country) ? $order->payment_country : "";

                $orderBug = $orderID."-".time();

                $price = number_format($order->total, 0, '.', '');

                $arrayIn = [
                    'acquirerId' => $acquirerId, 
                    'commerceId' => $config->merchantId,
                    'purchaseTerminalCode' => $config->nroTerminal,
                    'purchaseOperationNumber' => $orderBug,
                    'purchaseAmount' => $price."00",
                    'purchaseCurrencyCode' => $this->currencyISO($config->currency),
                    'purchasePlanId' => "01",
                    'purchaseQuotaId' => "001",
                    'purchaseIpAddress' => $request->ip(),
                    'purchaseLanguage' => 'SP',
                    'billingCountry' => isset($order->payment_country) ? $order->payment_country : "",
                    'billingCity' => isset($order->payment_city) ? $order->payment_city : "",
                    'billingAddress' => isset($order->payment_address_1) ? $order->payment_address_1 : "",
                    'billingPhoneNumber' => empty($order->telephone) ?  "123456" : $order->telephone,
                    'billingCelPhoneNumber' => empty($order->telephone) ? "123456" : $order->telephone,
                    'billingFirstName' => isset($order->payment_firstname) ? $order->payment_firstname : "",
                    'billingLastName' => isset($order->payment_lastname) ? $order->payment_lastname : "",
                    'billingGender' => $gender,
                    'billingEmail' => $order->email,
                    'billingNationality' => $billingNationality,
                    'fingerPrint' => $orderBug,
                    'additionalObservations' => 'Compra realizada en Linea',
                    'shippingCountry' => isset($order->shipping_country) ? $order->shipping_country : "",
                    'shippingCity' => isset($order->shipping_city) ? $order->shipping_city : "",
                    'shippingAddress' => isset($order->shipping_address_1) ? $order->shipping_address_1 : "",
                    'shippingPostalCode' => isset($order->shipping_postcode) ? $order->shipping_postcode : "",
                    'reserved1' =>  '1'
                ];
                
                $xmlSalida = createXMLPHP5($arrayIn);

                $namePrivateSign = trim($config->privateSign);
                $namePublicCrypto = trim($config->publicCrypto);
               
                $firmaPrivateSend = \Storage::disk('local')->get($pathKeys.$namePrivateSign);
                $cryptoPublicSend = \Storage::disk('local')->get($pathKeys.$namePublicCrypto);

                //Genera la firma Digital
                $firmaDigital = BASE64URL_digital_generate($xmlSalida,$firmaPrivateSend);

                //Ya se genero el XML y se genera la llave de sesion
                $llavesesion = generateSessionKey();

                //Se cifra el XML con la llave generada
                $xmlCifrado = BASE64URL_symmetric_cipher($xmlSalida,$llavesesion,$VI);

                if(!$xmlCifrado) return null;

                //Se cifra la llave de sesion con la llave publica dada
                $llaveSesionCifrada = BASE64URLRSA_encrypt($llavesesion,$cryptoPublicSend);

                if(!$llaveSesionCifrada) return null;
                if(!$firmaDigital) return null;

                //agregar al formulario
                $arrayOut['SESSIONKEY'] = $llaveSesionCifrada;
                $arrayOut['XMLREQ'] = $xmlCifrado;
                $arrayOut['DIGITALSIGN'] = $firmaDigital;

                $crediBanco->setXmlReq($arrayOut['XMLREQ']);
                $crediBanco->setDigitalSign($arrayOut['DIGITALSIGN']);
                $crediBanco->setSessionKey($arrayOut['SESSIONKEY']);

                
                $crediBanco->executeRedirection();
               
            } catch (Exception $e) {
                    echo $e->getMessage();
            }

        
        }else{
            return redirect()->route('homepage');
        }
       
    }


     /**
     * Response Page
     * @param Requests request
     * @return response
     */
    public function response(Requests $request)
    {

        //Log::info('CrediBanco Response - Recibiendo Respuesta '.time());

        $arrayIn = array(
            'XMLRES' => $request->XMLRES,
            'DIGITALSIGN' => $request->DIGITALSIGN, 
            'SESSIONKEY' => $request->SESSIONKEY
        );

        $llavesesion = generateSessionKey();

        if($arrayIn['SESSIONKEY']==null || $arrayIn['XMLRES']==null || $arrayIn['DIGITALSIGN'] == null){

            echo "No se encuentra información Resultante";
            
            Log::info('CrediBanco Response - No se encuentra información Resultante - '.time());
            
            return redirect()->route('homepage');
        }

        $config = new Configcredibanco();
        $config = $config->getData();

        $VI = $config->vec;

        if($config->url_action==0)
            $pathKeys = "storage/app/keys/tests/";
        else
            $pathKeys = "storage/app/keys/";
        

        $email_from = $this->setting->get('icommerce::from-email');
        $email_to = explode(',',$this->setting->get('icommerce::form-emails'));
        $sender  = $this->setting->get('core::site-name');

        try {

            $namePrivateCrypto = trim($config->privateCrypto);
            $namePublicCrypto = trim($config->publicCrypto);

            $cryptoPrivateRecive = \Storage::disk('local')->get($pathKeys.$namePrivateCrypto);
            $cryptoPublicSend = \Storage::disk('local')->get($pathKeys.$namePublicCrypto);

            $llavesesion = BASE64URLRSA_decrypt($arrayIn['SESSIONKEY'],$cryptoPrivateRecive);

            $xmlDecifrado = BASE64URL_symmetric_decipher($arrayIn['XMLRES'],$llavesesion, $VI);

            $validation = BASE64URL_digital_verify($xmlDecifrado, $arrayIn['DIGITALSIGN'], $cryptoPublicSend);

            $arrayOut = parseXMLPHP5($xmlDecifrado);

            if($arrayOut["reserved1"]==1){

                $referenceSale = explode('-',$arrayOut['purchaseOperationNumber']);

                $order = $this->order->find($referenceSale[0]);

                $products=[];

                foreach ($order->products as $product) {
                    array_push($products,[
                        "title" => $product->title,
                        "sku" => $product->sku,
                        "quantity" => $product->pivot->quantity,
                        "price" => $product->pivot->price,
                        "total" => $product->pivot->total,
                    ]);
                }

                $userEmail = $order->email;
                $userFirstname = "{$order->first_name} {$order->last_name}";


                if( $arrayOut['authorizationResult'] == "00" ) {
                /* 00, indica que la transacción ha sido autorizada. Ejemplo errorCode: 00 errorMessage . Aprobada */
                   
                    $msjTheme = "icommerce::email.success_order";
                    $msjSubject = trans('icommerce::common.emailSubject.complete')."- Order:".$order->id;
                    $msjIntro = trans('icommerce::common.emailIntro.complete');
                    $state = 12;
                   
                }else{

                    if( $arrayOut['authorizationResult'] == "01" ) {
                        /* 01, indica que la transacción ha sido rechazada por el VPOS. Ejemplo errorCode: 01 errorMessage: Negada, consulte al emisor de la tarjeta */
                        
                        $msjTheme = "icommerce::email.error_order";
                        $msjSubject = trans('icommerce::common.emailSubject.failed')."- Order:".$order->id;
                        $msjIntro = trans('icommerce::common.emailIntro.failed');
                        $state = 6;
                       

                    }elseif( $arrayOut['authorizationResult'] == "05" ){
                        /* 05, indica que la transacción ha sido denegada en el Banco Emisor. Ejemplo errorCode: 02  ErrorMessage: Negada, puede ser tarjeta bloqueada o timeout */
                        
                        $msjTheme = "icommerce::email.error_order";
                        $msjSubject = trans('icommercecredibanco::common.emailSubject.denied')."- Order:".$order->id;
                        $msjIntro = trans('icommercecredibanco::common.emailIntro.denied');
                        $state = 4;
                        

                    }elseif( $arrayOut['authorizationResult'] == "08" ){
                        /* 08, indica que la transacción ha sido anulada. Ejemplo errorCode: 08 errorMessage: La transacción fué anulada automáticamente por CyberSource */
                        
                        $msjTheme = "icommerce::email.error_order";
                        $msjSubject = trans('icommercecredibanco::common.emailSubject.canceled')."- Order:".$order->id;
                        $msjIntro = trans('icommercecredibanco::common.emailIntro.canceled');
                        $state = 2;
                        

                    }elseif( $arrayOut['authorizationResult'] == "19" ){
                        /* 19, indica que la transacción ha sido autorizada, sujeta a evaluación. Ejemplo errorCode: 19 errorMessage: Transacción autorizada, sujeta a evaluación */
                        
                        $msjTheme = "icommerce::email.error_order";
                        $msjSubject = trans('icommerce::common.emailSubject.pending')."- Order:".$order->id;
                        $msjIntro = trans('icommerce::common.emailIntro.pending');
                        $state = 10;
                       
                    }

                }

                if(isset($state)){
                    $success_process = icommerce_executePostOrder($referenceSale[0],$state,$request);
                }

                $order = $this->order->find($referenceSale[0]);

                $content=[
                    'order'=>$order,
                    'products' => $products,
                    'user' => $userFirstname
                ];

                icommerce_emailSend(['email_from'=>[$email_from],'theme' => $msjTheme,'email_to' => $order->email,'subject' => $msjSubject, 'sender'=>$sender,'data' => array('title' => $msjSubject,'intro'=> $msjIntro,'content'=>$content)]);
                        
                icommerce_emailSend(['email_from'=>[$email_from],'theme' => $msjTheme,'email_to' => $email_to,'subject' => $msjSubject, 'sender'=>$sender,'data' => array('title' => $msjSubject,'intro'=> $msjIntro,'content'=>$content)]);

                $transaction = $this->generateVoucher($order,$arrayOut,1,$config);

                return $this->reedirectCustomerVoucher($order);
                

            }else{
                return redirect()->route('icredibanco.response',[
                    'XMLRES' => $request->XMLRES,
                    'DIGITALSIGN' => $request->DIGITALSIGN, 
                    'SESSIONKEY' => $request->SESSIONKEY
                ]);
            }

        }catch (Exception $e) {

            Log::info('Error en Exception'.time());
            //echo $e->getMessage();
        }

    }

    /**
     * Confirmation Page
     * @param Requests request
     * @return response
     */
    public function confirmation(Requests $request)
    {

      return redirect()->route('homepage');
       
    }


     /**
     * Get Iso
     * @param $currency
     * @return Code
     */
    public function currencyISO($currency){
        
        $currency = strtoupper($currency);

        if($currency=="COP")
            return 170;

        if($currency=="USD")
            return 840;

    }

     /**
     * Generate Voucher
     * @param  $order
     * @param  $arrayOut
     * @param  $type (1 = IcommerceCredibanco , 2 = Icredibanco)
     * @param  $config
     * @return transaction
     */
    public function generateVoucher($order,$arrayOut,$type,$config){
        
        $data = array(
           'order_id' => $order->id,
           'order_status' => $order->order_status,
           'type' => $type,
           'commerceId' => $arrayOut['commerceId'],
           'operationDate' => $order->updated_at,
           'terminalCode' => $arrayOut['purchaseTerminalCode'],
           'operationNumber' => $arrayOut['purchaseOperationNumber'],
            'currency' =>  $config->currency,
            'amount' => $order->total,
            'tax' => (!empty($order->tax_amount)?$order->tax_amount:0),
            'description' => $arrayOut['additionalObservations'],
            'errorCode' => $arrayOut['errorCode'],
            'errorMessage' => $arrayOut['errorMessage'],
            'authorizationCode' => isset($arrayOut['authorizationCode'])?$arrayOut['authorizationCode']:'',
            'authorizationResult' => $arrayOut['authorizationResult']
        );

       $transaction = $this->transaction->create($data);

       return $transaction;

    }

    /**
     * Reedirect To Voucher Customer After all Proccess
     * @param $order
     * @return reedirect
     */
    public function reedirectCustomerVoucher($order){

        $user = $this->auth->user();

        if (isset($user) && !empty($user))
            if (!empty($order))
                return redirect()->route('icommercecredibanco.voucher.show', [$order->id]);
            else
                return redirect()->route('homepage')
                  ->withSuccess(trans('icommerce::common.order_success'));
        else
            if (!empty($order))
                return redirect()->route('icommercecredibanco.voucher.showvoucher', [$order->id, $order->key]);
            else
                return redirect()->route('homepage')
                  ->withSuccess(trans('icommerce::common.order_success'));
  
    }

    /**
     * Show Voucher
     * @param  $request
     * @return view
     */
    public function voucherShow(Requests $request){
       
        if (!isset($request->key)) {
            $user = $this->auth->user();
            $order = $this->order->findByUser($request->id, $user->id);
          }else{
            $order = $this->order->findByKey($request->id, $request->key);
        }

        $transaction = $this->transaction->findByOrder($order->id);
        $commerceName  = $this->setting->get('core::site-name');

        $tpl ='icommercecredibanco::frontend.index';
        return view($tpl, compact('transaction','order','commerceName'));

    }



}