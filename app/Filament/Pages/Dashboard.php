<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\Gate;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected function getViewData(): array
    {
        return [
            'canReport' => Gate::allows('reports'),
            'canCatalog' => Gate::allows('viewAny', Product::class),
            'canContent' => Gate::allows('viewAny', Page::class),
            'revenue' => Gate::allows('reports') ? Order::where('payment_status', 'paid')->where('created_at', '>=', now()->subDays(30))->sum('total') : null,
            'pending' => Gate::allows('reports') ? Order::where('status', 'pending')->count() : null,
            'low' => Gate::allows('viewAny', Product::class) ? Variant::whereRaw('(stock-reserved) <= low_stock_threshold')->count() : null,
        ];
    }
}
