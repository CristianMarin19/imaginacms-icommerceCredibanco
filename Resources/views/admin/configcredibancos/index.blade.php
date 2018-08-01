@php
	$configuration = icommercecredibanco_get_configuration();
	$options = array('required' =>'required');
	
	
	$cStatus = $configuration->status;
		
	$formID = uniqid("form_id");

@endphp


{!! Form::open(['route' => ['admin.icommercecredibanco.configcredibanco.update'], 'method' => 'put','name' => $formID]) !!}

	<div class="col-xs-12 col-sm-9">

		{!! Form::normalInput('description','*'.trans('icommercecredibanco::configcredibancos.table.description'), $errors,$configuration,$options) !!}

		{!! Form::normalInput('merchantId', '*'.trans('icommercecredibanco::configcredibancos.table.merchantId'), $errors,$configuration,$options) !!}

		{!! Form::normalInput('nroTerminal', '*'.trans('icommercecredibanco::configcredibancos.table.nroTerminal'), $errors,$configuration,$options) !!}

		{!! Form::normalInput('vec', '*'.trans('icommercecredibanco::configcredibancos.table.vec'), $errors,$configuration,$options) !!}

		<div class="form-group">
	        <label for="url_action">*Mode</label>
	        <select class="form-control" id="url_action" name="url_action" required>
	        	<option value="0" @if(!empty($configuration) && $configuration->url_action==0) selected @endif>SANDBOX</option>
	        	<option value="1" @if(!empty($configuration) && $configuration->url_action==1) selected @endif>PRODUCTION</option>
	        </select>
	    </div>

	    {!! Form::normalInput('currency', '*'.trans('icommercecredibanco::configcredibancos.table.currency'), $errors,$configuration,$options) !!}

		<div class="form-group">
		    <div>
			    <label class="checkbox-inline">
			    	<input name="status" type="checkbox" @if($cStatus==1) checked @endif>{{trans('icommercecredibanco::configcredibancos.table.activate')}}
			    </label>
			</div>   
		</div>

	</div>

	<div class="col-sm-3">

		@include('icommercecredibanco::admin.configcredibancos.partials.featured-img',['crop' => 0,'name' => 'mainimage','action' => 'create'])

	</div>
	   	
		
	<div class="clearfix"></div>   

	<div class="box-footer">
	   <button type="submit" class="btn btn-primary btn-flat">{{ trans('icommercecredibanco::configcredibancos.button.save configuration') }}</button>
	</div>

{!! Form::close() !!}