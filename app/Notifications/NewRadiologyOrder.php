<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRadiologyOrder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'patient_name' => $this->order->patient->nama,
            'examination' => $this->order->examinationType->name ?? 'Pemeriksaan Radiologi',
            'priority' => $this->order->priority,
            'message' => 'Order baru: ' . $this->order->order_number . ' - ' . $this->order->patient->nama,
            'url' => route('orders.show', $this->order->id),
            'type' => 'new_order'
        ];
    }
}
