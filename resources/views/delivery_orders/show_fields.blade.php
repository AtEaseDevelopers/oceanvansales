<div class="row">
<div class="{{ $deliveryOrder->attachment ? 'col-sm-8' : 'col-sm-12' }}">

<!-- Invoiceno Field -->
<div class="form-group">
    {!! Form::label('invoiceno', __('delivery_orders.do_no')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->invoiceno }}</p>
</div>

<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', __('delivery_orders.date')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->date }}</p>
</div>

<!-- Customer Id Field -->
<div class="form-group">
    {!! Form::label('customer_id', __('delivery_orders.customer')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->customer->company ?? '' }}</p>
</div>

<!-- Driver Id Field -->
<div class="form-group">
    {!! Form::label('driver_id', __('delivery_orders.driver')) !!}:
    <p>{{ $deliveryOrder->driver->name ?? '' }}</p>
</div>

<!-- Kelindan Id Field -->
<div class="form-group">
    {!! Form::label('kelindan_id', __('delivery_orders.kelindan')) !!}:
    <p>{{ $deliveryOrder->kelindan->name ?? '' }}</p>
</div>

<!-- Agent Id Field -->
<div class="form-group">
    {!! Form::label('agent_id', __('delivery_orders.agent')) !!}:
    <p>{{ $deliveryOrder->agent->name ?? '' }}</p>
</div>

<!-- Paymentterm Field -->
<div class="form-group">
    {!! Form::label('paymentterm', __('delivery_orders.payment_term')) !!}:
    <p>{{ \App\Models\Customer::PAYMENT_TERMS[$deliveryOrder->paymentterm] ?? 'Unknown' }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', __('delivery_orders.status')) !!}:<span class="asterisk"> *</span>
    <p>{{ $deliveryOrder->status == 2 ? "Cancelled" : ($deliveryOrder->status == 1 ? "Completed" : "New") }}</p>
</div>

<!-- Remark Field -->
<div class="form-group">
    {!! Form::label('remark', __('delivery_orders.remark')) !!}:
    <p>{{ $deliveryOrder->remark }}</p>
</div>

<!-- Converted Invoice -->
<div class="form-group">
    {!! Form::label('invoice_id', __('delivery_orders.converted_to_invoice')) !!}:
    <p>
        @if($deliveryOrder->invoice)
            <a href="{{ route('invoices.show', encrypt($deliveryOrder->invoice_id)) }}">{{ $deliveryOrder->invoice->invoiceno }}</a>
        @else
            -
        @endif
    </p>
</div>

</div>{{-- end left col --}}

@if($deliveryOrder->attachment)
<div class="col-sm-4 text-center">
    <label>Attachment</label>
    <div>
        <img src="{{ asset('/' . $deliveryOrder->attachment) }}" class="img-fluid img-thumbnail" style="max-height: 350px;">
    </div>
</div>
@endif

</div>{{-- end row --}}

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
