<?php

namespace App\Jobs;

use App\Http\Controllers\SalesRenderController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateAndUploadInvoice implements ShouldQueue
{
    use Queueable;

    protected $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        $controller = new SalesRenderController();
        $controller->create_invoice($this->orderId);
    }
}
