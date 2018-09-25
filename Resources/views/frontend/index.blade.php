@extends('layouts.master')

@section('title')
    Voucher - CrediBanco | @parent
@stop


@section('content')



<div class="icommercredibanco-body container mb-5">
    
    <div class="row">
        <div class="col">
            <h2 class="text-uppercase my-5 text-center">Voucher</h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-8 mx-auto">
           
            <table class="table table-striped">
               
                <tbody>
                    <tr>
                        <td>Comercio:</td>
                        <td>{{$commerceName}}</td>
                    </tr>
                  
                    <tr>
                        <td>Código único:</td>
                        <td>{{$transaction->commerceId}}</td>
                    </tr>

                    <tr>
                        <td>Fecha:</td>
                        <td>{{icommerce_formatDate($transaction->operationDate)}}</td>
                    </tr>

                    <tr>
                        <td>Nro de Terminal:</td>
                        <td>{{$transaction->terminalCode}}</td>
                    </tr>

                    <tr>
                        <td>Nro de Transacción:</td>
                        <td>{{$transaction->operationNumber}}</td>
                    </tr>

                    <tr>
                        <td>Moneda:</td>
                        <td>{{$transaction->currency}}</td>
                    </tr>

                    <tr>
                        <td>Total:</td>
                        <td>{{formatMoney($transaction->amount)}}</td>
                    </tr>

                    <tr>
                        <td>IVA:</td>
                        <td>{{$transaction->tax}}</td>
                    </tr>

                    <tr>
                        <td>Descripción:</td>
                        <td>{{$transaction->description}}</td>
                    </tr>

                    <tr>
                        <td>Respuesta:</td>
                        @if($transaction->authorizationResult==0)
                            <td>{{icommerce_get_Orderstatus()->get($transaction->order_status)}}</td>
                        @else
                            <td>{{$transaction->errorMessage}}</td>
                        @endif
                    </tr>

                    @if($transaction->authorizationResult==0)
                    <tr>
                        <td>Nro Autorización:</td>
                        <td>{{$transaction->authorizationCode}}</td>
                    </tr>
                    @endif
                    
                </tbody>

            </table>
            
            <div class="text-center">
                @if($transaction->type==1)
                    @if(isset($currentUser))

                        @if (!empty($order))
                            <a href="{{route('icommerce.orders.show',[$order->id])}}" class="btn btn-primary">Ver Orden</a>
                        @else
                            <a href="{{route('homepage')}}" class="btn btn-primary">Home</a>
                        @endif

                    @else

                        @if (!empty($order))
                            <a href="{{route('icommerce.orders.showorder',[$order->id, $order->key])}}" class="btn btn-primary">Ver Orden</a>
                        @else
                            <a href="{{route('homepage')}}" class="btn btn-primary">Home</a>
                        @endif

                    @endif

                @else
                    <a href="{{route('homepage')}}" class="btn btn-primary">Home</a>
                @endif
            </div>

        </div>
    </div>
    
</div>
 
@stop