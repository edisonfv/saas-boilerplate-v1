<?php

namespace App\Rules;

use App\Models\SignatureProduct;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A distributor's retail price can't go below the floor central set for
 * the product (`signature_products.min_retail_price`). The product is the
 * given id or, for maps keyed by product id (`prices.{id}`), the last
 * segment of the attribute.
 */
class RetailPriceFloor implements ValidationRule
{
    public function __construct(private ?string $productId = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $product = SignatureProduct::query()->find($this->productId ?? Str::afterLast($attribute, '.'));

        if ($product !== null && ! $product->allowsRetailPrice($value)) {
            $fail("El precio de «{$product->name}» no puede ser menor a {$product->min_retail_price} USD (precio mínimo de venta).");
        }
    }
}
