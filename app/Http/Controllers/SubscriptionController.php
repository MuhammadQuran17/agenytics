<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Checkout;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Display the subscription plans page.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('SubscriptionPlans', [
            'plans' => config('subscription-plans.plans'),
            'isEverSubscribed' => Auth::user()->subscriptions()->exists(),
        ]);
    }

    /**
     * Handle both subscription and one-time purchases
     * For subscription plans (basic_monthly): creates recurring subscription with metered billing
     * For one-time purchases (100_prompts, 200_prompts): creates one-time checkout session
     */
    public function subscribeOrPurchase(Request $request): Checkout
    {
        $planKeys = array_keys(config('subscription-plans.plans', []));

        $request->validate([
            'plan' => ['required', 'string', 'in:'.implode(',', $planKeys)],
        ]);

        $planConfig = config("subscription-plans.plans." . $request->plan);

        if ($planConfig['price_type'] === 'one_time') {
            return $this->handleOneTimePurchase($request, $planConfig);
        }

        return $this->handleSubscription($request, $planConfig);
    }

    private function handleOneTimePurchase(Request $request, array $planConfig): Checkout
    {
        $checkoutOptions = $this->getCheckoutOptions();

        // Add plan info to metadata so webhook can identify which plan was purchased
        $checkoutOptions['metadata'] = ['plan_key' => $request->plan];

        return $request->user()
            ->checkout([$planConfig['stripe_price_id'] => 1], $checkoutOptions);
    }

    private function handleSubscription(Request $request, array $planConfig): Checkout
    {
        // Recurring subscription (basic_monthly)
        $subscriptionBuilder = $request->user()
            ->newSubscription('default', $planConfig['stripe_price_id']);

        // Register metered price for subscription plans
        if (isset($planConfig['stripe_metered_price_id'])) {
            $subscriptionBuilder->meteredPrice($planConfig['stripe_metered_price_id']);
        }

        /** This checkout params differs from above one-time purchase because it's a recurring subscription */
        return $subscriptionBuilder->checkout($this->getCheckoutOptions());
    }

    private function getCheckoutOptions(): array
    {
        return  [
            'success_url' => route('dashboard'),
            'cancel_url' => route('dashboard'),
            'allow_promotion_codes' => true,
        ];
    }
}
