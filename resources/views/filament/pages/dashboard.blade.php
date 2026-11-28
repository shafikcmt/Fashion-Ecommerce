<x-filament-panels::page>
<style>
.mbd-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem}
.mbd-card{display:flex;align-items:center;gap:.9rem;padding:1rem 1.1rem;border-radius:.9rem;border:1px solid rgba(128,128,128,.25);background:rgba(128,128,128,.06);text-decoration:none;color:inherit;transition:transform .15s,box-shadow .15s}
a.mbd-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.12)}
.mbd-ico{flex:0 0 2.8rem;height:2.8rem;border-radius:.8rem;display:flex;align-items:center;justify-content:center;color:#fff;background:var(--c,#b3262f)}
.mbd-ico svg{width:1.4rem;height:1.4rem}
.mbd-card small{display:block;opacity:.7;font-size:.78rem;line-height:1.3}
.mbd-card strong{display:block;font-size:1.45rem;line-height:1.25}
.mbd-cols{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:1rem;align-items:start}
@media(max-width:900px){.mbd-cols{grid-template-columns:minmax(0,1fr)}}
.mbd-bars{display:flex;align-items:flex-end;gap:.6rem;height:11rem;padding-top:1rem}
.mbd-bar{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:.3rem;font-size:.72rem}
.mbd-bar i{display:block;width:100%;max-width:2.8rem;border-radius:.4rem .4rem 0 0;background:linear-gradient(180deg,#d9577f,#b3262f);min-height:4px}
.mbd-bar span{opacity:.7}
.mbd-bar b{font-size:.74rem}
.mbd-table{width:100%;border-collapse:collapse;font-size:.88rem}
.mbd-table th{text-align:start;padding:.5rem .6rem;border-bottom:1px solid rgba(128,128,128,.3);font-weight:600}
.mbd-table td{padding:.55rem .6rem;border-bottom:1px solid rgba(128,128,128,.15)}
.mbd-badge{display:inline-block;padding:.1rem .55rem;border-radius:999px;font-size:.72rem;font-weight:600;background:rgba(128,128,128,.2)}
.mbd-badge.pending{background:rgba(234,179,8,.22);color:#b7791f}
.mbd-badge.confirmed{background:rgba(59,130,246,.2);color:#2b6cb0}
.mbd-badge.shipped{background:rgba(99,102,241,.2);color:#4c51bf}
.mbd-badge.delivered{background:rgba(34,197,94,.2);color:#2f855a}
.mbd-badge.cancelled{background:rgba(239,68,68,.2);color:#c53030}
.mbd-links{display:flex;flex-wrap:wrap;gap:.6rem}
.mbd-links a{padding:.5rem .95rem;border-radius:999px;border:1.5px solid rgba(179,38,47,.7);color:#b3262f;font-weight:600;font-size:.85rem;text-decoration:none}
.mbd-links a:hover{background:#b3262f;color:#fff}
</style>
@php
$icon=fn(string $p)=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$p.'</svg>';
$orderUrl=\App\Filament\Resources\OrderResource::getUrl();
@endphp
<form method="post" action="{{ route('admin.locale') }}">@csrf<input type="hidden" name="admin_locale" value="{{ app()->getLocale()==='bn'?'en':'bn' }}"><x-filament::button type="submit" size="sm" color="gray">{{ app()->getLocale()==='bn'?'English':'বাংলা' }}</x-filament::button></form>

<div class="mbd-grid">
@if($canReport)
<a class="mbd-card" href="{{ $orderUrl }}"><span class="mbd-ico" style="--c:#b3262f">{!! $icon('<path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/>') !!}</span><span><small>{{ __('Orders today') }}</small><strong>{{ $todayOrders }}</strong></span></a>
<a class="mbd-card" href="{{ $orderUrl }}"><span class="mbd-ico" style="--c:#2f855a">{!! $icon('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/>') !!}</span><span><small>{{ __('Paid revenue today') }}</small><strong>{{ \App\Support\Money::format((int)$todayRevenue) }}</strong></span></a>
<a class="mbd-card" href="{{ \App\Filament\Pages\Reports::getUrl() }}"><span class="mbd-ico" style="--c:#6b46c1">{!! $icon('<path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/>') !!}</span><span><small>{{ __('Paid revenue, last 30 days') }}</small><strong>{{ \App\Support\Money::format((int)$revenue) }}</strong></span></a>
<a class="mbd-card" href="{{ $orderUrl }}"><span class="mbd-ico" style="--c:#d69e2e">{!! $icon('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>') !!}</span><span><small>{{ __('orders awaiting confirmation') }}</small><strong>{{ $pending }}</strong></span></a>
@if($awaitingPayment>0)<a class="mbd-card" href="{{ $orderUrl }}"><span class="mbd-ico" style="--c:#dd6b20">{!! $icon('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>') !!}</span><span><small>{{ __('bKash/Nagad orders waiting for payment verification') }}</small><strong>{{ $awaitingPayment }}</strong></span></a>@endif
@endif
@if($canCatalog)<a class="mbd-card" href="{{ \App\Filament\Resources\VariantResource::getUrl() }}"><span class="mbd-ico" style="--c:#c53030">{!! $icon('<path d="M12 3 2 20h20L12 3Z"/><path d="M12 10v4M12 17h.01"/>') !!}</span><span><small>{{ __('variants at or below their available-stock alert threshold') }}</small><strong>{{ $low }}</strong></span></a>@endif
@if(($pendingReviews ?? 0)>0)<a class="mbd-card" href="{{ \App\Filament\Resources\ProductReviewResource::getUrl() }}"><span class="mbd-ico" style="--c:#d69e2e">{!! $icon('<path d="m12 3 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.900l1.100-6.500L2.600 9.800l6.500-.9L12 3Z"/>') !!}</span><span><small>{{ __('reviews waiting for approval') }}</small><strong>{{ $pendingReviews }}</strong></span></a>@endif
</div>

@if($canReport)
<div class="mbd-cols">
<x-filament::section :heading="__('Sales in the last 7 days')">
@php $chart=array_reverse($days); $max=max(1,...array_column($chart,'revenue')); @endphp
<div class="mbd-bars">@foreach($chart as $day)<div class="mbd-bar" title="{{ $day['date'] }}: {{ $day['orders'] }} {{ __('Orders') }}, {{ \App\Support\Money::format($day['revenue']) }}"><b>{{ $day['revenue']>0?\App\Support\Money::format($day['revenue']):'' }}</b><i style="height:{{ max(3,round($day['revenue']/$max*100)) }}%"></i><span>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M') }} · {{ $day['orders'] }}</span></div>@endforeach</div>
<p style="opacity:.7;font-size:.8rem;margin-top:.75rem">{{ __('Currently paid order totals for orders created in the last 30 days, including shipping. Unpaid and refunded orders are excluded.') }}</p>
</x-filament::section>
<x-filament::section :heading="__('Recent orders')">
@if($recentOrders->isEmpty())<p style="opacity:.7">{{ __('No orders yet.') }}</p>@else
<table class="mbd-table"><thead><tr><th>#</th><th>{{ __('Customer') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>@foreach($recentOrders as $order)<tr><td>{{ $order->id }}</td><td>{{ $order->customer_name }}<br><small style="opacity:.65">{{ $order->created_at->diffForHumans() }}</small></td><td>{{ \App\Support\Money::format((int)$order->total) }}</td><td><span class="mbd-badge {{ $order->status }}">{{ ucfirst($order->status) }}</span>@if($order->payment_status==='paid') <span class="mbd-badge delivered">{{ __('Paid') }}</span>@endif</td></tr>@endforeach</tbody></table>
<p style="margin-top:.75rem"><x-filament::link href="{{ $orderUrl }}">{{ __('Manage orders') }}</x-filament::link></p>@endif
</x-filament::section>
</div>
@endif

@if($canCatalog && $lowStock->isNotEmpty())<x-filament::section :heading="__('Low available stock')"><table class="mbd-table"><tbody>@foreach($lowStock as $variant)<tr><td>{{ $variant->product->name }}</td><td>{{ $variant->sku }}</td><td><span class="mbd-badge cancelled">{{ $variant->available() }} {{ __('available') }}</span></td></tr>@endforeach</tbody></table></x-filament::section>@endif

<x-filament::section :heading="__('Quick links')"><div class="mbd-links">
@if($canReport)<a href="{{ $orderUrl }}">{{ __('Manage orders') }}</a><a href="{{ \App\Filament\Pages\Reports::getUrl() }}">{{ __('Reports with date filters') }}</a>@endif
@if($canCatalog)<a href="{{ \App\Filament\Resources\ProductResource::getUrl() }}">{{ __('Manage products') }}</a><a href="{{ \App\Filament\Resources\VariantResource::getUrl() }}">{{ __('Manage stock') }}</a><a href="{{ route('csv.form') }}">{{ __('CSV import / export') }}</a><a href="{{ \App\Filament\Resources\MediaResource::getUrl() }}">{{ __('Product images') }}</a>@endif
@if($canContent)<a href="{{ \App\Filament\Resources\ContentBlockResource::getUrl() }}">{{ __('Homepage, navigation & footer') }}</a><a href="{{ \App\Filament\Resources\SettingResource::getUrl() }}">{{ __('Brand & contact settings') }}</a><a href="{{ \App\Filament\Resources\PageResource::getUrl() }}">{{ __('Pages') }}</a>@endif
<a href="{{ route('home') }}">{{ __('Open storefront') }}</a></div>
@if($canContent)<p style="opacity:.7;font-size:.8rem;margin-top:.75rem">{{ __('Editable body content is plain text. Use Media IDs for the brand logo and social image settings.') }}</p>@endif
</x-filament::section>
</x-filament-panels::page>
