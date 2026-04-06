<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveoSyncLog extends Model
{
    protected $fillable = [
        'deliveo_id',
        'phone_number',
        'deliveo_status',
        'delivery_date',
        'order_amount',
        'customer_name',
        'salesrender_order_id',
        'sync_status',
        'error_message',
        'api_response'
    ];
}
