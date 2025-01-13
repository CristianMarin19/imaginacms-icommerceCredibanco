<?php

return [
  'single' => 'Credibanco',
  'description' => 'La descripcion del Modulo',
  'iaDescription' => 'Credibanco te permite pagar de manera rápida y segura usando tu tarjeta de crédito, débito, y otros métodos como PSE y pagos en efectivo a través de puntos de recaudo.',
  'list resource' => 'List icommercecredibancos',
  'create resource' => 'Create icommercecredibancos',
  'edit resource' => 'Edit icommercecredibancos',
  'destroy resource' => 'Destroy icommercecredibancos',
  'title' => [
    'icommercecredibancos' => 'IcommerceCredibanco',
    'create icommercecredibanco' => 'Create a icommercecredibanco',
    'edit icommercecredibanco' => 'Edit a icommercecredibanco',
  ],
  'button' => [
    'create icommercecredibanco' => 'Create a icommercecredibanco',
  ],
  'table' => [
    'user' => 'User',
    'password' => 'Password',
    'merchantId' => 'Merchant Id',
    'nroTerminal' => 'Nro Terminal',
    'vec' => 'Vector',
    'mode' => 'Mode',
    'privateCrypto' => 'Private Cryto (site.cifrado.privada.txt)',
    'privateSign' => 'Private Sign (site.firma.privada.txt)',
    'publicCrypto' => 'Public Crypto (LLAVE.VPOS.CRB.CRYPTO.1024.X509.txt)',
    'publicSign' => 'Public Sign (LLAVE.VPOS.CRB.SIGN.1024.X509.txt)',
  ],
  'form' => [
  ],
  'formFields' => [
    'maximumAmount' => 'Monto Máximo Permitido',
    'excludedUsersForMaximumAmount' => 'Usuarios excluidos del monto máximo',
  ],
  'messages' => [
    'info' => 'Remember to generate the keys and store them safely on the site',
  ],
  'validation' => [
    'maximumAmount' => 'El Monto de la orden excede el máximo permitido (:maximumAmount) para este método de pago',
  ],
  'statusTransaction' => [
    'approved' => 'Aprobada',
    'denied' => 'Negada',
  ],
];
