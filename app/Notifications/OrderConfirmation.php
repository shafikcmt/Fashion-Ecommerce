<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = Order::findOrFail($this->orderId);

        return (new MailMessage)->subject('Your order has been received')->greeting('Hello '.$o->customer_name)
            ->line('Order #'.$o->id.' ? '.Money::format($o->total).' ? Cash on delivery')
            ->line('We will confirm your order shortly.')
            ->action('View your order', URL::temporarySignedRoute('orders.show', now()->addDays(30), ['order' => $o->public_id]));
    }
}
