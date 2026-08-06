<?php

namespace App\DataTables;

use App\Models\DeliveryOrder;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class DeliveryOrderDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        $dataTable->addColumn('converted_invoice', function ($deliveryOrder) {
            if (empty($deliveryOrder->invoice)) {
                return '-';
            }

            return '<a href="' . route('invoices.show', encrypt($deliveryOrder->invoice->id)) . '">' . e($deliveryOrder->invoice->invoiceno) . '</a>';
        });

        $dataTable->rawColumns(['converted_invoice', 'action']);

        return $dataTable->addColumn('action', 'delivery_orders.datatables_actions');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\DeliveryOrder $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(DeliveryOrder $model)
    {
        return $model->newQuery()
        ->with('customer')
        ->with('driver:id,name')
        ->with('kelindan:id,name')
        ->with('supervisor:id,name')
        ->with('deliveryorderdetail')
        ->with('invoice:id,invoiceno')
        ->select('deliveryorders.*');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['title' => trans('delivery_orders.action'), 'printable' => false])
            ->parameters([
                'dom'       => '<"row"B><"row"<"dataTableBuilderDiv"t>><"row"ip>',
                'stateSave' => true,
                'stateDuration' => 0,
                'processing' => false,
                'order'     => [[2, 'desc']],
                'lengthMenu' => [[ 10, 50, 100, 300 ],[ '10 rows', '50 rows', '100 rows', '300 rows' ]],
                'buttons' => [
                    [
                        'extend' => 'create',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-plus"></i> ' . trans('table_buttons.create'),
                    ],
                    [
                        'extend' => 'print',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-print"></i> ' . trans('table_buttons.print'),
                    ],
                    [
                        'extend' => 'reset',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reset'),
                    ],
                    [
                        'extend' => 'reload',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-refresh"></i> ' . trans('table_buttons.reload'),
                    ],
                    [
                        'extend' => 'excelHtml5',
                        'text' => '<i class="fa fa-file-excel-o"></i> ' . trans('table_buttons.excel'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'delivery_order' . date('dmYHis')
                    ],
                    [
                        'extend' => 'pdfHtml5',
                        'orientation' => 'landscape',
                        'pageSize' => 'LEGAL',
                        'text' => '<i class="fa fa-file-pdf-o"></i> ' . trans('table_buttons.pdf'),
                        'exportOptions' => ['columns' => ':visible:not(:last-child)'],
                        'className' => 'btn btn-default btn-sm no-corner',
                        'title' => null,
                        'filename' => 'delivery_order' . date('dmYHis')
                    ],
                    [
                        'extend' => 'colvis',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => '<i class="fa fa-columns"></i> ' . trans('table_buttons.column')
                    ],
                    [
                        'extend' => 'pageLength',
                        'className' => 'btn btn-default btn-sm no-corner',
                        'text' => trans('table_buttons.show_10_rows')
                    ],
                ],
                'columnDefs' => [
                    [
                        'targets' => -1,
                        'visible' => true
                    ],
                    [
                        'targets' => 0,
                        'visible' => true,
                        'render' => 'function(data, type){return "<input type=\'checkbox\' class=\'checkboxselect\' checkboxid=\'"+data+"\'/>";}'
                    ],
                    [
                        'targets' => 6,
                        'visible' => true,
                        'render' => 'function(data, type){var totalqty = 0; $.each(data,function(index,value){ totalqty=totalqty+parseInt(value.quantity) }); return totalqty;}'
                    ],
                    [
                    'targets' => 7,
                    'render' => 'function(data, type, row){
                            var map = {1:"Cash",2:"Credit",3:"Online BankIn",4:"E-wallet",5:"Cheque"};
                            return map[data] || data || "";
                        }'
                    ],
                    [
                    'targets' => 8,
                    'render' => 'function(data, type){var map = {1:"Completed",2:"Cancelled"}; return map[data] || "New";}'
                    ],
                    [
                    'targets' => 9,
                    'orderable' => false,
                    ],

                ],
                'initComplete' => 'function(){
                    var columns = this.api().init().columns;
                    this.api()
                    .columns()
                    .every(function (index) {
                        var column = this;
                        if(columns[index].searchable){
                            if(columns[index].title == \'Status\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="1">Completed</option><option value="2">Cancelled</option></select>\';
                            }else if(columns[index].title == \'Payment Term\'){
                                var input = \'<select class="border-0" style="width: 100%;"><option value=""></option><option value="1">Cash</option><option value="2">Credit</option><option value="3">Online BankIn</option><option value="4">E-wallet</option><option value="5">Cheque</option></select>\';
                            }else if(columns[index].title == \'Date\'){
                                var input = \'<input type="text" id="\'+index+\'Date" onclick="searchDateColumn(this);" placeholder="Search ">\';
                            }else{
                                var input = \'<input type="text" placeholder="Search ">\';
                            }
                            $(input).appendTo($(column.footer()).empty()).on(\'change\', function(){
                                column.search($(this).val(),false,false).draw();
                                ShowLoad();
                            })
                        }
                    });

                }'
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'checkbox'=> new \Yajra\DataTables\Html\Column(['title' => '<input type="checkbox" id="selectallcheckbox">',
            'data' => 'id',
            'name' => 'id',
            'orderable' => false,
            'searchable' => false
            ]),

            'invoiceno' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.do_no'),
                'data' => 'invoiceno',
                'name' => 'invoiceno'
            ]),

            'date' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.date'),
                'data' => 'date',
                'name' => 'date'
            ]),

            'customer_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.customer'),
                'data' => 'customer.company',
                'name' => 'customer.company'
            ]),

            'driver_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.driver'),
                'data' => 'driver.name',
                'name' => 'driver.name'
            ]),

            'kelindan_id' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.kelindan'),
                'data' => 'kelindan.name',
                'name' => 'kelindan.name'
            ]),

            'total' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.total_qty'),
                'data' => 'deliveryorderdetail',
                'name' => 'deliveryorderdetail',
                'searchable' => false
            ]),

            'paymentterm' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.payment_term'),
                'data' => 'paymentterm',
                'name' => 'deliveryorders.paymentterm'
            ]),

            'status' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.status'),
                'data' => 'status',
                'name' => 'deliveryorders.status'
            ]),

            'converted_invoice' => new \Yajra\DataTables\Html\Column([
                'title' => trans('delivery_orders.converted_to_invoice'),
                'data' => 'converted_invoice',
                'name' => 'converted_invoice',
                'orderable' => false,
                'searchable' => false
            ]),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'delivery_orders_datatable_' . time();
    }
}
