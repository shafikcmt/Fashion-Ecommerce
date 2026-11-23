<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\ContentBlock;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Wishlist;
use App\Support\SearchTerms;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
            'products' => Product::published()->withRating()->with(['media', 'variants', 'category'])->where('featured', true)->limit(8)->get(),
            'arrivals' => Product::published()->withRating()->with(['media', 'variants', 'category'])->latest()->limit(4)->get(),
            'categories' => Category::withCount(['products' => fn ($q) => $q->published()])->orderBy('id')->get()->filter(fn ($c) => $c->products_count > 0),
            'festival' => Collection::where('slug', 'eid-collection')->first(),
            'festivalProducts' => Product::published()->withRating()->with(['media', 'variants', 'category'])->whereHas('collections', fn ($q) => $q->where('slug', 'eid-collection'))->limit(4)->get(),
            'sale' => Product::published()->withRating()->with(['media', 'variants', 'category'])->whereHas('variants', fn ($q) => $q->whereNotNull('sale_price'))->limit(4)->get(),
        ]);
    }

    public function catalog(Request $r, ?string $category = null, ?string $collection = null): View
    {
        $r->validate(['q' => 'nullable|string|max:100', 'size' => 'nullable|string|max:30', 'color' => 'nullable|string|max:50', 'fabric' => 'nullable|string|max:100', 'stock' => 'nullable|in:1', 'min' => 'nullable|integer|min:0|max:1000000', 'max' => 'nullable|integer|min:0|max:1000000', 'sort' => 'nullable|in:newest,price_asc,price_desc,name']);
        $query = Product::published()->withRating()->with(['media', 'variants', 'category']);
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
            $this->applySearch($query, $r->q);
        }
        if ($r->filled('fabric')) {
            $query->where(fn ($q) => $q->where('fabric', $r->fabric)->orWhere('fabric_bn', $r->fabric));
        }
        $query->whereHas('variants', function ($q) use ($r) {
            if ($r->filled('size')) {
                $q->where('size', $r->size);
            }
            if ($r->filled('color')) {
                $q->where('color', $r->color);
            }
            if ($r->stock) {
                $q->whereRaw('stock - reserved > 0');
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

        return view('store.catalog', [
            'products' => $query->paginate(12)->withQueryString(), 'title' => $title,
            'colors' => Variant::query()->whereHas('product', fn ($q) => $q->published())->whereNotNull('color')->distinct()->orderBy('color')->pluck('color'),
            'fabrics' => Product::published()->whereNotNull('fabric')->distinct()->orderBy('fabric')->pluck('fabric'),
        ]);
    }

    /** Match names, fabric, descriptions and category in English or Bangla, including common spelling variants. */
    private function applySearch(Builder $query, string $text): void
    {
        $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $columns = ['name', 'name_bn', 'fabric', 'fabric_bn', 'description', 'description_bn'];
        $query->where(function (Builder $q) use ($text, $like, $columns) {
            foreach (SearchTerms::expand($text) as $term) {
                $pattern = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                foreach ($columns as $column) {
                    $q->orWhere($column, $like, $pattern);
                }
                $q->orWhereHas('category', fn (Builder $c) => $c->where('name', $like, $pattern)->orWhere('name_bn', $like, $pattern));
            }
        });
    }

    public function product(string $slug): View
    {
        $p = Product::published()->withRating()->with(['media', 'variants.media', 'category'])->where('slug', $slug)->firstOrFail();
        $reviews = $p->approvedReviews()->latest()->limit(20)->get();

        return view('store.product', ['product' => $p, 'reviews' => $reviews, 'related' => Product::published()->withRating()->with(['media', 'variants', 'category'])->where('category_id', $p->category_id)->whereKeyNot($p->id)->limit(4)->get()]);
    }

    public function review(Request $r, string $slug): RedirectResponse
    {
        $product = Product::published()->where('slug', $slug)->firstOrFail();
        abort_if($r->filled('website'), 422);
        $data = $r->validate(['name' => [$r->user() ? 'nullable' : 'required', 'string', 'max:100'], 'rating' => 'required|integer|between:1,5', 'body' => 'required|string|min:10|max:2000']);
        $user = $r->user();
        $verified = $user && Order::where('user_id', $user->id)->where('status', 'delivered')->whereHas('items', fn ($q) => $q->whereIn('variant_id', $product->variants()->select('id')))->exists();
        $attributes = ['name' => $data['name'] ?: $user?->name ?: 'Customer', 'rating' => $data['rating'], 'body' => $data['body'], 'status' => 'pending', 'verified_purchase' => (bool) $verified];
        if ($user) {
            $product->reviews()->updateOrCreate(['user_id' => $user->id], $attributes);
        } else {
            $product->reviews()->create($attributes);
        }

        return redirect()->to(route('product', $product->slug).'#reviews')->with('status', __('Thank you! Your review will appear after our team approves it.'));
    }

    public function page(string $slug): View
    {
        return view('store.page', ['page' => Page::where('slug', $slug)->where('published', true)->firstOrFail()]);
    }

    public function wishlist(): View
    {
        return view('store.wishlist', ['products' => Product::published()->withRating()->with(['media', 'variants', 'category'])->whereIn('id', Wishlist::where('user_id', auth()->id())->select('product_id'))->get()]);
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
