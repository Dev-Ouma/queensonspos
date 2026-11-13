<?php

namespace App\Support\AiSearch;

use Anthropic\Lib\Attributes\Constrained;
use Anthropic\Lib\Concerns\StructuredOutputModelTrait;
use Anthropic\Lib\Contracts\StructuredOutputModel;

/** Structured shape Claude returns when it reads a shopper's search. */
class SearchIntent implements StructuredOutputModel
{
    use StructuredOutputModelTrait;

    #[Constrained(description: 'Leftover words to match against product names, or null when the filters say it all')]
    public ?string $keywords = null;

    #[Constrained(description: 'One category slug from the allowed list, or null')]
    public ?string $category = null;

    #[Constrained(description: 'One colour from the allowed list, or null')]
    public ?string $color = null;

    #[Constrained(description: 'One size from the allowed list, or null')]
    public ?string $size = null;

    #[Constrained(description: 'Minimum price in whole Kenyan shillings, or null')]
    public ?int $min_price = null;

    #[Constrained(description: 'Maximum price in whole Kenyan shillings, or null')]
    public ?int $max_price = null;

    #[Constrained(description: 'One of: price_asc, price_desc, best_selling, or null')]
    public ?string $sort = null;
}
