<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantEntitlements;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * robots.txt and sitemap.xml of every domain (replaces the static
 * public/robots.txt, which couldn't differ per tenant). Tenant websites
 * list their public pages and keep the workspace and customers' signed
 * links out of crawlers; the central domain keeps the console out.
 */
class SeoController extends Controller
{
    public function __construct(private TenantEntitlements $entitlements) {}

    public function robots(Request $request): Response
    {
        $lines = ['User-agent: *'];

        if ($this->hasPublicWebsite($request)) {
            $lines = [
                ...$lines,
                'Allow: /$',
                'Allow: /solicitud$',
                'Disallow: /solicitud/',
                'Disallow: /firmas-electronicas',
                'Disallow: /login',
                'Disallow: /tenancy/',
                '',
                'Sitemap: '.url('/sitemap.xml'),
            ];
        } elseif ($this->isCentral($request)) {
            $lines = [...$lines, 'Disallow: /central', 'Disallow: /soporte/seguimiento'];
        } else {
            // A tenant without a public website: nothing to index.
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(Request $request): Response
    {
        $paths = match (true) {
            $this->hasPublicWebsite($request) => ['/' => '1.0', '/solicitud' => '0.8'],
            $this->isCentral($request) => ['/' => '1.0'],
            default => [],
        };

        $urls = collect($paths)->map(fn (string $priority, string $path) => sprintf(
            '<url><loc>%s</loc><changefreq>weekly</changefreq><priority>%s</priority></url>',
            e(url($path)),
            $priority,
        ))->implode('');

        return response(
            '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$urls.'</urlset>',
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    private function isCentral(Request $request): bool
    {
        return in_array($request->getHost(), config('tenancy.central_domains', []), true);
    }

    private function hasPublicWebsite(Request $request): bool
    {
        $tenant = tenant();

        return ! $this->isCentral($request)
            && $tenant instanceof Tenant
            && $this->entitlements->activeModules($tenant)->contains(config('signatures.module_slug', 'signatures'));
    }
}
