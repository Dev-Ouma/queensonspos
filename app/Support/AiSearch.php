<?php

namespace App\Support;

use Anthropic\Client;
use App\Support\AiSearch\SearchIntent;
use Illuminate\Support\Facades\Log;

/**
 * Turns a free-text shopper search ("red dress under 3k for a wedding")
 * into storefront filters. Uses Claude when ANTHROPIC_API_KEY is set and
 * AI search is switched on in Admin → Website CMS; otherwise, or on any
 * API failure, a local rule-based parser gives the same shape.
 */
class AiSearch
{
    public const FIELDS = ['keywords', 'category', 'color', 'size', 'min_price', 'max_price', 'sort'];

    /** @return array{filters: array, ai: bool} */
    public static function interpret(string $term, array $categories, array $colors, array $sizes): array
    {
        $term = trim(mb_substr($term, 0, 200));
        $useAi = (bool) (StorefrontContent::cached()['ai_search_enabled'] ?? true) && config('services.anthropic.key');

        if ($useAi) {
            $key = 'ai_search.'.sha1(mb_strtolower($term).'|'.implode(',', array_keys($categories)).'|'.implode(',', $colors).'|'.implode(',', $sizes));
            $filters = cache()->remember($key, now()->addDay(), fn () => self::claude($term, $categories, $colors, $sizes));
            if ($filters !== null) {
                return ['filters' => $filters, 'ai' => true];
            }
            cache()->forget($key);
        }

        return ['filters' => self::rules($term, $categories, $colors, $sizes), 'ai' => false];
    }

    private static function claude(string $term, array $categories, array $colors, array $sizes): ?array
    {
        try {
            $client = new Client(apiKey: config('services.anthropic.key'));
            $catalog = "Category slugs (name → slug):\n".collect($categories)->map(fn ($name, $slug) => "- $name → $slug")->implode("\n")
                ."\nColours: ".implode(', ', $colors)
                ."\nSizes: ".implode(', ', $sizes);

            $message = $client->messages->create(
                model: config('services.anthropic.search_model'),
                maxTokens: 1024,
                outputConfig: ['effort' => 'low', 'format' => SearchIntent::class],
                system: "You turn shopper searches for a Kenyan women's fashion store into filters. "
                    ."Only use values from the lists below; never invent a category, colour or size. "
                    ."Prices are in Kenyan shillings: \"3k\" means 3000. Map synonyms (e.g. \"heels\" to a shoes category, \"navy\" to Blue if Navy is absent). "
                    ."Put words that are not captured by a filter in keywords, dropping filler such as \"for\", \"a\", \"cheap\".\n\n".$catalog,
                messages: [['role' => 'user', 'content' => $term]],
            );

            if ($message->stopReason !== 'end_turn') {
                return null;
            }
            $intent = $message->parsedOutput();

            return self::clean((array) $intent, $categories, $colors, $sizes);
        } catch (\Throwable $e) {
            Log::warning('AI search failed, using rules', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** Rule-based fallback: picks out categories, colours, sizes and prices. */
    public static function rules(string $term, array $categories, array $colors, array $sizes): array
    {
        $text = ' '.mb_strtolower($term).' ';
        $out = array_fill_keys(self::FIELDS, null);

        if (preg_match('/(?:under|below|less than|max|<)\s*(?:kes|ksh)?\s*([\d,.]+)\s*(k)?\b/', $text, $m)) {
            $out['max_price'] = (int) round((float) str_replace(',', '', $m[1]) * (empty($m[2]) ? 1 : 1000));
            $text = str_replace($m[0], ' ', $text);
        }
        if (preg_match('/(?:over|above|more than|from|min|>)\s*(?:kes|ksh)?\s*([\d,.]+)\s*(k)?\b/', $text, $m)) {
            $out['min_price'] = (int) round((float) str_replace(',', '', $m[1]) * (empty($m[2]) ? 1 : 1000));
            $text = str_replace($m[0], ' ', $text);
        }
        if (preg_match('/\b(cheap|cheapest|affordable|budget)\b/', $text, $m)) {
            $out['sort'] = 'price_asc';
            $text = str_replace($m[0], ' ', $text);
        } elseif (preg_match('/\b(best ?sellers?|best selling|popular|trending)\b/', $text, $m)) {
            $out['sort'] = 'best_selling';
            $text = str_replace($m[0], ' ', $text);
        }

        foreach ($colors as $c) {
            if (preg_match('/\b'.preg_quote(mb_strtolower($c), '/').'\b/', $text)) {
                $out['color'] = $c;
                $text = preg_replace('/\b'.preg_quote(mb_strtolower($c), '/').'\b/', ' ', $text);
                break;
            }
        }
        foreach ($sizes as $s) {
            if (preg_match('/\bsize\s+'.preg_quote(mb_strtolower($s), '/').'\b/', $text, $m)) {
                $out['size'] = $s;
                $text = str_replace($m[0], ' ', $text);
                break;
            }
        }
        foreach ($categories as $slug => $name) {
            $n = mb_strtolower($name);
            $forms = array_unique([$n, preg_replace('/es$/', '', $n), preg_replace('/s$/', '', $n)]);
            usort($forms, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            if (preg_match('/\b(?:'.implode('|', array_map(fn ($x) => preg_quote($x, '/'), $forms)).')\b/', $text, $m)) {
                $out['category'] = $slug;
                $text = str_replace($m[0], ' ', $text);
                break;
            }
        }

        $words = array_diff(preg_split('/\s+/', trim(preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $text))), ['for', 'a', 'an', 'the', 'and', 'with', 'in', 'to', 'of', 'kes', 'ksh', 'size', '']);
        $out['keywords'] = $words ? implode(' ', $words) : null;

        return $out;
    }

    private static function clean(array $f, array $categories, array $colors, array $sizes): array
    {
        $out = array_fill_keys(self::FIELDS, null);
        $out['keywords'] = isset($f['keywords']) && trim((string) $f['keywords']) !== '' ? mb_substr(trim($f['keywords']), 0, 100) : null;
        $out['category'] = isset($f['category']) && array_key_exists($f['category'], $categories) ? $f['category'] : null;
        $out['color'] = in_array($f['color'] ?? null, $colors, true) ? $f['color'] : null;
        $out['size'] = in_array($f['size'] ?? null, $sizes, true) ? $f['size'] : null;
        $out['min_price'] = isset($f['min_price']) && $f['min_price'] > 0 ? (int) $f['min_price'] : null;
        $out['max_price'] = isset($f['max_price']) && $f['max_price'] > 0 ? (int) $f['max_price'] : null;
        $out['sort'] = in_array($f['sort'] ?? null, ['price_asc', 'price_desc', 'best_selling'], true) ? $f['sort'] : null;

        return $out;
    }
}
