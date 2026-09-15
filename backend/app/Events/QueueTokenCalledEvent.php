<?php

namespace App\Events;

use App\Models\OpdQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueTokenCalledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $queue;

    public function __construct(OpdQueue $queue)
    {
        $this->queue = $queue;
    }
}
