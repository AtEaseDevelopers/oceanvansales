@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('deliveryOrders.index') !!}">{{ __('delivery_orders.delivery_orders') }}</a>
          </li>
          <li class="breadcrumb-item active">{{ __('delivery_orders.edit') }}</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>{{ __('delivery_orders.edit_delivery_order') }}</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($deliveryOrder, ['route' => ['deliveryOrders.update', encrypt($deliveryOrder->id)], 'method' => 'patch', 'files' => true]) !!}

                              @include('delivery_orders.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection
