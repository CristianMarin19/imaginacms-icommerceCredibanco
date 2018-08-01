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

        $this->urlSandbox = "http://172.19.200.82:9080/vpos2/MM/transactionStart20.do";
        $this->urlProduction = "https://ecommerce.credibanco.com/vpos2/MM/transactionStart20.do";

        
        /*

        $merchantId = 1303 - 1024;
        $nroTerminal = 00004036;

        $wsdl = "http://172.19.44.41:9081/vpos2/services/VPOS2RESULTTXSOAP/META-INF/VPOS2RESULTTXSOAP.wsdl";

        // SEND
        $cifradollavePublica1 = ;
        $cifradollavePrivada2 = ;

        $firmaPrivateSend
        $cryptoPublicSend

        // RECIVE
        $firmallavePublica3 = ;
        $firmallavePrivada4 = ;

        $cifradoCredibanco5 = ;
        $firmaCredibanco6 = ;

        acquirerId = 138 - OJO si son strings

        define('CRYPTO_PUBLIC_SEND', './certificates/LLAVE.VPOS.CRB.CRYPTO.1024.X509.txt');
        define('FIRMA_PRIVATE_SEND', './certificates/sasmon.firma.privada.pem');
        define('SIGNATURE_PUBLIC_RECIVE', './certificates/LLAVE.VPOS.CRB.SIGN.1024.X509.txt');
        define('CIFRADO_PRIVATE_RECIVE', './certificates/sasmon.cifrado.privada.pem');
        define('VI','65fa43b0a300acd0');
        */

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
            $orderID = 145;
            $order = $this->order->find($orderID);

            $restDescription = "Order:{$orderID} - {$order->email}";

            $config = new Configcredibanco();
            $config = $config->getData();

            try {

                $crediBanco = new Credibanco();

                 if($config->url_action==0)
                    $crediBanco->setUrlgate($this->urlSandbox);
                else
                    $crediBanco->setUrlgate($this->urlProduction);

                $acquirerId = "1";

                $crediBanco->setAcquirerId($acquirerId);
                $crediBanco->setMerchantid($config->merchantId);
                $crediBanco->setTerminalCode($config->nroTerminal);
                
                /*
                    81a5cc5e68e0d612 - Youtube
                    2cb3a5f8b93dbb42 - Documentacion
                    65fa43b0a300acd0 - Imagina
                */
                $VI = $config->vec;

                $arrayIn = [
                    'acquirerId' => $acquirerId, 
                    'commerceId' => $config->merchantId,
                    'purchaseTerminalCode' => $config->nroTerminal,
                    'purchaseOperationNumber' => $orderID,
                    'purchaseAmount' => 10,
                    'purchaseCurrencyCode' => $this->currencyISO($config->currency),
                    'purchasePlanId' => "01",
                    'purchaseQuotaId' => "001",
                    'purchaseIpAddress' => $request->ip(),
                    'purchaseLanguage' => 'SP',
                    'fingerPrint' => $orderID,
                    'additionalObservations' => 'Compra realizada en Linea'
                ];
                
                $xmlSalida = createXMLPHP5($arrayIn);

                $firmaPrivateSend = "";
                $firmaPrivateSend = \Storage::disk('local')->get('Modules/IcommerceCredibanco/Support/certificates/pruebas/sasmon.firma.privada.pem');

                $cryptoPublicSend = "";
                $cryptoPublicSend = \Storage::disk('local')->get('Modules/IcommerceCredibanco/Support/certificates/pruebas/LLAVE.VPOS.CRB.CRYPTO.1024.X509.txt');

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
                /*
                $arrayIn = [
                    'acquirerId' => "1", 
                    'commerceId' => $config->merchantId,
                    'purchaseTerminalCode' => $config->nroTerminal,
                    'purchaseOperationNumber' => $orderID,
                    'purchaseAmount' => 10,
                    'purchaseCurrencyCode' => $this->currencyISO($config->currency),
                    'purchasePlanId' => "01",
                    'purchaseQuotaId' => "001",
                    'purchaseIpAddress' => $request->ip(),
                    'purchaseLanguage' => 'SP',
                    'billingCountry' => Tools::safeOutput( $billing_address->country->iso_code ),
                    'billingCity' => remove_accents(html_entity_decode(Tools::safeOutput( $billing_address->city ))),
                    'billingAddress' => remove_accents(html_entity_decode(Tools::safeOutput( $billing_address->address1 ))),
                    'billingPhoneNumber' => Tools::safeOutput( $billing_address->phone ),
                    'billingCelPhoneNumber' => Tools::safeOutput( $billing_address->phone_mobile ),
                    'billingFirstName' => remove_accents(html_entity_decode(Tools::safeOutput( $customer->firstname ))),
                    'billingLastName' => remove_accents(html_entity_decode(Tools::safeOutput( $customer->lastname ))),
                    'billingGender' => $gender,
                    'billingEmail' => Tools::safeOutput( $customer->email ),
                    'billingNationality' => Tools::safeOutput( $billing_address->country->iso_code ),
                    'fingerPrint' => $orderID,
                    'additionalObservations' => 'Compra realizada en Linea',
                    'shippingCountry' => Tools::safeOutput( $billing_address->country->iso_code ),
                    'shippingCity' => remove_accents(html_entity_decode(Tools::safeOutput( $billing_address->city ))),
                    'shippingAddress' => remove_accents(html_entity_decode(Tools::safeOutput( $billing_address->address1 ))),
                    'shippingPostalCode' => Tools::safeOutput( $billing_address->postcode ), 
                ];
                */


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
     * Confirmation Page
     * @param Requests request
     * @return response
     */
    public function ok(Requests $request)
    {


        dd($request);

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