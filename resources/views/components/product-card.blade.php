@props(['product'])
@php
$cheapest=$product->variants->sortBy(fn($v)=>$v->price())->first();
$sale=$product->variants->first(fn($v)=>$v->sale_price!==null);
$off=$sale?\App\Support\Money::percentOff($sale->price,$sale->sale_price):0;
$sizes=$product->variants->pluck('size')->unique();
$colors=$product->variants->pluck('color')->unique()->filter();
$soldOut=$product->variants->every(fn($v)=>$v->available()<=0);
$badge=$product->localized('badge');
@endphp
<article class="product-card"><a class="product-photo" href="{{ route('product',$product->slug) }}"><img src="{{ $product->image() }}" alt="{{ $product->media->first()?->alt ?? $product->name }}" width="600" height="760" loading="lazy" decoding="async">@if($soldOut)<span class="product-badge sold">{{ __('Sold out') }}</span>@elseif($off>0)<span class="product-badge sale">−{{ app()->getLocale()==='bn'?\App\Support\Money::digits($off):$off }}%</span>@elseif($badge)<span class="product-badge">{{ $badge }}</span>@endif</a>
<div class="product-meta"><span>{{ $product->category->localized('name') }}</span><form method="post" action="{{ route('wishlist.toggle',$product) }}">@csrf<button aria-label="{{ __('Save to wishlist') }}: {{ $product->localized('name') }}"><x-shop-icon name="heart"/></button></form></div><h3><a href="{{ route('product',$product->slug) }}">{{ $product->localized('name') }}</a></h3>
<x-rating-stars :average="$product->approved_reviews_avg_rating" :count="(int)$product->approved_reviews_count"/>
<p class="price">{{ \App\Support\Money::format((int)$cheapest?->price()) }}@if($sale)<del>{{ \App\Support\Money::format($sale->price) }}</del>@endif</p>
<div class="card-extra">@if($colors->isNotEmpty())<span class="swatches" aria-label="{{ $colors->implode(', ') }}">@foreach($colors->take(5) as $color)<i style="background:{{ \App\Support\Swatch::hex($color) ?? '#c9a24b' }}" title="{{ $color }}"></i>@endforeach</span>@endif<span class="muted">{{ $sizes->count()>1?$sizes->first().' – '.$sizes->last():$sizes->first() }}</span></div></article>
