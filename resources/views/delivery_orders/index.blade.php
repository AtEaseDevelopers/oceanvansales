@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('delivery_orders.delivery_orders') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('delivery_orders.delivery_orders') }}
                             <a class="pull-right" href="{{ route('deliveryOrders.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right text-danger pr-2" id="massdelete" href="#" alt="Mass delete"><i class="fa fa-trash fa-lg"></i></a>
                             <a class="pull-right text-success pr-2" id="massactive" href="#" alt="Mass active"><i class="fa fa-check fa-lg"></i></a>
                             <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="combineconvert" title="Combine the selected delivery orders into one invoice for ANEKA INTERTRADE MARKETING SDN BHD">
                                <i class="fa fa-object-group"></i> Combine and Convert
                             </button>
                             <button type="button" class="btn btn-success btn-sm pull-right mr-2" id="convert" title="Convert each selected delivery order into its own invoice">
                                <i class="fa fa-exchange"></i> Convert
                             </button>
                         </div>
                         <div class="card-body">
                             @include('delivery_orders.table')
                              <div class="pull-right mr-3">

                              </div>
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
                $('.card .card-header a')[0].click();
            }
        });

        $(document).on("click", "#massdelete", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to delete 1 row!"
            }else{
                m = "Confirm to delete " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Delete',
                content: m,
                buttons: {
                    Yes: function() {
                        massdelete(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });

        $(document).on("click", "#massactive", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to update 1 row"
            }else{
                m = "Confirm to update " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Update',
                content: m,
                buttons: {
                    Completed: function() {
                        massupdatestatus(window.checkboxid,1);
                    },
                    Cancelled: function() {
                        massupdatestatus(window.checkboxid,2);
                    },
                    somethingElse: {
                        text: 'Cancel',
                        btnClass: 'btn-gray',
                        keys: ['enter', 'shift']
                    }
                }
            });

        });
        function massdelete(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/deliveryOrders/massdestroy",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Delete Successfully',response+' row(s) had been deleted.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
        function massupdatestatus(ids,status){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/massupdatestatus') }}",
                type:"POST",
                data:{
                ids: ids,
                status: status
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Update Successfully',response+' row(s) had been updated.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        $(document).on("click", "#convert", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one delivery order');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to convert 1 delivery order into its own invoice?"
            }else{
                m = "Confirm to convert " + window.checkboxid.length + " delivery orders, each into its own invoice?"
            }
            $.confirm({
                title: 'Convert',
                content: m,
                buttons: {
                    Yes: function() {
                        convertDo(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });
        function convertDo(ids){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/convert') }}",
                type:"POST",
                data:{
                    ids: ids,
                    _token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Converted', response.message);
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Please contact your administrator', error.responseJSON?.message || 'Failed to convert delivery orders');
                }
            });
        }

        $(document).on("click", "#combineconvert", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one delivery order');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to combine 1 delivery order into an invoice for ANEKA INTERTRADE MARKETING SDN BHD?"
            }else{
                m = "Confirm to combine " + window.checkboxid.length + " delivery orders into ONE invoice for ANEKA INTERTRADE MARKETING SDN BHD?"
            }
            $.confirm({
                title: 'Combine and Convert',
                content: m,
                buttons: {
                    Yes: function() {
                        combineConvertDo(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });
        function combineConvertDo(ids){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/combine-convert') }}",
                type:"POST",
                data:{
                    ids: ids,
                    _token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Combined', response.message);
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Please contact your administrator', error.responseJSON?.message || 'Failed to combine delivery orders');
                }
            });
        }
    </script>
@endpush
