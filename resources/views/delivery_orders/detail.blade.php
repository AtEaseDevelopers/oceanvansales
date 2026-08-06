@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('deliveryOrders.index') !!}">{{ __('delivery_orders.delivery_orders') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('delivery_orders.detail') }}</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Delivery Order Detail</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => ['deliveryOrders.adddetail', Crypt::encrypt($id)]]) !!}

                                    <!-- Delivery Order Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('deliveryorder_id', __('delivery_orders.do_no') . ':') !!}<span class="asterisk"> *</span>
                                        {!! Form::text('deliveryorder_id_display', $id, ['class' => 'form-control', 'disabled']) !!}
                                    </div>

                                    <!-- Product Id Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('product_id', __('delivery_orders.product') . ':') !!}<span class="asterisk"> *</span>
                                        {!! Form::select('product_id', $productItems, null, ['class' => 'form-control', 'placeholder' => 'Pick a Product...','autofocus']) !!}
                                    </div>

                                    <!-- Quantity Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('quantity', __('delivery_orders.quantity') . ':') !!}<span class="asterisk"> *</span>
                                        {!! Form::number('quantity', null, ['class' => 'form-control','min' => 0,'step' => 1]) !!}
                                    </div>

                                    <!-- Price Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('price', __('delivery_orders.price') . ':') !!}<span class="asterisk"> *</span>
                                        {!! Form::text('price', null, ['class' => 'form-control','min' => 0,'step' => 0.01]) !!}
                                    </div>

                                    <!-- Remark Field -->
                                    <div class="form-group col-sm-6">
                                        {!! Form::label('remark', __('delivery_orders.remark') . ':') !!}
                                        {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255]) !!}
                                    </div>

                                    <!-- Submit Field -->
                                    <div class="form-group col-sm-12">
                                        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                                        <a href="{{ route('deliveryOrders.show', Crypt::encrypt($id)) }}" class="btn btn-secondary">Cancel</a>
                                    </div>

                                    @push('scripts')
                                        <script>
                                            $(document).keyup(function(e) {
                                                if (e.key === "Escape") {
                                                    $('form a.btn-secondary')[0].click();
                                                }
                                            });
                                            $(document).ready(function () {
                                                HideLoad();
                                            });
                                        </script>
                                    @endpush

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
