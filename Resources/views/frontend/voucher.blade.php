@extends('layouts.master')

@section('title')
    Voucher - CrediBanco | @parent
@stop


@section('content')


<div class="icommercredibanco-body container mb-5">
    
    <div class="row">
        <div class="col">
            <h2 class="text-uppercase my-5 text-center">Voucher Credibanco</h2>
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
                        <td>Orden Referencia del Comercio:</td>
                        <td>{{$data->orderRefCommerce}}</td>
                    </tr>

                    <tr>
                        <td>Orden Status del Comercio:</td>
                        <td>{{$data->orderStatus->title}}</td>
                    </tr>

                    <tr>
                        <td>Fecha:</td>
                        <td>{{format_date($data->order->created_at,"%d-%m-%Y")}}</td>
                    </tr>

                    <tr>
                        <td>Nro de Terminal:</td>
                        <td>{{$data->dataCredibanco->terminalId}}</td>
                    </tr>

                    <tr>
                        <td>Nro Orden CrediBanco:</td>
                        <td>{{$data->orderIdCredibanco}}</td>
                    </tr>

                    <tr>
                        <td>Moneda:</td>
                        <td>{{$data->order->currency_code}}</td>
                    </tr>

                    <tr>
                        <td>Total:</td>
                        <td>{{formatMoney($data->order->total)}}</td>
                    </tr>

                    <tr>
                        <td>IVA:</td>
                        <td>{{$data->order->tax_amount?$order->tax_amount:0}}</td>
                    </tr>
                    <tr>
                        <td>Respuesta Order Status Code - Credibanco:</td>
                        <td>{{$data->dataCredibanco->orderStatus}}</td>
                    </tr>
                    <tr>
                        <td>Respuesta Action Code - Credibanco:</td>
                        <td>{{$data->dataCredibanco->actionCode}}</td>
                    </tr>
                    <tr>
                        <td>Respuesta Action Code Description - Credibanco:</td>
                        @if(!empty($data->dataCredibanco->actionCodeDescription))
                            <td>{{$data->dataCredibanco->actionCodeDescription}}</td>
                        @else
                            <td>{{$data->dataCredibanco->errorMessage}}</td>
                        @endif
                    </tr>
                    
                </tbody>
              
            </table>
            
            
                @if(isset($currentUser))
                    @if (!empty($data->order))
                    <div class="text-center">
                        <a href="{{route(locale().'.icommerce.orders.show',[$data->order->id])}}" class="btn btn-primary">Ver Orden</a>
                    </div>
                    @endif
                @else
                    @if (!empty($data->order))
                        <a href="{{route(locale().'.icommerce.order.showorder',[$order->id, $order->key])}}" class="btn btn-primary">Ver Orden</a>
                    @endif
                @endif

                <div class="text-center my-2">
                    <a href="{{route('homepage')}}" class="btn btn-primary">Home</a>
                </div>

            </div>

        </div>
    </div>
    
</div>
 
@stop