<?php

namespace App\Services\Subscription;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SubscriptionBillingHelper;

class SubscriptionTrackUsage
{
    /**
     * Track usage with priority-based consumption:
     * 1. Free prompts first
     * 2. Purchased prompts (if not expired)
     * 3. Monthly subscription meter event
     */
    public function trackUsage(?User $user = null): void
    {
        /** @var User $user */
        $user ??= Auth::user();

        if ($user->free_prompts > 0) {
            $user->decrement('free_prompts', 1);

        } elseif ($user->purchased_prompts > 0) {
            $user->decrement('purchased_prompts', 1);

        } elseif (
            $user->subscribed() &&
            $user->subscribedToProduct(SubscriptionBillingHelper::getPlansStripeProductIdsThatUseMeteredBilling())
        ) {
            $user->reportMeterEvent('prompts', 1);
        }
    }


    public function zeroingPurchasedPromptsIfExpired(): void
    {
        $user = Auth::user();

        if ($user->isPurchasedPromptsNotExpired()) {
            return;
        }

        $user->purchased_prompts = 0;
        $user->save();
    }

}
