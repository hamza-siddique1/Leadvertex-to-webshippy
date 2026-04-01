<?php

namespace App\Jobs;

use App\Http\Controllers\DeliveoController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateDeliveoInvoice implements ShouldQueue
{
    use Queueable;

    protected $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        $controller = new DeliveoController();
        $controller->create_invoice_from_deliveo($this->orderId);
    }
}
