<?php

namespace App\Notifications;

use App\Models\MembershipPurchase;
use Illuminate\Notifications\Notification;

class MembershipActivatedNotification extends Notification
{
    public function __construct(private readonly array $data) {}

    public static function forPurchase(MembershipPurchase $purchase): self
    {
        return new self([
            'membership_purchase_id' => $purchase->id,
            'title' => 'Membership activated',
            'message' => 'Your '.$purchase->membership_name.' membership is now active.',
            'context' => $purchase->membership_name.' membership',
        ]);
    }

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array { return $this->data; }
}
