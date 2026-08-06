<!-- Invoiceno Field -->
<div class="form-group col-sm-6">
    {!! Form::label('invoiceno', __('delivery_orders.do_no')) !!}<span class="asterisk"> *</span>
    {!! Form::text('invoiceno', null, ['class' => 'form-control','maxlength' => 255,'autofocus', 'placeholder' => 'SYSTEM GENERATED IF BLANK']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', __('delivery_orders.date')) !!}<span class="asterisk"> *</span>
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Customer Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('customer_id', __('delivery_orders.customer')) !!}<span class="asterisk"> *</span>
    {!! Form::select('customer_id', $customerItems, null, ['class' => 'form-control select2-customer', 'placeholder' => 'Pick a Customer...']) !!}
</div>


<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', __('delivery_orders.driver')) !!}
    {!! Form::select('driver_id', $driverItems, null, ['class' => 'form-control select2-driver', 'placeholder' => 'Pick a Driver...']) !!}
</div>


<!-- Kelindan Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('kelindan_id', __('delivery_orders.kelindan')) !!}
    {!! Form::select('kelindan_id', $kelindanItems, null, ['class' => 'form-control select2-kelindan', 'placeholder' => 'Pick a Kelindan...']) !!}
</div>


<!-- Agent Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('agent_id',  __('delivery_orders.agent'))  !!}
    {!! Form::select('agent_id', $agentItems, null, ['class' => 'form-control select2-agent', 'placeholder' => 'Pick a Agent...']) !!}
</div>


<!-- Paymentterm Field -->
<div class="form-group col-sm-6">
    {!! Form::label('paymentterm',  __('delivery_orders.payment_term'))  !!}
    {{ Form::select('paymentterm', array(1 => 'Cash' , 2 => 'Credit',3 => 'Online BankIn' , 4 => 'E-wallet', 5 => 'Cheque'), null, ['class' => 'form-control']) }}
</div>

<!-- ChequeNo Field -->
<div class="form-group col-sm-6" id='cheque-container' style='display:none;'>
    {!! Form::label('chequeno', __('delivery_orders.cheque_no')) !!}
    {!! Form::text('chequeno', null, ['class' => 'form-control','maxlength' => 20]) !!}
</div>

@if(isset($deliveryOrder))
<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status',  __('delivery_orders.status'))  !!}<span class="asterisk"> *</span>
    {{ Form::select('status', array(1 => 'Completed', 2 => 'Cancelled'), null, ['class' => 'form-control']) }}
</div>
@else
    {{ Form::hidden('status', 1) }}
@endif

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark',  __('delivery_orders.remark'))  !!}
    {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Attachment Field -->
<div class="form-group col-sm-6">
    {!! Form::label('attachment', 'Attachment') !!}
    <input type="file" name="attachment" class="form-control-file" accept="image/*">
    @if(!empty($deliveryOrder->attachment))
        <div class="mt-2">
            <img src="{{ asset('storage/' . $deliveryOrder->attachment) }}" style="max-height: 100px;" class="img-thumbnail">
            <small class="d-block text-muted">Current attachment</small>
        </div>
    @endif
</div>

<input type="text" class="d-none" name="method" id="method" value="2">

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::button( __('delivery_orders.save_&_exit') , ['class' => 'btn btn-primary','id' => 'save_exit']) !!}
    {!! Form::button (__('delivery_orders.save_&_continue') , ['class' => 'btn btn-primary','id' => 'save_continue']) !!}
    <a href="{{ route('deliveryOrders.index') }}" class="btn btn-secondary"> {{__('delivery_orders.cancel') }}</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });

        var customerPaymentTerms = {!! json_encode($customerPaymentTerms) !!};

        function fillPaymentTerm(customerId) {
            if (!customerId) return;
            var term = customerPaymentTerms[customerId];
            if (term) {
                $('#paymentterm').val(term).trigger('change');
            }
        }

        $(document).ready(function () {
            $('.select2-customer').select2({
                placeholder: "Search for a customer...",
                allowClear: true,
                width: '100%'
            });

            $('.select2-driver').select2({
                placeholder: "Search for a driver...",
                allowClear: true,
                width: '100%'
            });

            $('.select2-kelindan').select2({
                placeholder: "Search for a kelindan...",
                allowClear: true,
                width: '100%'
            });

            $('.select2-agent').select2({
                placeholder: "Search for an agent...",
                allowClear: true,
                width: '100%'
            });

            $('#customer_id').on('select2:select select2:clear', function(e) {
                fillPaymentTerm(e.type === 'select2:clear' ? null : $(this).val());
            });

            fillPaymentTerm($('#customer_id').val());

            HideLoad();
        });
        $("#save_exit").click(function(){
            $('#method').val(1);
            $('form').submit();
        });
        $("#save_continue").click(function(){
            $('#method').val(2);
            $('form').submit();
        });

        $('#paymentterm').change(function(){
            if($(this).val() == "5") {
                $('#cheque-container').show();
            } else {
                $('#cheque-container').hide();
            }
        });
    </script>
    <style>
        .select2-container--default .select2-search--dropdown .select2-search__field {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            box-shadow: none;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #007bff;
            color: white;
        }
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
            color: #495057;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .select2-container {
            width: 100% !important;
        }
    </style>
@endpush
