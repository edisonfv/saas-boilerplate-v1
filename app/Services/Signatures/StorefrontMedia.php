<?php

namespace App\Services\Signatures;

use App\Models\SignatureStorefront;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photos of the tenant's public website banner (tenant context). Stored
 * on the tenant-suffixed "public" disk and served through stancl's
 * tenant asset route (tenant_asset()), so each tenant's files stay
 * isolated. The slide list (order, alt text) lives on the storefront row.
 */
class StorefrontMedia
{
    public function add(SignatureStorefront $storefront, UploadedFile $image, string $alt): void
    {
        $slides = $storefront->heroSlides();

        if (count($slides) >= SignatureStorefront::MaxHeroSlides) {
            throw new DomainException('El banner admite hasta '.SignatureStorefront::MaxHeroSlides.' fotos. Quita una para agregar otra.');
        }

        $id = (string) Str::uuid();
        $path = (string) $image->storeAs('storefront/banner', $id.'.'.$image->guessExtension(), $this->disk());

        $storefront->update(['hero_slides' => [...$slides, ['id' => $id, 'path' => $path, 'alt' => $alt]]]);
    }

    /**
     * Apply the order and alt texts edited on the settings page; slides
     * left out are removed (and their files deleted).
     *
     * @param  list<array{id: string, alt: string}>  $edited
     */
    public function sync(SignatureStorefront $storefront, array $edited): void
    {
        $current = [];

        foreach ($storefront->heroSlides() as $slide) {
            $current[$slide['id']] = $slide;
        }

        $kept = [];

        foreach ($edited as $slide) {
            if (isset($current[$slide['id']])) {
                $kept[] = ['id' => $slide['id'], 'path' => $current[$slide['id']]['path'], 'alt' => trim($slide['alt'])];
                unset($current[$slide['id']]);
            }
        }

        // Whatever wasn't kept was removed on the settings page.
        foreach ($current as $removed) {
            Storage::disk($this->disk())->delete($removed['path']);
        }

        $storefront->update(['hero_slides' => $kept]);
    }

    /**
     * Public URL of a stored banner photo.
     */
    public function url(string $path): string
    {
        return $this->disk() === 'public'
            ? tenant_asset($path)
            : Storage::disk($this->disk())->url($path);
    }

    /**
     * @return list<array{id: string, url: string, alt: string}>
     */
    public function slides(SignatureStorefront $storefront): array
    {
        return array_map(fn (array $slide) => [
            'id' => $slide['id'],
            'url' => $this->url($slide['path']),
            'alt' => $slide['alt'],
        ], $storefront->heroSlides());
    }

    private function disk(): string
    {
        return (string) config('signatures.media_disk', 'public');
    }
}
