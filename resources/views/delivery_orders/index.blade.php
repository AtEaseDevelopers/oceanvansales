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
                             <button type="button" class="btn btn-warning btn-sm pull-right mr-2" id="mergeconvert" title="Merge the selected delivery orders into an existing invoice you pick; that invoice is reset to NOT synced to AutoCount">
                                <i class="fa fa-compress"></i> Merge to Invoice
                             </button>
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

@push('styles')
    <style>
        /* Invoice-picker list lives inline inside the modal (no floating dropdown), so it can
           never be clipped. Wrap long invoice/customer text and highlight the chosen row. */
        #mergeInvoiceList .list-group-item {
            white-space: normal;
            word-break: break-word;
            cursor: pointer;
        }
        #mergeInvoiceList .list-group-item.active {
            background-color: #20a8d8;
            border-color: #20a8d8;
            color: #fff;
        }
    </style>
@endpush

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

        // Read the selected DO's date (rendered as d-m-Y) and return it as yyyy-mm-dd
        // for the <input type="date"> default value.
        function firstSelectedDoDate(){
            var firstId = window.checkboxid[0];
            var iso = '';
            try {
                $('#dataTableBuilder').DataTable().rows().every(function(){
                    var d = this.data();
                    if(String(d.id) === String(firstId) && d.date){
                        var p = d.date.split('-'); // [dd, mm, yyyy]
                        if(p.length === 3){ iso = p[2] + '-' + p[1] + '-' + p[0]; }
                    }
                });
            } catch(err) {}
            return iso;
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
            var defaultDate = firstSelectedDoDate();
            $.confirm({
                title: 'Combine and Convert',
                content: '<div>' + m + '</div>' +
                    '<div class="form-group mt-3">' +
                        '<label for="combineDate">Invoice Date</label>' +
                        '<input type="date" id="combineDate" class="form-control" value="' + defaultDate + '">' +
                    '</div>',
                buttons: {
                    Yes: function() {
                        var chosen = this.$content.find('#combineDate').val();
                        if(!chosen){
                            noti('i','Info','Please select an invoice date');
                            return false; // keep the dialog open
                        }
                        combineConvertDo(window.checkboxid, chosen);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });
        function combineConvertDo(ids, date){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/combine-convert') }}",
                type:"POST",
                data:{
                    ids: ids,
                    date: date,
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

        // Render invoice search results as an inline, clickable list inside the modal.
        // No floating dropdown (select2) — it can't survive jquery-confirm's clipping panes —
        // just a normal scrollable list that lives entirely within the dialog.
        function renderMergeInvoiceResults($list, $hidden, results){
            $list.empty();
            $hidden.val('');
            if(!results || !results.length){
                $list.append($('<div class="list-group-item text-muted"></div>').text('No invoices found'));
                return;
            }
            results.forEach(function(item){
                $('<a href="#" class="list-group-item list-group-item-action"></a>')
                    .attr('data-id', item.id)
                    .text(item.text)
                    .appendTo($list);
            });
        }

        $(document).on("click", "#mergeconvert", function(e){
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one delivery order');
                return;
            }
            var count = window.checkboxid.length;
            var m = "Pick an existing invoice to merge " + count + " selected delivery order" + (count > 1 ? "s" : "") + " into. The invoice will be reset to NOT synced to AutoCount.";
            $.confirm({
                title: 'Merge to Invoice',
                columnClass: 'medium',
                content: '<div>' + m + '</div>' +
                    '<div class="form-group mt-3">' +
                        '<label for="mergeInvoiceSearch">Invoice</label>' +
                        '<input type="text" id="mergeInvoiceSearch" class="form-control" placeholder="Search invoice number..." autocomplete="off">' +
                        '<div id="mergeInvoiceList" class="list-group mt-2" style="max-height: 240px; overflow-y: auto;"></div>' +
                        '<input type="hidden" id="mergeInvoiceId">' +
                    '</div>',
                onContentReady: function(){
                    var self = this;
                    var url = "{{ url('/deliveryOrders/merge-invoices') }}";
                    var $search = self.$content.find('#mergeInvoiceSearch');
                    var $list = self.$content.find('#mergeInvoiceList');
                    var $hidden = self.$content.find('#mergeInvoiceId');
                    var timer = null;

                    function load(q){
                        $list.html('<div class="list-group-item text-muted">Loading...</div>');
                        $.ajax({
                            url: url,
                            dataType: 'json',
                            data: { q: q },
                            success: function(data){ renderMergeInvoiceResults($list, $hidden, data.results); },
                            error: function(){ $list.html('<div class="list-group-item text-danger">Failed to load invoices</div>'); }
                        });
                    }

                    // Pick a row.
                    self.$content.on('click', '#mergeInvoiceList .list-group-item[data-id]', function(ev){
                        ev.preventDefault();
                        $list.find('.list-group-item').removeClass('active');
                        $(this).addClass('active');
                        $hidden.val($(this).attr('data-id'));
                    });

                    // Debounced search.
                    $search.on('keyup', function(){
                        var q = $(this).val();
                        clearTimeout(timer);
                        timer = setTimeout(function(){ load(q); }, 250);
                    });

                    load(''); // initial: most recent invoices
                    setTimeout(function(){ $search.focus(); }, 0);
                },
                buttons: {
                    Yes: function() {
                        var invoiceId = this.$content.find('#mergeInvoiceId').val();
                        if(!invoiceId){
                            noti('i','Info','Please select an invoice');
                            return false; // keep the dialog open
                        }
                        mergeConvertDo(window.checkboxid, invoiceId);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });
        function mergeConvertDo(ids, invoiceId){
            ShowLoad();
            $.ajax({
                url: "{{ url('/deliveryOrders/merge-convert') }}",
                type:"POST",
                data:{
                    ids: ids,
                    invoice_id: invoiceId,
                    _token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Merged', response.message);
                },
                error: function(error) {
                    HideLoad();
                    noti('e','Please contact your administrator', error.responseJSON?.message || 'Failed to merge delivery orders');
                }
            });
        }
    </script>
@endpush
