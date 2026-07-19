<!-- Lorry Field -->
<div class="form-group">
    {!! Form::label('lorry_id', __('assign.lorry')) !!}:
    <p>{{ $assign->lorry->lorryno ?? '-' }}</p>
</div>

<!-- Customer Code Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('assign.customer_code')) !!}:
    <p>{{ $assign->customer->code }}</p>
</div>

<!-- Customer Company Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('assign.customer_company')) !!}:
    <p>{{ $assign->customer->company }}</p>
</div>

<!-- Sequence Field -->
<div class="form-group">
    {!! Form::label('sequence', __('assign.sequence')) !!}:
    <p>{{ $assign->sequence }}</p>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush