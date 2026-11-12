<?php

namespace App\Console\Commands;

use App\Mail\Admin\ContactFormMail;
use App\Mail\Admin\LowStockMail;
use App\Mail\Admin\NewOrderMail;
use App\Mail\Admin\NewReviewMail;
use App\Mail\Admin\NewUserMail;
use App\Mail\Cart\AbandonedCartMail;
use App\Mail\LoginAlertMail;
use App\Mail\Order\OrderStatusMail;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Quick test-send for every mail template.
 *
 * Usage examples:
 *   php artisan mail:test welcome --to=you@example.com
 *   php artisan mail:test order-status --to=you@example.com --order=1 --status=shipped
 *   php artisan mail:test all --to=you@example.com
 */
class TestMailCommand extends Command
{
    protected $signature = 'mail:test
        {type : Template name (welcome|verify|login-alert|admin-new-order|admin-new-user|admin-low-stock|admin-new-review|admin-contact|order-status|cart-abandoned|all)}
        {--to= : Recipient email address (defaults to mail.from.address)}
        {--order= : Order ID for order-related templates}
        {--status= : Override order status (pending|processing|paid|shipped|completed|canceled|refunded)}
    ';

    protected $description = 'Send a test email for one or all mail templates.';

    /* ──────────────── entry point ───────────────────────────── */

    public function handle(): int
    {
        $type = $this->argument('type');
        $to   = $this->option('to') ?: config('mail.from.address');

        $this->info("Sending [{$type}] test mail to <{$to}> …");

        $types = $type === 'all'
            ? ['welcome','verify','login-alert','admin-new-order','admin-new-user',
               'admin-low-stock','admin-new-review','admin-contact','order-status','cart-abandoned']
            : [$type];

        foreach ($types as $t) {
            try {
                $this->dispatch($t, $to);
                $this->line("  ✓ <fg=green>{$t}</>");
            } catch (\Throwable $e) {
                $this->line("  ✗ <fg=red>{$t}: {$e->getMessage()}</>");
            }
        }

        $this->newLine();
        $this->info('Done.');
        return self::SUCCESS;
    }

    /* ──────────────── dispatch helpers ─────────────────────── */

    private function dispatch(string $type, string $to): void
    {
        match ($type) {
            'welcome'         => Mail::to($to)->send($this->makeWelcome()),
            'verify'          => Mail::to($to)->send($this->makeVerify()),
            'login-alert'     => Mail::to($to)->send($this->makeLoginAlert()),
            'admin-new-order' => Mail::to($to)->send($this->makeAdminNewOrder()),
            'admin-new-user'  => Mail::to($to)->send($this->makeAdminNewUser()),
            'admin-low-stock' => Mail::to($to)->send($this->makeAdminLowStock()),
            'admin-new-review'=> Mail::to($to)->send($this->makeAdminNewReview()),
            'admin-contact'   => Mail::to($to)->send($this->makeAdminContact()),
            'order-status'    => Mail::to($to)->send($this->makeOrderStatus()),
            'cart-abandoned'  => Mail::to($to)->send($this->makeAbandonedCart()),
            default           => throw new \InvalidArgumentException("Unknown template: {$type}"),
        };
    }

    /* ──────────────── stub factories ───────────────────────── */

    private function fakeUser(): User
    {
        return User::factory()->make([
            'id'         => 1,
            'name'       => 'Test Customer',
            'email'      => 'customer@example.com',
            'created_at' => now(),
        ]);
    }

    private function makeWelcome(): WelcomeMail
    {
        return new WelcomeMail($this->fakeUser());
    }

    private function makeVerify(): VerifyEmailMail
    {
        return new VerifyEmailMail(
            $this->fakeUser(),
            config('app.url') . '/email/verify/1/fake-hash?expires=9999999999&signature=fake'
        );
    }

    private function makeLoginAlert(): LoginAlertMail
    {
        return new LoginAlertMail(
            user:      $this->fakeUser(),
            ip:        '198.51.100.1',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            loginAt:   now()->format('d M Y, H:i') . ' UTC',
        );
    }

    private function resolveOrder(): Order
    {
        $id = $this->option('order');
        if ($id && $order = Order::with(['items', 'shippingAddress', 'user'])->find($id)) {
            if ($status = $this->option('status')) {
                $order->status = $status;
            }
            return $order;
        }

        // Stub order when no real ID given
        $order = new Order([
            'id'             => 0,
            'number'         => 'ORD-TEST-0001',
            'email'          => 'customer@example.com',
            'status'         => $this->option('status') ?? Order::STATUS_PENDING,
            'currency'       => 'USD',
            'subtotal'       => 79.99,
            'discount_total' => 5.00,
            'shipping_total' => 4.99,
            'tax_total'      => 7.20,
            'grand_total'    => 87.18,
            'placed_at'      => now(),
        ]);

        $order->setRelation('items', collect([
            (object)['name'=>'Wireless Headphones','sku'=>'WH-1000XM5','qty'=>1,'unit_price'=>79.99,'row_total'=>79.99],
            (object)['name'=>'Phone Case',         'sku'=>'PC-S24-BLK', 'qty'=>2,'unit_price'=>9.99, 'row_total'=>19.98],
        ]));
        $order->setRelation('shippingAddress', null);

        return $order;
    }

    private function makeAdminNewOrder(): NewOrderMail
    {
        return new NewOrderMail($this->resolveOrder());
    }

    private function makeAdminNewUser(): NewUserMail
    {
        return new NewUserMail($this->fakeUser());
    }

    private function makeAdminLowStock(): LowStockMail
    {
        return new LowStockMail(collect([
            ['sku' => 'PROD-001', 'name' => 'Widget Alpha',  'qty' => 2],
            ['sku' => 'PROD-007', 'name' => 'Gadget Beta',   'qty' => 0],
            ['sku' => 'PROD-042', 'name' => 'Doohickey Pro', 'qty' => 1],
        ]));
    }

    private function makeAdminNewReview(): NewReviewMail
    {
        return new NewReviewMail([
            'product_name'  => 'Wireless Headphones XM5',
            'reviewer_name' => 'Jane Smith',
            'rating'        => 4,
            'body'          => 'Great sound quality, comfortable fit. Battery could be better.',
        ]);
    }

    private function makeAdminContact(): ContactFormMail
    {
        return new ContactFormMail(
            senderName:  'John Doe',
            senderEmail: 'john@example.com',
            subject:     'Question about my order',
            message:     "Hi there,\n\nI placed an order three days ago and haven't received a confirmation. Could you please check?\n\nThanks,\nJohn",
        );
    }

    private function makeOrderStatus(): OrderStatusMail
    {
        $order = $this->resolveOrder();
        $tracking = $order->status === Order::STATUS_SHIPPED ? 'TRACK123456789' : null;
        return new OrderStatusMail($order, $tracking);
    }

    private function makeAbandonedCart(): AbandonedCartMail
    {
        $cart = new Cart([
            'id'            => 0,
            'session_token' => 'fake-session-token',
            'email'         => 'customer@example.com',
            'currency'      => 'USD',
            'grand_total'   => 99.97,
        ]);

        $cart->setRelation('user', null);
        $cart->setRelation('items', collect([
            (object)['name'=>'Wireless Headphones','qty'=>1,'unit_price'=>79.99,'row_total'=>79.99],
            (object)['name'=>'Phone Case',         'qty'=>2,'unit_price'=>9.99, 'row_total'=>19.98],
        ]));

        return new AbandonedCartMail($cart);
    }
}
