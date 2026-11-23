@php
$isBn=app()->getLocale()==='bn';
$brand=$isBn?\App\Models\Setting::value('brand_name_bn','মামণি বস্ত্রালয়'):\App\Models\Setting::value('brand_name','Mamoni Bosraloy');
$whatsapp=preg_replace('/\D+/','',(string)\App\Models\Setting::value('whatsapp'));
$facebook=\App\Models\Setting::value('facebook_url');
$hotline=\App\Models\Setting::value('contact_phone');
$nav=\App\Models\ContentBlock::where('type','navigation')->where('active',true)->orderBy('position')->get();
$footer=\App\Models\ContentBlock::where('type','footer')->where('active',true)->orderBy('position')->get();
$logo=\App\Models\Media::find((int)\App\Models\Setting::value('logo_media_id'));
$social=\App\Models\Media::find((int)\App\Models\Setting::value('social_media_id'));
$accent=\App\Models\Setting::value('brand_color','#7A1F2B');
if (! preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
    $accent = '#7A1F2B';
}
@endphp
<!doctype html><html lang="{{ app()->getLocale() }}"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/svg+xml" href="{{ $logo?->url() ?? asset('images/brand-mark.svg') }}"><meta name="theme-color" content="{{ $accent }}">
<title>{{ $title ?? __('Sari, Panjabi, Three-piece & more') }} · {{ $brand }}</title>
<meta name="description" content="{{ $description ?? \App\Models\Setting::value('seo_description') }}">
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">
<meta property="og:title" content="{{ $title ?? $brand }}"><meta property="og:description" content="{{ $description ?? \App\Models\Setting::value('seo_description') }}"><meta property="og:url" content="{{ url()->current() }}">
@if(isset($shareImage)||$social)<meta property="og:image" content="{{ $shareImage ?? $social->url() }}">@endif
@if(request()->is('account*','orders*','checkout*','login','register','bag','wishlist','track-order','admin-catalog*'))<meta name="robots" content="noindex,nofollow">@endif
@php
$pixelId=\App\Support\Analytics::facebookPixelId();
$gaId=\App\Support\Analytics::gaId();
@endphp
@if($pixelId)<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $pixelId }}');fbq('track','PageView');</script>@endif
@if($gaId)<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>@endif
@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles
<style>:root{--accent:{{ $accent }};--maroon:{{ $accent }}}</style></head><body>
<a class="skip" href="#main">{{ __('Skip to content') }}</a>
<div class="announcement"><span>{{ \App\Models\Setting::value($isBn?'announcement_bn':'announcement','Delivered across Bangladesh · Cash on delivery') }}</span>@if($hotline)<a href="tel:{{ preg_replace('/[^\d+]/','',$hotline) }}" class="hotline">{{ __('Hotline') }}: {{ $isBn?\App\Support\Money::digits($hotline):$hotline }}</a>@endif</div>
<header class="site-header"><div class="header-main wrap">
<a class="wordmark" href="{{ route('home') }}" aria-label="{{ $brand }}">@if($logo)<img src="{{ $logo->url() }}" alt="{{ $logo->alt }}" width="150" height="45">@else<x-brand-logo :name="$brand"/>@endif</a>
<form class="search" action="{{ route('catalog') }}"><label class="sr-only" for="search">{{ __('Search') }}</label><input id="search" name="q" type="search" placeholder="{{ __('Find your next favourite') }}" value="{{ request('q') }}"><button aria-label="{{ __('Search') }}"><x-shop-icon name="search"/></button></form>
<div class="header-actions"><form action="{{ route('locale') }}" method="post">@csrf<input type="hidden" name="locale" value="{{ app()->getLocale()==='en'?'bn':'en' }}"><button class="language">{{ app()->getLocale()==='en'?'বাংলা':'English' }}</button></form>
<a href="{{ route('account') }}" aria-label="{{ __('Account') }}"><x-shop-icon name="user"/></a><a href="{{ route('wishlist') }}" aria-label="{{ __('Wishlist') }}"><x-shop-icon name="heart"/></a><a href="{{ route('bag') }}" aria-label="{{ __('Shopping bag') }}"><x-shop-icon name="bag"/></a></div></div>
<nav class="main-nav wrap" aria-label="{{ __('Main navigation') }}"><details class="mobile-nav"><summary>{{ __('Menu') }}</summary><div>@forelse($nav as $link)<a href="{{ $link->url }}">{{ $link->localized('title') }}</a>@empty<a href="{{ route('catalog') }}">{{ __('Shop all') }}</a>@endforelse</div></details><div class="desktop-nav">@forelse($nav as $link)<a href="{{ $link->url }}">{{ $link->localized('title') }}</a>@empty<a href="{{ route('catalog') }}">{{ __('Shop all') }}</a>@endforelse</div><a class="nav-note" href="{{ route('page','delivery') }}">{{ __('Cash on delivery nationwide') }}</a></nav></header>
<main id="main">@if(session('status'))<div class="wrap notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="wrap error" role="alert"><strong>{{ __('Please check the following:') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}</main>
<footer><div class="trust-bar"><div class="wrap"><span>✓ {{ __('Delivery across Bangladesh') }}</span><span>✓ {{ __('Cash on delivery') }}</span><span>✓ {{ __('7-day easy return') }}</span><span>✓ {{ __('Genuine products') }}</span></div></div>
<div class="wrap footer-grid"><div><a class="wordmark" href="{{ route('home') }}" aria-label="{{ $brand }}"><x-brand-logo :name="$brand"/></a><p>{{ \App\Models\Setting::value($isBn?'brand_story_bn':'brand_story','Traditional & modern clothing for the whole family.') }}</p><p>{{ \App\Models\Setting::value('contact_address','Dhaka, Bangladesh') }}</p></div><div><h3>{{ __('Good to know') }}</h3>@foreach($footer as $link)<a href="{{ $link->url }}">{{ $link->localized('title') }}</a>@endforeach</div><div><h3>{{ __('Here to help') }}</h3><p>{{ \App\Models\Setting::value('contact_email') }}</p><p>{{ $isBn?\App\Support\Money::digits($hotline):$hotline }}</p>@if($whatsapp)<a href="https://wa.me/{{ $whatsapp }}" rel="noopener">WhatsApp</a>@endif @if($facebook && str_starts_with($facebook,'https://'))<a href="{{ $facebook }}" rel="noopener">Facebook</a>@endif<a href="{{ route('page','contact') }}">{{ __('Contact us') }}</a><a href="{{ route('track') }}">{{ __('Track your order') }}</a><a href="{{ route('account') }}">{{ __('Your orders') }}</a></div></div><div class="wrap footer-bottom"><span>© {{ $isBn?\App\Support\Money::digits(date('Y')):date('Y') }} {{ $brand }}</span><span>{{ __('BDT · Cash on delivery') }}</span></div></footer>
@if($whatsapp)<a class="whatsapp-float" href="https://wa.me/{{ $whatsapp }}" rel="noopener" aria-label="{{ __('Chat on WhatsApp') }}"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20l1.4-4.2A8.5 8.5 0 1 1 8.4 18.8L4 20Z"/><path d="M9 9.5c.3 2.2 2.300 4.200 5.500 5.500l1.300-1.400-2-.9-.9.700a5 5 0 0 1-2.200-2.200l.700-.9-.9-2Z" fill="currentColor" stroke="none"/></svg></a>@endif
<nav class="bottom-nav" aria-label="{{ __('Quick navigation') }}"><a href="{{ route('home') }}" @class(['active'=>request()->routeIs('home')])><x-shop-icon name="home"/><span>{{ __('Home') }}</span></a><a href="{{ route('catalog') }}" @class(['active'=>request()->routeIs('catalog','category','collection')])><x-shop-icon name="grid"/><span>{{ __('Shop') }}</span></a><a href="{{ route('wishlist') }}"><x-shop-icon name="heart"/><span>{{ __('Wishlist') }}</span></a><a href="{{ route('bag') }}" @class(['active'=>request()->routeIs('bag')])><x-shop-icon name="bag"/><span>{{ __('Bag') }}</span></a><a href="{{ route('account') }}"><x-shop-icon name="user"/><span>{{ __('Account') }}</span></a></nav>
@livewireScripts</body></html>
