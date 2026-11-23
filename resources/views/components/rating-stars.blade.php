@props(['average' => 0, 'count' => 0])
@php
$value=max(0,min(5,(float)$average));
$display=app()->getLocale()==='bn'?\App\Support\Money::digits(number_format($value,1)):number_format($value,1);
@endphp
@if($count>0)<span class="rating" role="img" aria-label="{{ $display }} / {{ app()->getLocale()==='bn'?'৫':'5' }}"><span class="stars" style="--rating:{{ round($value/5*100) }}%" aria-hidden="true">★★★★★</span> <small>{{ $display }} ({{ app()->getLocale()==='bn'?\App\Support\Money::digits($count):$count }})</small></span>@endif
