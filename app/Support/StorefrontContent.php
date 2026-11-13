<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Storefront marketing content, editable from Admin → Website CMS.
 * Stored as JSON in the settings table under storefront.* keys;
 * defaults() keeps the site rendering before anything is saved.
 */
class StorefrontContent
{
    public const KEYS = [
        'hero_slides', 'announcements', 'marquee', 'flash_sale',
        'testimonials', 'stats', 'socials', 'social_proof_enabled',
        'free_delivery_threshold_cents', 'promo_tiles', 'ai_search_enabled',
    ];

    public static function defaults(): array
    {
        return [
            'hero_slides' => [
                ['img' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=1800&auto=format&fit=crop', 'eyebrow' => 'Affordable Premium Fashion', 'line1' => 'Style beyond', 'line2' => 'seasons.'],
                ['img' => 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?q=80&w=1800&auto=format&fit=crop', 'eyebrow' => 'The New Collection', 'line1' => 'Dresses that', 'line2' => 'turn heads.'],
                ['img' => 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?q=80&w=1800&auto=format&fit=crop', 'eyebrow' => 'Office Edit 2026', 'line1' => 'Power meets', 'line2' => 'poise.'],
            ],
            'announcements' => [
                'Free Nairobi delivery on orders above KES 5,000',
                'New arrivals every Friday — first looks for members',
                '7-day easy returns on unworn items',
                'Style Beyond Seasons.',
            ],
            'marquee' => [
                'Free Nairobi delivery over KES 5,000', 'New arrivals every Friday', '7-day easy returns',
                'Affordable premium fashion', 'Dresses · Office wear · Shoes · Bags', 'Style beyond seasons',
            ],
            'flash_sale' => [
                'enabled' => true,
                'eyebrow' => 'Today Only',
                'title' => 'Midnight Edit',
                'highlight' => 'up to 20% off.',
                'subtitle' => 'Selected dresses, shoes and bags. Gone at midnight.',
                'cta' => 'Shop the Sale',
                'link' => '/shop',
            ],
            'testimonials' => [
                ['quote' => 'The blazer fits like it was tailored for me. Delivery to Westlands took under 20 hours.', 'name' => 'Wanjiru K.', 'city' => 'Nairobi'],
                ['quote' => 'Finally a Kenyan store where the photos match what arrives. The midi dress is my new favourite.', 'name' => 'Achieng O.', 'city' => 'Kisumu'],
                ['quote' => 'Ordered shoes and a handbag for a wedding — both arrived boxed beautifully, two days early.', 'name' => 'Fatma S.', 'city' => 'Mombasa'],
            ],
            'stats' => [
                ['label' => 'Happy Customers', 'target' => 4800, 'suffix' => '+'],
                ['label' => 'Styles in Store', 'target' => 650, 'suffix' => '+'],
                ['label' => 'Deliveries Made', 'target' => 12400, 'suffix' => '+'],
                ['label' => 'Years of Style', 'target' => 6, 'suffix' => ''],
            ],
            'socials' => [
                'instagram' => 'https://instagram.com/queensons',
                'tiktok' => 'https://tiktok.com/@queensons',
                'whatsapp' => 'https://wa.me/254100214198',
            ],
            'social_proof_enabled' => true,
            'free_delivery_threshold_cents' => 500000,
            'promo_tiles' => [
                ['eyebrow' => 'Workwear edit', 'title' => 'Office looks from KES 2,500', 'img' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=800&auto=format&fit=crop', 'href' => '/shop?category=office-wear'],
                ['eyebrow' => 'Finish the look', 'title' => 'Bags & accessories', 'img' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?q=80&w=800&auto=format&fit=crop', 'href' => '/shop?category=handbags'],
            ],
            'ai_search_enabled' => true,
        ];
    }

    public static function all(): array
    {
        $defaults = self::defaults();
        $stored = Setting::where('key', 'like', 'storefront.%')->pluck('value', 'key');

        foreach (self::KEYS as $key) {
            $raw = $stored->get("storefront.$key");
            if ($raw !== null) {
                $decoded = json_decode($raw, true);
                if ($decoded !== null || $raw === 'null') {
                    $defaults[$key] = $decoded;
                }
            }
        }

        return $defaults;
    }

    public static function save(array $content): void
    {
        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $content)) {
                Setting::updateOrCreate(
                    ['key' => "storefront.$key"],
                    ['value' => json_encode($content[$key])]
                );
            }
        }
        cache()->forget('storefront.content');
    }

    public static function cached(): array
    {
        return cache()->remember('storefront.content', 300, fn () => self::all());
    }
}
