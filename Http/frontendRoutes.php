<?php

use Illuminate\Routing\Router;

    $router->group(['prefix'=>'icommercecredibanco'],function (Router $router){
        $locale = LaravelLocalization::setLocale() ?: App::getLocale();

        $router->get('/', [
            'as' => 'icommercecredibanco',
            'uses' => 'PublicController@index',
        ]);

        $router->post('/response', [
            'as' => 'icommercecredibanco.response',
            'uses' => 'PublicController@response',
        ]);

        $router->get('/confirmation', [
            'as' => 'icommercecredibanco.confirmation',
            'uses' => 'PublicController@confirmation',
        ]);

        $router->get('voucher/order/{id}', [
            'as' => 'icommercecredibanco.voucher.show',
            'uses' => 'PublicController@voucherShow',
            'middleware' => 'logged.in'
        ]);

        $router->get('voucher/order/{id}/{key}', [
            'as' => 'icommercecredibanco.voucher.showvoucher',
            'uses' => 'PublicController@voucherShow',
            'middleware' => 'auth.guest',
        ]);

    });