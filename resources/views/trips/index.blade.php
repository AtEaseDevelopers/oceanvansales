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
    </script>
@endpush

