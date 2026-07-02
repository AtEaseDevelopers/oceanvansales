<!-- Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('code', __('products.code')) !!}<span class="asterisk"> *</span>
    {!! Form::text('code', null, ['class' => 'form-control', 'maxlength' => 255, 'autofocus']) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', __('products.name')) !!}<span class="asterisk"> *</span>
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Prices Field -->
<div class="form-group col-sm-12">
    {!! Form::label('price_tiers', 'Prices') !!}<span class="asterisk"> *</span>
    <div id="price-tiers-container">
        @if(isset($product) && $product->prices->count() > 0)
            @foreach($product->prices as $i => $tier)
            <div class="price-tier-row input-group mb-2" style="max-width:300px;">
                <div class="input-group-prepend"><span class="input-group-text">RM</span></div>
                <input type="number" name="price_tiers[{{ $i }}][price]" value="{{ $tier->price }}" class="form-control" step="0.01" min="0" placeholder="0.00">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm remove-tier">Remove</button>
                </div>
            </div>
            @endforeach
        @else
            <div class="price-tier-row input-group mb-2" style="max-width:300px;">
                <div class="input-group-prepend"><span class="input-group-text">RM</span></div>
                <input type="number" name="price_tiers[0][price]" value="{{ isset($product) ? $product->price : '' }}" class="form-control" step="0.01" min="0" placeholder="0.00">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm remove-tier">Remove</button>
                </div>
            </div>
        @endif
    </div>
    <button type="button" class="btn btn-outline-success btn-sm mt-1" id="add-tier">+ Add Price</button>
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', __('products.type')) !!}
    {{ Form::select('type', [
        0 => __('products.type_ice'),
    ], null, ['class' => 'form-control']) }}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', __('products.status')) !!}
    {{ Form::select('status', [
        1 => __('products.active'),
        0 => __('products.unactive'),
    ], null, ['class' => 'form-control']) }}
</div>
@einvoice
<!-- Classification Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('classification_code', 'Classification Code:') !!}<span class="asterisk"> *</span>
    {!! Form::select('classification_code', $classificationOptions ?? [], null, ['class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-size' => '10', 'data-dropup-auto' => 'false', 'id' => 'classification_code', 'placeholder' => 'Select Classification Code...']) !!}
</div>
@endeinvoice
<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit(__('products.save'), ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('products.cancel') }}</a>
</div>

@push('scripts')
   <style>
        #classification_code + .dropdown-menu,
        select[name="classification_code"] + .dropdown-menu,
        .bootstrap-select.show .dropdown-menu {
            max-height: 400px !important;
            overflow-y: auto !important;
            max-width: 400px !important;
            width: auto !important;
        }
        .bootstrap-select .dropdown-menu.inner {
            max-height: 350px !important;
            overflow-y: auto !important;
        }
        .bootstrap-select button {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });

        var tierIndex = {{ isset($product) ? max($product->prices->count(), 1) : 1 }};

        $('#add-tier').click(function () {
            var row = '<div class="price-tier-row input-group mb-2" style="max-width:300px;">' +
                '<div class="input-group-prepend"><span class="input-group-text">RM</span></div>' +
                '<input type="number" name="price_tiers[' + tierIndex + '][price]" class="form-control" step="0.01" min="0" placeholder="0.00">' +
                '<div class="input-group-append"><button type="button" class="btn btn-danger btn-sm remove-tier">Remove</button></div>' +
                '</div>';
            $('#price-tiers-container').append(row);
            tierIndex++;
        });

        $(document).on('click', '.remove-tier', function () {
            if ($('.price-tier-row').length > 1) {
                $(this).closest('.price-tier-row').remove();
            }
        });

        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush
