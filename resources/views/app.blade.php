<!DOCTYPE html>
<html lang="{{ isset($seo) ? 'es' : str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="theme-color" content="#121A42">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Inline, before Vue mounts: only the "system" appearance needs a
        client-side check (OS preference isn't known to the server). A
        stored "dark"/"light" value is already applied above via the
        $appearance-driven @class on <html>, set by HandleAppearance. --}}
        <script>
            (function () {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Search/social metadata of public pages (tenant websites), from the
        `seo` view data (App\Services\Signatures\StorefrontSeo). Rendered here,
        not with Inertia's <Head>, so crawlers and link previews get it
        without JavaScript or the SSR server. --}}
        @isset($seo)
            <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
            @if (($seo['robots'] ?? '') === 'noindex, nofollow')
                {{-- Private pages live at signed URLs: don't leak them to the
                sites they link to (e.g. wa.me) through the Referer header. --}}
                <meta name="referrer" content="no-referrer">
            @endif
            @isset($seo['description'])
                <meta name="description" content="{{ $seo['description'] }}">
            @endisset
            @isset($seo['canonical'])
                <link rel="canonical" href="{{ $seo['canonical'] }}">
                <meta property="og:url" content="{{ $seo['canonical'] }}">
            @endisset
            @if (($seo['robots'] ?? '') !== 'noindex, nofollow')
                <meta property="og:type" content="website">
                <meta property="og:locale" content="es_EC">
                <meta property="og:title" content="{{ $seo['title'] }}">
                <meta property="og:site_name" content="{{ $seo['site_name'] ?? '' }}">
                <meta name="twitter:title" content="{{ $seo['title'] }}">
                @isset($seo['description'])
                    <meta property="og:description" content="{{ $seo['description'] }}">
                    <meta name="twitter:description" content="{{ $seo['description'] }}">
                @endisset
                @isset($seo['image'])
                    <meta property="og:image" content="{{ $seo['image'] }}">
                    <meta property="og:image:alt" content="{{ $seo['image_alt'] ?? '' }}">
                    <meta name="twitter:card" content="summary_large_image">
                    <meta name="twitter:image" content="{{ $seo['image'] }}">
                    <link rel="preload" as="image" href="{{ $seo['image'] }}" fetchpriority="high">
                @else
                    <meta name="twitter:card" content="summary">
                @endisset
            @endif
            @isset($seo['json_ld'])
                <script type="application/ld+json">{!! json_encode($seo['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
            @endisset
        @endisset

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $seo['title'] ?? config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
