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

    });