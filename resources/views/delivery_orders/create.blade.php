@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('deliveryOrders.index') !!}">{{ __('delivery_orders.delivery_orders') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('delivery_orders.create_delivery_order') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>{{ __('delivery_orders.create_delivery_order') }}</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'deliveryOrders.store', 'files' => true]) !!}

                                   @include('delivery_orders.fields')

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
