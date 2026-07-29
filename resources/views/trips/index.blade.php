@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('trips.trips') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('trips.trips') }}
                         </div>
                         <div class="card-body">
                             @include('trips.table')
                              <div class="pull-right mr-3">

                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>

    {{-- Trip Images Modal --}}
    <div id="tripImagesModal" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">Trip Images</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row" id="tripImagesModalBody"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ANEKA Entry Modal --}}
    <div id="tripAnekaModal" class="modal fade">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">ANEKA Quantity &mdash; <span id="tripAnekaLorry"></span></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Opening</th>
                                <th class="text-right">Sales Used</th>
                                <th class="text-right">Wastage</th>
                                <th style="width:120px;">ANEKA Qty</th>
                            </tr>
                        </thead>
                        <tbody id="tripAnekaModalBody"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="tripAnekaSaveBtn">
                        <i class="fa fa-save"></i> Save
                    </button>
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

        $(document).on('click', '.btn-trip-images', function () {
            var images = JSON.parse($(this).attr('data-images'));
            var body = $('#tripImagesModalBody');
            body.empty();
            images.forEach(function (img) {
                body.append(
                    '<div class="col-md-4 mb-3 text-center">' +
                        '<p><strong>' + img.label + '</strong></p>' +
                        '<a href="' + img.url + '" target="_blank">' +
                            '<img src="' + img.url + '" class="img-fluid img-thumbnail" style="max-height:250px;">' +
                        '</a>' +
                    '</div>'
                );
            });
            $('#tripImagesModal').modal('show');
        });

        $(document).on('click', '.btn-trip-report', function () {
            var tripId = $(this).attr('data-trip-id');
            $('#tripAnekaSaveBtn').attr('data-trip-id', tripId);
            $('#tripAnekaModalBody').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
            $('#tripAnekaLorry').text('');
            $('#tripAnekaModal').modal('show');

            $.ajax({
                url: "{{ url('/trips') }}/" + tripId + "/aneka",
                type: 'GET',
                success: function (response) {
                    $('#tripAnekaLorry').text(response.lorry);
                    var body = $('#tripAnekaModalBody');
                    body.empty();
                    if (!response.products.length) {
                        body.append('<tr><td colspan="5" class="text-center text-muted">No products found for this trip.</td></tr>');
                        return;
                    }
                    response.products.forEach(function (p) {
                        body.append(
                            '<tr>' +
                                '<td>' + p.product_name + '</td>' +
                                '<td class="text-right">' + p.opening_stock + '</td>' +
                                '<td class="text-right">' + p.sales_used + '</td>' +
                                '<td class="text-right">' + p.wastage + '</td>' +
                                '<td>' +
                                    '<input type="number" min="0" step="1" class="form-control form-control-sm aneka-qty-input" ' +
                                        'data-product-id="' + p.product_id + '" value="' + p.aneka + '">' +
                                '</td>' +
                            '</tr>'
                        );
                    });
                },
                error: function (error) {
                    $('#tripAnekaModalBody').html('<tr><td colspan="5" class="text-center text-danger">Failed to load trip products.</td></tr>');
                }
            });
        });

        $(document).on('click', '#tripAnekaSaveBtn', function () {
            var btn = $(this);
            var tripId = btn.attr('data-trip-id');
            var products = [];
            $('.aneka-qty-input').each(function () {
                products.push({
                    product_id: $(this).attr('data-product-id'),
                    quantity: $(this).val() || 0
                });
            });

            $.confirm({
                title: 'Confirm ANEKA Save',
                content: 'This will deduct/return the driver\'s lorry inventory based on the ANEKA quantities entered. Are you sure you want to proceed?',
                buttons: {
                    Yes: function () {
                        ShowLoad();
                        $.ajax({
                            url: "{{ url('/trips') }}/" + tripId + "/aneka",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                products: products
                            },
                            success: function (response) {
                                HideLoad();
                                $('#tripAnekaModal').modal('hide');
                                noti('s', 'Saved', response.message);
                                window.open(response.report_url, '_blank');
                            },
                            error: function (error) {
                                HideLoad();
                                var msg = (error.responseJSON && error.responseJSON.message) ? error.responseJSON.message : 'Failed to save ANEKA quantities.';
                                noti('e', 'Please contact your administrator', msg);
                            }
                        });
                    },
                    No: function () {
                        return;
                    }
                }
            });
        });
    </script>
@endpush

