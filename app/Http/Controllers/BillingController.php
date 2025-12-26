<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage-clinics');
    }

    public function show(Clinic $clinic)
    {
        $clinic->load(['users', 'invoices.payments']);

        // Get subscription details from Stripe if available
        $subscriptionDetails = null;
        if ($clinic->stripe_subscription_id) {
            $subscriptionDetails = $this->getStripeSubscription($clinic->stripe_subscription_id);
        }

        return Inertia::render('Clinics/Billing', [
            'clinic' => $clinic,
            'subscriptionDetails' => $subscriptionDetails,
            'stripePublicKey' => config('services.stripe.key'),
        ]);
    }

    public function createCheckoutSession(Clinic $clinic, Request $request)
    {
        $request->validate([
            'price_id' => 'required|string',
            'plan' => 'required|in:basic,pro,enterprise'
        ]);

        try {
            $session = \Stripe\Checkout\Session::create([
                'customer' => $clinic->stripe_customer_id ?? $this->createStripeCustomer($clinic),
                'line_items' => [[
                    'price' => $request->price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('billing.success', ['clinic' => $clinic->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.cancel', ['clinic' => $clinic->id]),
                'metadata' => [
                    'clinic_id' => $clinic->id,
                    'plan' => $request->plan
                ],
            ]);

            return response()->json(['sessionId' => $session->id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    private function handleCheckoutCompleted($session)
    {
        $clinic = Clinic::find($session->metadata->clinic_id);
        if ($clinic) {
            $clinic->update([
                'stripe_subscription_id' => $session->subscription,
                'stripe_price_id' => $session->line_items->data[0]->price->id,
                'subscription_plan' => $session->metadata->plan,
                'subscription_status' => 'active',
                'subscription_ends_at' => now()->addMonth(),
            ]);
        }
    }

    private function createStripeCustomer(Clinic $clinic)
    {
        $customer = \Stripe\Customer::create([
            'email' => $clinic->email,
            'name' => $clinic->name,
            'metadata' => ['clinic_id' => $clinic->id],
        ]);

        $clinic->update(['stripe_customer_id' => $customer->id]);
        return $customer->id;
    }
}
