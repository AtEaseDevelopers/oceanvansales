<?php

namespace App\Repositories;

use App\Models\DeliveryOrder;
use App\Repositories\BaseRepository;

/**
 * Class DeliveryOrderRepository
 * @package App\Repositories
*/

class DeliveryOrderRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'invoiceno',
        'date',
        'customer_id',
        'driver_id',
        'kelindan_id',
        'agent_id',
        'supervisor_id',
        'status',
        'remark'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return DeliveryOrder::class;
    }
}
