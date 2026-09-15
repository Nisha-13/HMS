<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyAlertEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $alertData;

    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
    }
}
