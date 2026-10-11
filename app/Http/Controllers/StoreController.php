<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function home(): View
    {
        return view('store.home', [
            'heroes' => ContentBlock::with('media')->where('type', 'hero')->where('active', true)->orderBy('position')->get(),
            'promos' => ContentBlock::with('media')->where('type', 'promotion')->where('active', true)->orderBy('position')->get(),
            'products' => Product::published()->with(['media', 'variants', 'category'])->where('featured', true)->limit(8)->get(),
            'arrivals' => Product::published()->with(['media', 'variants', 'category'])->latest()->limit(4)->get(),
        ]);
    }

    public function catalog(Request $r, ?string $category = null, ?string $collection = null): View
    {
        $r->validate(['q' => 'nullable|string|max:100', 'size' => 'nullable|string|max:30', 'color' => 'nullable|string|max:50', 'min' => 'nullable|integer|min:0|max:1000000', 'max' => 'nullable|integer|min:0|max:1000000', 'sort' => 'nullable|in:newest,price_asc,price_desc,name']);
        $query = Product::published()->with(['media', 'variants', 'category']);
        $title = __('Shop all');
        if ($category) {
            $c = Category::where('slug', $category)->firstOrFail();
            $title = app()->getLocale() === 'bn' ? ($c->name_bn ?: $c->name) : $c->name;
            $query->where('category_id', $c->id);
        }
        if ($collection) {
            $c = Collection::where('slug', $collection)->firstOrFail();
            $title = $c->localized('name');
            $query->whereHas('collections', fn ($q) => $q->where('collections.id', $c->id));
        }
        if ($r->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $r->q).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('name_bn', 'like', $term));
        }
        $query->whereHas('variants', function ($q) use ($r) {
            if ($r->filled('size')) {
                $q->where('size', $r->size);
            }
            if ($r->filled('color')) {
                $q->where('color', $r->color);
            }
            if ($r->filled('min')) {
                $q->whereRaw('COALESCE(sale_price,price) >= ?', [(int) $r->min * 100]);
            }
            if ($r->filled('max')) {
                $q->whereRaw('COALESCE(sale_price,price) <= ?', [(int) $r->max * 100]);
            }
        });
        if (in_array($r->sort, ['price_asc', 'price_desc'], true)) {
            $query->withMin('variants as lowest_price', DB::raw('COALESCE(sale_price,price)'))->orderBy('lowest_price', $r->sort === 'price_asc' ? 'asc' : 'desc');
        } elseif ($r->sort === 'name') {
            $query->orderBy('name');
        } else {
            $query->latest();
        }

        return view('store.catalog', ['products' => $query->paginate(12)->withQueryString(), 'title' => $title]);
    }

    public function product(string $slug): View
    {
        $p = Product::published()->with(['media', 'variants.media', 'category'])->where('slug', $slug)->firstOrFail();

        return view('store.product', ['product' => $p, 'related' => Product::published()->with(['media', 'variants', 'category'])->where('category_id', $p->category_id)->whereKeyNot($p->id)->limit(4)->get()]);
    }

    public function page(string $slug): View
    {
        return view('store.page', ['page' => Page::where('slug', $slug)->where('published', true)->firstOrFail()]);
    }

    public function wishlist(): View
    {
        return view('store.wishlist', ['products' => Product::published()->with(['media', 'variants', 'category'])->whereIn('id', Wishlist::where('user_id', auth()->id())->select('product_id'))->get()]);
    }

    public function toggleWishlist(Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published', 404);
        $item = Wishlist::where('user_id', auth()->id())->where('product_id', $product->id)->first();
        if ($item) {
            $item->delete();
        } else {
            Wishlist::firstOrCreate(['user_id' => auth()->id(), 'product_id' => $product->id]);
        }

        return back()->with('status', __('Wishlist updated.'));
    }
}
