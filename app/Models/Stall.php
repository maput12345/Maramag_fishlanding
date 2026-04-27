<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stall extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_number',
        'stall_status',
        'remarks',
        'stall_image_path',
    ];

    protected $appends = ['display_name', 'image_url', 'gallery_image_urls'];

    /**
     * Get application openings for this stall.
     */
    public function applicationOpenings(): HasMany
    {
        return $this->hasMany(ApplicationOpening::class, 'stall_id');
    }

    /**
     * Get the uploaded image gallery for this stall.
     */
    public function stallImages(): HasMany
    {
        return $this->hasMany(StallImage::class, 'stall_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Get the active broker occupying this stall.
     */
    public function broker(): HasOne
    {
        return $this->hasOne(Broker::class, 'stall_id');
    }

    /**
     * Display a readable stall label across the UI.
     */
    public function getDisplayNameAttribute(): string
    {
        return 'Stall ' . $this->stall_number;
    }

    /**
     * Expose a public URL for the uploaded stall image.
     */
    public function getImageUrlAttribute(): ?string
    {
        $imagePaths = $this->resolveGalleryImagePaths();

        return empty($imagePaths) ? null : asset('storage/' . $imagePaths[0]);
    }

    /**
     * Expose all uploaded stall image URLs for applicant galleries.
     *
     * @return array<int, string>
     */
    public function getGalleryImageUrlsAttribute(): array
    {
        return array_map(
            fn (string $path): string => asset('storage/' . $path),
            $this->resolveGalleryImagePaths()
        );
    }

    /**
     * Resolve gallery image paths with the primary image kept first.
     *
     * @return array<int, string>
     */
    private function resolveGalleryImagePaths(): array
    {
        $imagePaths = $this->relationLoaded('stallImages')
            ? $this->stallImages->pluck('image_path')->filter()->values()->all()
            : $this->stallImages()->pluck('image_path')->filter()->values()->all();

        if ($this->stall_image_path) {
            array_unshift($imagePaths, $this->stall_image_path);
        }

        return array_values(array_unique(array_filter($imagePaths)));
    }
}
