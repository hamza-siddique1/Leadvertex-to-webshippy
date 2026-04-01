<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveoOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliveo_id',
        'order_id',
        'picked_up',
        'last_modified',
        'invoice_created_at',
        'invoice_path',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'picked_up' => 'datetime',
        'last_modified' => 'datetime',
        'invoice_created_at' => 'datetime',
    ];

    public function getInvoiceGeneratedAttribute(): bool
    {
        return !is_null($this->invoice_created_at);
    }


    public function generateInvoice()
    {
        if (!$this->invoice_created_at) {
            //\App\Jobs\GenerateInvoiceJob::dispatch($this->id);
        }
    }
}
