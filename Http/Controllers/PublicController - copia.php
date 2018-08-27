<?php

namespace Modules\IcommerceCredibanco\Http\Controllers;

use Mockery\CountValidator\Exception;

use Modules\IcommerceCredibanco\Entities\Credibanco;
use Modules\IcommerceCredibanco\Entities\Configcredibanco;

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

    public function __construct(Setting $setting, Authentication $auth, UserRepository $user,  OrderRepository $order)
    {

        $this->setting = $setting;
        $this->auth = $auth;
        $this->user = $user;
        $this->order = $order;

        //$this->urlSandbox = "http://172.19.200.82:9080/vpos2/MM/transactionStart20.do";
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
        
        //if($request->session()->exists('orderID')) {

           
            //$orderID = session('orderID');
            $orderID = 1;
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
                    'shippingPostalCode' => isset($order->shipping_postcode) ? $order->shipping_postcode : ""
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

        /*
        }else{
            return redirect()->route('homepage');
        }
        */

    }


     /**
     * Response Page
     * @param Requests request
     * @return response
     */
    public function response(Requests $request)
    {

        Log::info('CrediBanco: Recibiendo Respuesta'.time());
        
        $arrayIn = array(
            'XMLRES' => $request->XMLRES,
            'DIGITALSIGN' => $request->DIGITALSIGN, 
            'SESSIONKEY' => $request->SESSIONKEY
        );

        $llavesesion = generateSessionKey();

        if($arrayIn['SESSIONKEY']==null || $arrayIn['XMLRES']==null || $arrayIn['DIGITALSIGN'] == null){

            echo "No se encuentra información Resultante";

            Log::info('CrediBanco Response - No se encuentra información Resultante - '.time());
            return false;
        }

        $config = new Configcredibanco();
        $config = $config->getData();

        $VI = $config->vec;

        if($config->url_action==0){
            $crediBanco->setUrlgate($this->urlSandbox);
            $pathKeys = "storage/app/keys/tests/";

        }else{
            $crediBanco->setUrlgate($this->urlProduction);
            $pathKeys = "storage/app/keys/";
        }

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


            /*

            $arrayOut['purchaseOperationNumber']

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
            
            */

            if( $arrayOut['authorizationResult'] == "00" ) {
            /* 00, indica que la transacción ha sido autorizada. Ejemplo errorCode: 00 errorMessage . Aprobada */
               
                // $success_process = icommerce_executePostOrder($referenceSale[0],1,$request);
                $msjTheme = "icommerce::email.success_order";
                //$msjSubject = trans('icommerce::common.emailSubject.complete')."- Order:".$order->id;
                $msjIntro = trans('icommerce::common.emailIntro.complete');

                //$ms = "La transacción ha sido autorizada, esta notificación será confirmada en nuestro sistema y se enviará según los paramentros seleccionados al momento de su compra.";
                
            }else{

                if( $arrayOut['authorizationResult'] == "01" ) {
                    /* 01, indica que la transacción ha sido rechazada por el VPOS. Ejemplo errorCode: 01 errorMessage: Negada, consulte al emisor de la tarjeta */
                    
                    //$success_process = icommerce_executePostOrder($referenceSale[0],6,$request);
                    $msjTheme = "icommerce::email.error_order";
                    //$msjSubject = trans('icommerce::common.emailSubject.failed')."- Order:".$order->id;
                    $msjIntro = trans('icommerce::common.emailIntro.failed');
                  
                    //$ms = "La Transacción ha sido rechazada, consulte al emisor de la tarjeta";

                }elseif( $arrayOut['authorizationResult'] == "05" ){
                    /* 05, indica que la transacción ha sido denegada en el Banco Emisor. Ejemplo errorCode: 02  ErrorMessage: Negada, puede ser tarjeta bloqueada o timeout */
                    
                    //$success_process = icommerce_executePostOrder($referenceSale[0],4,$request);
                    $msjTheme = "icommerce::email.error_order";
                     //$msjSubject = trans('icommerce::common.emailSubject.denied')."- Order:".$order->id;
                    $msjIntro = trans('icommerce::common.emailIntro.denied');

                    //$ms = "La transacción ha sido denegada en el Banco Emisor";

                }elseif( $arrayOut['authorizationResult'] == "08" ){
                    /* 08, indica que la transacción ha sido anulada. Ejemplo errorCode: 08 errorMessage: La transacción fué anulada automáticamente por CyberSource */
                    
                    //$success_process = icommerce_executePostOrder($referenceSale[0],2,$request);
                    $msjTheme = "icommerce::email.error_order";
                     //$msjSubject = trans('icommerce::common.emailSubject.canceled')."- Order:".$order->id;
                    $msjIntro = trans('icommerce::common.emailIntro.canceled');
                   
                    //$ms = "La transacción ha sido anulada";

                }elseif( $arrayOut['authorizationResult'] == "19" ){
                    /* 19, indica que la transacción ha sido autorizada, sujeta a evaluación. Ejemplo errorCode: 19 errorMessage: Transacción autorizada, sujeta a evaluación */
                    
                    //$success_process = icommerce_executePostOrder($referenceSale[0],10,$request);
                    $msjTheme = "icommerce::email.error_order";
                     //$msjSubject = trans('icommerce::common.emailSubject.pending')."- Order:".$order->id;
                    $msjIntro = trans('icommerce::common.emailIntro.pending');

                    //$ms = "La transacción ha sido autorizada más se encuentra sujeta a evaluación";
                }
            }
            
            /*
            $order = $this->order->find($referenceSale[0]);

            $content=[
                'order'=>$order,
                'products' => $products,
                'user' => $userFirstname
            ];

            icommerce_emailSend(['email_from'=>[$email_from],'theme' => $msjTheme,'email_to' => $request->email_buyer,'subject' => $msjSubject, 'sender'=>$sender,'data' => array('title' => $msjSubject,'intro'=> $msjIntro,'content'=>$content)]);
                        
            icommerce_emailSend(['email_from'=>[$email_from],'theme' => $msjTheme,'email_to' => $email_to,'subject' => $msjSubject, 'sender'=>$sender,'data' => array('title' => $msjSubject,'intro'=> $msjIntro,'content'=>$content)]);
            */

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

        Log::info('CrediBanco - Pag Confirmation: Recibiendo Respuesta'.time());
        //echo "pagina confirmation";

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

}