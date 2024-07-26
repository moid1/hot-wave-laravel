<?php

namespace App\Http\Controllers\Tenancy;

use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use TCG\Voyager\Models\Role;
use Wave\PaddleSubscription;
use Wave\Plan;
use Wave\User;

class SubscriptionController extends \Wave\Http\Controllers\SubscriptionController
{
    public function webhook(Request $request){

        // Which alert/event is this request for?
        $alert_name = $request->alert_name;
        $subscription_id = $request->subscription_id;
        $status = $request->status;

        // Respond appropriately to this request.
        switch($alert_name) {
            case 'subscription_cancelled':
                $this->cancelSubscription($subscription_id);
                return response('Your subscription plan has been cancelled successfully.', 200);
            case 'payment_succeeded':
                return response('Thanks for purchasing it', 200);
            case 'subscription_updated':
                return response('Your subscription plan has been updated successfully.', 200);
            case 'subscription_payment_succeeded':
                return response('Thanks to choose the subscription plan', 200);
            default:
                return response('Sorry, there is a problem to process the payment.', 400);
        }
    }

    /**
     * @param $plan
     * @param \App\User $user
     * @param $tenant
     * @return void
     */
    public function addAssociatedRoleToUser($plan, \App\User $user, $tenant)
    {
        $user->role_id = $plan->role_id;
        $user->save();

        // add role
        $tenant->email_sent = 0;
        $tenant->email_total = 0;
        $tenant->sms_sent = 0;
        $tenant->sms_total = 0;
        $tenant->gateway = 0;
        $tenant->sensor = 0;
        if ($plan) {
            $tenant->email_total = $plan->email;
            $tenant->sms_total = $plan->sms;
            $tenant->gateway = $plan->gateway;
            $tenant->sensor = $plan->sensor;
        }
        $tenant->save();
    }

    private function cancelSubscription($subscription_id){
        $subscription = PaddleSubscription::where('subscription_id', $subscription_id)->first();
        $subscription->status = 'cancelled';
        $subscription->save();
        $user = User::find( $subscription->user_id );
        $old_plan = Plan::where('role_id', $user->role_id)->first();
        $cancelledRole = Role::where('name', '=', 'cancelled')->first();
        $user->role_id = $cancelledRole->id;
        $user->save();

        // update tenant
        $tenant = Tenant::find($user->username);
        // add role
        $plan = Plan::where('role_id', $user->role_id)->first();
        $tenant->email_sent = 0;
        $tenant->sms_sent = 0;
        $tenant->gateway = 0;
        $tenant->sensor = 0;
        if($plan && $old_plan){
            $tenant->email_total = $tenant->email_total - $old_plan->email + $plan->email;
            $tenant->sms_total = $tenant->sms_total - $old_plan->sms + $plan->sms;
            $tenant->gateway = $plan->gateway;
            $tenant->sensor = $plan->sensor;
        } else {
            $tenant->email_total = 0;
            $tenant->sms_total = 0;
        }
        $tenant->save();
    }

    public function checkout(Request $request){

        //PaddleSubscriptions
        $response = Http::get($this->paddle_checkout_url . '/1.0/order?checkout_id=' . $request->checkout_id);
        $status = 0;
        $message = '';
        $guest = (auth()->guest()) ? 1 : 0;

        if( $response->successful() ){
            $resBody = json_decode($response->body());

            if(isset($resBody->order)){
                $order = $resBody->order;

                $plans = Plan::all();

                if($order->is_subscription && $plans->contains('plan_id', $order->product_id) ){
                    $subscriptionUser = Http::post($this->paddle_vendors_url . '/2.0/subscription/users', [
                        'vendor_id' => $this->vendor_id,
                        'vendor_auth_code' => $this->vendor_auth_code,
                        'subscription_id' => $order->subscription_id
                    ]);

                    $subscriptionData = json_decode($subscriptionUser->body());
                    $subscription = $subscriptionData->response[0];
                    $plan = Plan::where('plan_id', $subscription->plan_id)->first();
                    if(auth()->guest()){
                        // create a new user
                        $registration = new \Wave\Http\Controllers\Auth\RegisterController;

                        $user_data = [
                            'name' => '',
                            'email' => $subscription->user_email,
                            'password' => Hash::make(uniqid())
                        ];

                        $user = $registration->create($user_data);

                        // create tenant
                        $tenant = Tenant::create(['id' => $user->username]);

                        // add associated role to user
                        $this->addAssociatedRoleToUser($plan, $user, $tenant);

                        // log in
                        Auth::login($user);

                    } else {
                        $user = auth()->user();

                        // update tenant
                        $tenant = Tenant::find($user->username);

                        // add associated role to user
                        $this->addAssociatedRoleToUser($plan, $user, $tenant);
                    }

                    PaddleSubscription::create([
                        'subscription_id' => $order->subscription_id,
                        'plan_id' => $order->product_id,
                        'user_id' => $user->id,
                        'status' => 'active', // https://paddle.com/docs/subscription-status-reference/
                        'next_bill_data' => \Carbon\Carbon::now()->addMonths(1)->toDateTimeString(),
                        'cancel_url' => $subscription->cancel_url,
                        'update_url' => $subscription->update_url
                    ]);

                    $status = 1;
                } else {

                    $message = 'Error locating that subscription product id. Please contact us if you think this is incorrect.';

                }
            } else {

                $message = 'Error locating that order. Please contact us if you think this is incorrect.';
            }

        } else {
            $message = $response->serverError();
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'guest' => $guest
        ]);
    }

    public function smsCheckout(Request $request){
        //Paddle checkout
        $response = Http::get($this->paddle_checkout_url . '/1.0/order?checkout_id=' . $request->checkout_id);
        $status = 0;
        $message = '';

        if( $response->successful() ){
            $resBody = json_decode($response->body());

            if(isset($resBody->order)){
                $order = $resBody->order;

                if(!$order->is_subscription){
                    $user = auth()->user();

                    // update tenant
                    $tenant = Tenant::find($user->username);
                    $tenant->email_total = $tenant->email_total + config("wave.paddle.sms_product_email_count");
                    $tenant->sms_total = $tenant->sms_total + config("wave.paddle.sms_product_sms_count");
                    $status = 1;
                } else {
                    $message = 'Error locating that product id. Please contact us if you think this is incorrect.';
                }
            } else {
                $message = 'Error locating that order. Please contact us if you think this is incorrect.';
            }

        } else {
            $message = $response->serverError();
        }

        return response()->json([
            'status' => $status,
            'message' => $message
        ]);
    }

    public function switchPlans(Request $request){
        $plan = Plan::where('plan_id', $request->plan_id)->first();

        if(isset($plan->id)){


            // Update the user plan with Paddle
            $response = Http::post($this->paddle_vendors_url . '/2.0/subscription/users/update', [
                'vendor_id' => $this->vendor_id,
                'vendor_auth_code' => $this->vendor_auth_code,
                'subscription_id' => auth()->user()->subscription->subscription_id,
                'plan_id' => $request->plan_id,
                'bill_immediately' => true,
                'prorate' => true
            ]);

            // old plan
            $old_plan = Plan::where('role_id', auth()->user()->role_id)->first();

            // Next, update the user role associated with the updated plan
            auth()->user()->role_id = $plan->role_id;
            auth()->user()->save();

            // update tenant
            $tenant = Tenant::find(auth()->user()->username);
            // add role
            $tenant->email_sent = 0;
            $tenant->sms_sent = 0;
            if($plan && $old_plan){
                $tenant->email_total = $tenant->email_total - $old_plan->email + $plan->email;
                $tenant->sms_total = $tenant->sms_total - $old_plan->sms + $plan->sms;
                $tenant->gateway = $plan->gateway;
                $tenant->sensor = $plan->sensor;
            }
            $tenant->save();

            if($response->successful()){
                $subscription = PaddleSubscription::where('subscription_id', auth()->user()->subscription->subscription_id)->first();
                $subscription->status = 'active';
                $subscription->save();

                return back()->with(['message' => 'Successfully switched to the ' . $plan->name . ' plan.', 'message_type' => 'success']);
            }

        }

        return back()->with(['message' => 'Sorry, there was an issue updating your plan.', 'message_type' => 'danger']);
    }

    public function invoices(User $user){
        $invoices = [];
        if(isset($user->subscription->subscription_id)){
            $response = Http::post($this->paddle_vendors_url . '/2.0/subscription/payments', [
                'vendor_id' => $this->vendor_id,
                'vendor_auth_code' => $this->vendor_auth_code,
                'subscription_id' => $user->subscription->subscription_id,
                'is_paid' => 1
            ]);
            $invoices = json_decode($response->body());
        }

        return $invoices;
    }

    public function productInvoices(User $user,$product_id){
        $invoices = [];
        if(isset($user->subscription->subscription_id)){
            $response = Http::post($this->paddle_vendors_url . '/2.0/product/'.$product_id.'/transactions', [
                'vendor_id' => $this->vendor_id,
                'vendor_auth_code' => $this->vendor_auth_code,
                'is_paid' => 1
            ]);
            $invoices = json_decode($response->body());
        }

        return $invoices;
    }
}