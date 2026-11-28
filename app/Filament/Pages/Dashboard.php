<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Variant;
use App\Support\ManualPayments;
use Illuminate\Support\Facades\Gate;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected function getViewData(): array
    {
        $canReport = Gate::allows('reports');
        $canCatalog = Gate::allows('viewAny', Product::class);

        return [
            'canReport' => $canReport,
            'canCatalog' => $canCatalog,
            'canContent' => Gate::allows('viewAny', Page::class),
            'revenue' => $canReport ? Order::where('payment_status', 'paid')->where('created_at', '>=', now()->subDays(30))->sum('total') : null,
            'pending' => $canReport ? Order::where('status', 'pending')->count() : null,
            'low' => $canCatalog ? Variant::whereRaw('(stock-reserved) <= low_stock_threshold')->count() : null,
            'lowStock' => $canCatalog ? Variant::with('product')->whereRaw('(stock-reserved) <= low_stock_threshold')->orderByRaw('stock - reserved')->limit(8)->get() : collect(),
            'days' => $canReport ? $this->lastSevenDays() : [],
            'todayOrders' => $canReport ? Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->count() : null,
            'todayRevenue' => $canReport ? (int) Order::whereDate('created_at', today())->where('payment_status', 'paid')->sum('total') : null,
            'recentOrders' => $canReport ? Order::latest('id')->limit(8)->get(['id', 'customer_name', 'total', 'status', 'payment_status', 'payment_method', 'created_at']) : collect(),
            'awaitingPayment' => $canReport ? Order::whereIn('payment_method', array_keys(ManualPayments::METHODS))->where('payment_status', 'unpaid')->where('status', '!=', 'cancelled')->count() : null,
            'pendingReviews' => Gate::allows('viewAny', ProductReview::class) ? ProductReview::where('status', 'pending')->count() : null,
        ];
    }

    /**
     * Orders placed and paid revenue per day for the last seven days in the app timezone (Asia/Dhaka), newest first.
     *
     * @return array<int, array{date: string, orders: int, revenue: int}>
     */
    private function lastSevenDays(): array
    {
        $start = now()->subDays(6)->startOfDay();
        $orders = Order::where('created_at', '>=', $start)->where('status', '!=', 'cancelled')->get(['created_at', 'total', 'payment_status']);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = now()->subDays($i)->toDateString();
            $ofDay = $orders->filter(fn (Order $order) => $order->created_at->toDateString() === $day);
            $days[] = ['date' => $day, 'orders' => $ofDay->count(), 'revenue' => (int) $ofDay->where('payment_status', 'paid')->sum('total')];
        }

        return $days;
    }
}
