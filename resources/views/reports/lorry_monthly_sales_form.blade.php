@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">{{ __('report.reports') }}</a></li>
        <li class="breadcrumb-item active">Lorry Monthly Sales Product Details Report</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('flash::message')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-truck"></i>
                            Lorry Monthly Sales Product Details Report
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('reports.lorry-monthly-sales.excel') }}">
                                @csrf
                                <div class="row">

                                    {{-- Date From --}}
                                    <div class="form-group col-sm-6">
                                        <label>Date From <span class="text-danger">*</span></label>
                                        <input type="date" name="date_from" class="form-control"
                                               value="{{ old('date_from', date('Y-m-01')) }}" required>
                                    </div>

                                    {{-- Date To --}}
                                    <div class="form-group col-sm-6">
                                        <label>Date To <span class="text-danger">*</span></label>
                                        <input type="date" name="date_to" class="form-control"
                                               value="{{ old('date_to', date('Y-m-d')) }}" required>
                                    </div>

                                    {{-- Lorry --}}
                                    <div class="form-group col-sm-6">
                                        <label>Lorry <span class="text-muted small">(optional)</span></label>
                                        <select name="lorry_id" class="form-control select2-lorry">
                                            <option value="">— All Lorries —</option>
                                            @foreach($lorries as $lorry)
                                                <option value="{{ $lorry->id }}"
                                                    {{ old('lorry_id') == $lorry->id ? 'selected' : '' }}>
                                                    {{ $lorry->lorryno }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <div class="form-group mt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-file-excel-o"></i> Generate Excel
                                    </button>
                                    <a href="{{ route('reports.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fa fa-arrow-left"></i> Back
                                    </a>
                                </div>

                            </form>
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
    $('.select2-lorry').select2({ placeholder: 'Search lorry...', allowClear: true, width: '100%' });
    HideLoad();
});
</script>
@endpush
