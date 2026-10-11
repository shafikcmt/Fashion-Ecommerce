@php
$brand=\App\Models\Setting::value('brand_name','Aabaran');
$nav=\App\Models\ContentBlock::where('type','navigation')->where('active',true)->orderBy('position')->get();
$footer=\App\Models\ContentBlock::where('type','footer')->where('active',true)->orderBy('position')->get();
$logo=\App\Models\Media::find((int)\App\Models\Setting::value('logo_media_id'));
$social=\App\Models\Media::find((int)\App\Models\Setting::value('social_media_id'));
$accent=\App\Models\Setting::value('brand_color','#333D79');
if(!preg_match('/^#[0-9a-fA-F]{6}$/',$accent)) $accent='#333D79';
@endphp
<!doctype html><html lang="{{ app()->getLocale() }}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" href="{{ $logo?->url() ?? asset('images/brand-mark.svg') }}">
<title>{{ $title ?? __('Everyday, beautifully dressed') }} · {{ $brand }}</title>
<meta name="description" content="{{ $description ?? \App\Models\Setting::value('seo_description') }}">
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">
<meta property="og:title" content="{{ $title ?? $brand }}"><meta property="og:description" content="{{ $description ?? \App\Models\Setting::value('seo_description') }}"><meta property="og:url" content="{{ url()->current() }}">
@if(isset($shareImage)||$social)<meta property="og:image" content="{{ $shareImage ?? $social->url() }}">@endif
@if(request()->is('account*','orders*','checkout*','login','register','bag','wishlist','admin-catalog*'))<meta name="robots" content="noindex,nofollow">@endif
@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles
<style>:root{--accent:{{ $accent }}}</style></head><body>
<a class="skip" href="#main">{{ __('Skip to content') }}</a>
<div class="announcement">{{ \App\Models\Setting::value(app()->getLocale()==='bn'?'announcement_bn':'announcement','Made for the way you move. Delivered across Bangladesh.') }}</div>
<header class="site-header"><div class="header-main wrap">
<a class="wordmark" href="{{ route('home') }}">@if($logo)<img src="{{ $logo->url() }}" alt="{{ $logo->alt }}" width="150" height="45">@else{{ $brand }}<span>{{ __('Dhaka, Bangladesh') }}</span>@endif</a>
<form class="search" action="{{ route('catalog') }}"><label class="sr-only" for="search">{{ __('Search') }}</label><input id="search" name="q" placeholder="{{ __('Find your next favourite') }}" value="{{ request('q') }}"><button aria-label="{{ __('Search') }}"><x-shop-icon name="search"/></button></form>
<div class="header-actions"><form action="{{ route('locale') }}" method="post">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale()==='en'?'bn':'en' }}"><button class="language">{{ app()->getLocale()==='en'?'বাংলা':'English' }}</button></form>
<a href="{{ route('account') }}" aria-label="{{ __('Account') }}"><x-shop-icon name="user"/></a><a href="{{ route('wishlist') }}" aria-label="{{ __('Wishlist') }}"><x-shop-icon name="heart"/></a><a href="{{ route('bag') }}" aria-label="{{ __('Shopping bag') }}"><x-shop-icon name="bag"/></a></div></div>
<nav class="main-nav wrap" aria-label="{{ __('Main navigation') }}"><details class="mobile-nav"><summary>{{ __('Menu') }}</summary><div>@foreach($nav as $link)<a href="{{ $link->url }}">{{ $link->localized('title') }}</a>@endforeach</div></details><div class="desktop-nav">@forelse($nav as $link)<a href="{{ $link->url }}">{{ $link->localized('title') }}</a>@empty<a href="{{ route('catalog') }}">{{ __('Shop all') }}</a>@endforelse</div><a class="nav-note" href="{{ route('page','delivery') }}">{{ __('Cash on delivery nationwide') }}</a></nav></header>
<main id="main">@if(session('status'))<div class="wrap notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="wrap error" role="alert"><strong>{{ __('Please check the following:') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}</main>
<footer><div class="wrap footer-grid"><div><a class="wordmark" href="{{ route('home') }}">{{ $brand }}</a><p>{{ \App\Models\Setting::value('brand_story','A considered wardrobe. A little more you.') }}</p><p>{{ \App\Models\Setting::value('contact_address','Dhaka, Bangladesh') }}</p></div><div><h3>{{ __('Good to know') }}</h3>@foreach($footer as $link)<a href="{{ $link->url }}">{{ $link->localized('title') }}</a>@endforeach</div><div><h3>{{ __('Here to help') }}</h3><p>{{ \App\Models\Setting::value('contact_email') }}</p><p>{{ \App\Models\Setting::value('contact_phone') }}</p><a href="{{ route('page','contact') }}">{{ __('Contact us') }}</a><a href="{{ route('account') }}">{{ __('Your orders') }}</a></div></div><div class="wrap footer-bottom"><span>© {{ date('Y') }} {{ $brand }}</span><span>{{ __('BDT · Cash on delivery') }}</span></div></footer>
@livewireScripts</body></html>
