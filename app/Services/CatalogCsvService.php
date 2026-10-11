<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CatalogCsvService
{
    public const HEADERS = ['product_slug', 'sku', 'size', 'color', 'price', 'sale_price', 'stock'];

    public function import(string $path): int
    {
        $stream = fopen($path, 'rb');
        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            if ($header !== self::HEADERS) {
                throw ValidationException::withMessages(['csv' => 'Headers must be: '.implode(',', self::HEADERS)]);
            }
            $rows = [];
            $errors = [];
            $seen = [];
            $line = 1;
            while (($values = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $line++;
                if ($line > 5001) {
                    $errors['csv'][] = 'Maximum 5,000 rows per import.';
                    break;
                }
                if (count($values) !== count($header)) {
                    $errors['csv'][] = "Row $line: incorrect column count.";

                    continue;
                }
                $row = array_combine($header, $values);
                $row['sale_price'] = $row['sale_price'] === '' ? null : $row['sale_price'];
                $validator = Validator::make($row, [
                    'product_slug' => 'required|exists:products,slug', 'sku' => 'required|string|max:255',
                    'size' => 'required|string|max:30', 'color' => 'required|string|max:50',
                    'price' => 'required|integer|min:1|max:100000000',
                    'sale_price' => 'nullable|integer|min:1|lte:price', 'stock' => 'required|integer|min:0|max:1000000',
                ]);
                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $e) {
                        $errors['csv'][] = "Row $line: $e";
                    }

                    continue;
                }
                $product = Product::where('slug', $row['product_slug'])->first();
                $existing = Variant::where('sku', $row['sku'])->first();
                $combination = $product->id.'|'.$row['size'].'|'.$row['color'];
                if (isset($seen[$row['sku']]) || isset($seen[$combination]) || ($existing && $existing->product_id !== $product->id) ||
                    Variant::where('product_id', $product->id)->where('size', $row['size'])->where('color', $row['color'])->where('sku', '!=', $row['sku'])->exists() ||
                    ($existing && $row['stock'] < $existing->reserved)) {
                    $errors['csv'][] = "Row $line: duplicate variant, SKU mismatch, or stock below reservations.";

                    continue;
                }
                $seen[$row['sku']] = true;
                $seen[$combination] = true;
                $rows[] = $row + ['product_id' => $product->id];
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    $v = Variant::where('sku', $row['sku'])->lockForUpdate()->first();
                    if (! $v) {
                        $v = Variant::create(collect($row)->except(['product_slug', 'stock'])->all());
                    } else {
                        $v->update(collect($row)->except(['product_slug', 'stock'])->all());
                    }
                    $delta = (int) $row['stock'] - $v->stock;
                    if ($delta) {
                        app(InventoryService::class)->adjust($v->id, $delta, 'CSV import', auth()->id());
                    }
                }
            }, 5);

            return count($rows);
        } finally {
            fclose($stream);
        }
    }

    public function export($stream): void
    {
        fputcsv($stream, self::HEADERS, ',', '"', '');
        foreach (Variant::with('product')->orderBy('id')->cursor() as $v) {
            $row = [$v->product->slug, $v->sku, $v->size, $v->color, $v->price, $v->sale_price, $v->stock];
            $row = array_map(fn ($value) => preg_match('/^[=+@\-\t\r]/', (string) $value) ? "'".$value : $value, $row);
            fputcsv($stream, $row, ',', '"', '');
        }
    }
}
