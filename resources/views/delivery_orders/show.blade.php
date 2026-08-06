@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{{ route('deliveryOrders.index') }}">{{ __('delivery_orders.delivery_orders') }}</a>
      </li>
      <li class="breadcrumb-item active">{{ __('delivery_orders.detail') }}</li>
    </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
                @include('flash::message')
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Details</strong>
                                 <a href="{{ route('deliveryOrders.index') }}" class="btn btn-light">Back</a>
                            </div>
                            <div class="card-body">
                                @include('delivery_orders.show_fields')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>{{ __('delivery_orders.delivery_order_detail') }}</strong>
                               <a class="pull-right" href="{{ route('deliveryOrders.detail', Crypt::encrypt($id)) }}"><i class="fa fa-plus-square fa-lg"></i></a>
                            </div>
                            <div class="card-body">
                               <table class="table table-striped table-bordered dataTable" width="100%" role="grid" style="width: 100%;">
                                   <thead>
                                       <tr role="row">
                                           <th>{{ __('delivery_orders.product') }}</th>
                                           <th>{{ __('delivery_orders.quantity') }}</th>
                                           <th>{{ __('delivery_orders.price') }}</th>
                                           <th>{{ __('invoices.total_price') }}</th>
                                           <th>{{ __('delivery_orders.remark') }}</th>
                                           <th>{{ __('delivery_orders.action') }}</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       @if(count($deliveryorderdetails) == 0)
                                           <tr class="odd">
                                               <td valign="top" colspan="10" class="dataTables_empty">No matching records found</td>
                                           </tr>
                                       @endif
                                       @foreach($deliveryorderdetails as $i=>$deliveryorderdetail)
                                           <tr class="{{ ($i+1) % 2 == 0 ? 'even' : 'odd' }}">
                                               <td>{{ $deliveryorderdetail['product']['name'] }}</td>
                                               <td>{{ $deliveryorderdetail['quantity'] }}</td>
                                               <td>{{ $deliveryorderdetail['price'] }}</td>
                                               <td>{{ $deliveryorderdetail['totalprice'] }}</td>
                                               <td>{{ $deliveryorderdetail['remark'] }}</td>
                                               <td>
                                               {!! Form::open(['route' => ['deliveryOrders.deletedetail', Crypt::encrypt($deliveryorderdetail['id'])], 'method' => 'delete']) !!}
                                                   <div class='btn-group'>
                                                       {!! Form::button('<i class="fa fa-trash"></i>', [
                                                           'type' => 'submit',
                                                           'class' => 'btn btn-ghost-danger',
                                                           'onclick' => "return confirm('Are you sure to delete the Delivery Order Detail?')"
                                                       ]) !!}
                                                   </div>
                                               {!! Form::close() !!}
                                               </td>
                                           </tr>
                                       @endforeach
                                   </tbody>
                               </table>

                            </div>
                        </div>
                    </div>
                </div>
         </div>
   </div>
@endsection

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[1].click();
            }
        });
    </script>
@endpush
