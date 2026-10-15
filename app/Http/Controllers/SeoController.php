<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [route('home'), route('catalog')];
        foreach (Product::published()->cursor() as $p) {
            $urls[] = route('product', $p->slug);
        }
        foreach (Category::cursor() as $c) {
            $urls[] = route('category', $c->slug);
        }
        foreach (Collection::cursor() as $c) {
            $urls[] = route('collection', $c->slug);
        }
        foreach (Page::where('published', true)->cursor() as $p) {
            $urls[] = route('page', $p->slug);
        }

        return response(view('store.sitemap', compact('urls')), 200, ['Content-Type' => 'application/xml']);
    }
}
