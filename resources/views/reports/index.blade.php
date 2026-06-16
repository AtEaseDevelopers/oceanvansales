@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('report.reports') }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-bar-chart"></i>
                            {{ __('report.reports') }}
                        </div>
                        <div class="card-body">
                            <div class="row mt-2">

                                {{-- Daily Sales Report --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #007bff;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#007bff;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-file-text-o fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">Daily Sales Report</h5>
                                            </div>
                                            <p class="text-muted small">
                                                View sales summary by date range. Filter by driver, customer and payment type. Includes invoice details and payment breakdown.
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.daily-sales') }}" class="btn btn-primary btn-sm">
                                                <i class="fa fa-arrow-right"></i> Open Report
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Customer Purchase History --}}
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 shadow-sm" style="border-left: 4px solid #28a745;">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="fa-stack fa-lg mr-3" style="color:#28a745;">
                                                    <i class="fa fa-circle fa-stack-2x"></i>
                                                    <i class="fa fa-user fa-stack-1x fa-inverse"></i>
                                                </span>
                                                <h5 class="mb-0">Customer Purchase History</h5>
                                            </div>
                                            <p class="text-muted small">
                                                Monthly purchase summary per customer — quantity, amount and frequency. Filter by driver, payment type and date range.
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent border-0">
                                            <a href="{{ route('reports.customer-purchase') }}" class="btn btn-success btn-sm">
                                                <i class="fa fa-arrow-right"></i> Open Report
                                            </a>
                                        </div>
                                    </div>
                                </div>

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
    $(document).ready(function () {
        HideLoad();
    });
</script>
@endpush
