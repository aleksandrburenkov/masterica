<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use App\Models\Course;
use App\Models\Masterclass;
use App\Models\Product;
use App\Support\LegalDocuments;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        $static = [
            'home' => ['priority' => '1.0', 'freq' => 'daily'],
            'about' => ['priority' => '0.8', 'freq' => 'monthly'],
            'how-classes-work' => ['priority' => '0.7', 'freq' => 'monthly'],
            'masterclasses.index' => ['priority' => '0.9', 'freq' => 'weekly'],
            'masterclasses.bryansk' => ['priority' => '0.8', 'freq' => 'weekly'],
            'outreach.index' => ['priority' => '0.7', 'freq' => 'monthly'],
            'shop.index' => ['priority' => '0.8', 'freq' => 'weekly'],
            'courses.index' => ['priority' => '0.8', 'freq' => 'weekly'],
            'reviews.index' => ['priority' => '0.6', 'freq' => 'weekly'],
            'guides.index' => ['priority' => '0.6', 'freq' => 'monthly'],
            'contacts' => ['priority' => '0.7', 'freq' => 'monthly'],
        ];

        foreach ($static as $name => $meta) {
            $urls[] = [
                'loc' => route($name),
                'changefreq' => $meta['freq'],
                'priority' => $meta['priority'],
                'lastmod' => now()->toDateString(),
            ];
        }

        foreach (Masterclass::query()->published()->get() as $item) {
            $urls[] = ['loc' => route('masterclasses.show', $item->slug), 'changefreq' => 'weekly', 'priority' => '0.7', 'lastmod' => $item->updated_at->toDateString()];
        }
        foreach (Product::query()->published()->get() as $item) {
            $urls[] = ['loc' => route('shop.show', $item->slug), 'changefreq' => 'weekly', 'priority' => '0.6', 'lastmod' => $item->updated_at->toDateString()];
        }
        foreach (Course::query()->published()->get() as $item) {
            $urls[] = ['loc' => route('courses.show', $item->slug), 'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => $item->updated_at->toDateString()];
        }
        foreach (ContentPage::query()->published()->get() as $item) {
            $urls[] = ['loc' => route('guides.show', $item->slug), 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => $item->updated_at->toDateString()];
        }
        foreach (LegalDocuments::all() as $document) {
            $urls[] = ['loc' => route('legal.show', $document->slug), 'changefreq' => 'yearly', 'priority' => '0.3', 'lastmod' => $document->updated_at->toDateString()];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($url['loc']).'</loc>'."\n";
            $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
