<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Variant;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    public string $from = '';

    public string $to = '';

    public static function canAccess(): bool
    {
        return Gate::allows('reports');
    }

    public function mount(): void
    {
        Gate::authorize('reports');
        $this->from = now()->subDays(30)->toDateString();
        $this->to = now()->toDateString();
    }

    public function apply(): void
    {
        $this->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);
    }

    protected function getViewData(): array
    {
        Gate::authorize('reports');
        $this->validate(['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from']);
        $base = Order::whereBetween('created_at', [$this->from.' 00:00:00', $this->to.' 23:59:59']);

        return [
            'revenue' => (clone $base)->where('payment_status', 'paid')->sum('total'),
            'counts' => (clone $base)->selectRaw('status, COUNT(*) as count')->groupBy('status')->get(),
            'best' => OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.payment_status', 'paid')->whereBetween('orders.created_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])->selectRaw('order_items.sku, order_items.product_name, SUM(order_items.quantity) as quantity')->groupBy('order_items.sku', 'order_items.product_name')->orderByDesc('quantity')->limit(10)->get(),
            'low' => Variant::with('product')->whereRaw('(stock-reserved) <= low_stock_threshold')->orderBy('stock')->limit(30)->get(),
        ];
    }
}
