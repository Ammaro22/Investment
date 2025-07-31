<?php

namespace App\Notifications;
use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;


class WithdrawalProcessedNotification extends Notification
{
    use Queueable;

    protected $withdrawal; // 👈 تعريف المتغير

    public function __construct(WithdrawalRequest $withdrawal)
    {
        $this->withdrawal = $withdrawal; // 👈 تخزينه في المتغير
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تمت معالجة طلب السحب الخاص بك')
            ->greeting('مرحباً ' . $this->withdrawal->user->name)
            ->line('تمت معالجة طلب السحب الخاص بك.')
            ->line('المبلغ: $' . number_format($this->withdrawal->amount, 2))
            ->line('المرجع: ' . $this->withdrawal->transaction_reference)
            ->line('طريقة السحب: ' . $this->withdrawal->method)
            ->line('شكراً لاستخدامك منصتنا.');
    }
}
