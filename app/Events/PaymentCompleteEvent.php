<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleteEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public $status;
    public $message;
    public $userId;

    public function __construct($status, $message, $userId)
    {
        $this->status = $status;
        $this->message = $message;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new Channel('payments.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'payment.complete';
    }
}
