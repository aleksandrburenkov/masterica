<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /kabinet',
            'Disallow: /oformlenie',
            'Disallow: /admin',
            'Disallow: /korzina',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
