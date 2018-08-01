<?php

use Illuminate\Routing\Router;
/** @var Router $router */

$router->group(['prefix' =>'/icommercecredibanco'], function (Router $router) {
    $router->bind('configcredibanco', function ($id) {
        return app('Modules\IcommerceCredibanco\Repositories\ConfigcredibancoRepository')->find($id);
    });
    $router->get('configcredibancos', [
        'as' => 'admin.icommercecredibanco.configcredibanco.index',
        'uses' => 'ConfigcredibancoController@index',
        'middleware' => 'can:icommercecredibanco.configcredibancos.index'
    ]);
    $router->get('configcredibancos/create', [
        'as' => 'admin.icommercecredibanco.configcredibanco.create',
        'uses' => 'ConfigcredibancoController@create',
        'middleware' => 'can:icommercecredibanco.configcredibancos.create'
    ]);
    $router->post('configcredibancos', [
        'as' => 'admin.icommercecredibanco.configcredibanco.store',
        'uses' => 'ConfigcredibancoController@store',
        'middleware' => 'can:icommercecredibanco.configcredibancos.create'
    ]);
    $router->get('configcredibancos/{configcredibanco}/edit', [
        'as' => 'admin.icommercecredibanco.configcredibanco.edit',
        'uses' => 'ConfigcredibancoController@edit',
        'middleware' => 'can:icommercecredibanco.configcredibancos.edit'
    ]);
    $router->put('configcredibancos/', [
        'as' => 'admin.icommercecredibanco.configcredibanco.update',
        'uses' => 'ConfigcredibancoController@update',
        'middleware' => 'can:icommercecredibanco.configcredibancos.edit'
    ]);
    $router->delete('configcredibancos/{configcredibanco}', [
        'as' => 'admin.icommercecredibanco.configcredibanco.destroy',
        'uses' => 'ConfigcredibancoController@destroy',
        'middleware' => 'can:icommercecredibanco.configcredibancos.destroy'
    ]);
// append

});
