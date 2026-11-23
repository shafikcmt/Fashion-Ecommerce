<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function product(): Product
    {
        $product = Product::factory()->create(['name' => 'Rated panjabi']);
        Variant::factory()->create(['product_id' => $product->id]);

        return $product;
    }

    public function test_guest_review_is_saved_as_pending_and_hidden(): void
    {
        $product = $this->product();
        $this->post(route('reviews.store', $product->slug), ['name' => 'Rahim', 'rating' => 5, 'body' => 'Very good quality fabric.'])->assertRedirect();
        $review = ProductReview::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertFalse($review->verified_purchase);
        $this->get(route('product', $product->slug))->assertOk()->assertDontSee('Very good quality fabric.')->assertSee('No reviews yet');
    }

    public function test_only_approved_reviews_are_shown_and_counted(): void
    {
        $product = $this->product();
        ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 5, 'body' => 'Approved praise here.']);
        ProductReview::factory()->create(['product_id' => $product->id, 'rating' => 3, 'body' => 'Approved fair review.']);
        ProductReview::factory()->pending()->create(['product_id' => $product->id, 'rating' => 1, 'body' => 'Pending angry words.']);
        $page = $this->get(route('product', $product->slug))->assertOk();
        $page->assertSee('Approved praise here.')->assertDontSee('Pending angry words.')->assertSee('4.0 (2)')->assertSee('"ratingValue":4', false);
        $this->get(route('catalog'))->assertOk()->assertSee('4.0 (2)');
    }

    public function test_rating_and_text_are_validated(): void
    {
        $product = $this->product();
        $this->post(route('reviews.store', $product->slug), ['name' => 'A', 'rating' => 6, 'body' => 'Long enough text'])->assertSessionHasErrors('rating');
        $this->post(route('reviews.store', $product->slug), ['name' => 'A', 'rating' => 4, 'body' => 'short'])->assertSessionHasErrors('body');
        $this->post(route('reviews.store', $product->slug), ['rating' => 4, 'body' => 'Long enough text'])->assertSessionHasErrors('name');
        $this->assertSame(0, ProductReview::count());
    }

    public function test_review_text_is_escaped(): void
    {
        $product = $this->product();
        ProductReview::factory()->create(['product_id' => $product->id, 'body' => '<script>alert(1)</script> nice']);
        $this->get(route('product', $product->slug))->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_honeypot_blocks_bots(): void
    {
        $product = $this->product();
        $this->post(route('reviews.store', $product->slug), ['name' => 'Bot', 'rating' => 5, 'body' => 'Spam spam spam spam', 'website' => 'http://spam.test'])->assertStatus(422);
        $this->assertSame(0, ProductReview::count());
    }

    public function test_a_signed_in_customer_keeps_one_review_per_product_and_edits_reset_to_pending(): void
    {
        $product = $this->product();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('reviews.store', $product->slug), ['rating' => 4, 'body' => 'First impression good.']);
        ProductReview::query()->update(['status' => 'approved']);
        $this->actingAs($user)->post(route('reviews.store', $product->slug), ['rating' => 5, 'body' => 'Even better after washing.']);
        $this->assertSame(1, ProductReview::count());
        $review = ProductReview::firstOrFail();
        $this->assertSame(5, $review->rating);
        $this->assertSame('pending', $review->status);
        $this->assertSame($user->name, $review->name);
    }
}
