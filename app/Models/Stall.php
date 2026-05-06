<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Stall extends Model
{
    use HasFactory;

    protected $table = 'Stall';

    protected $fillable = [
        'stall_number',
        'stall_status',
        'length_meters',
        'width_meters',
        'area_sqm',
        'address',
        'remarks',
        'stall_image_path',
    ];

    protected $casts = [
        'length_meters' => 'decimal:2',
        'width_meters' => 'decimal:2',
        'area_sqm' => 'decimal:2',
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
     * Get the next numeric stall number from existing stall labels.
     */
    public static function nextStallNumber(): string
    {
        return self::nextStallNumberFrom(static::query()->pluck('stall_number'));
    }

    /**
     * Resolve the next stall number from a prepared list of existing labels.
     */
    public static function nextStallNumberFrom(Collection $stallNumbers): string
    {
        $highestStallNumber = $stallNumbers
            ->map(fn ($stallNumber) => self::numericStallNumberValue((string) $stallNumber))
            ->max() ?? 0;

        return (string) ($highestStallNumber + 1);
    }

    /**
     * Extract the last numeric segment from labels like "7" or "Stall 7".
     */
    public static function numericStallNumberValue(string $stallNumber): int
    {
        preg_match_all('/\d+/', $stallNumber, $matches);

        if (empty($matches[0])) {
            return 0;
        }

        $numericSegments = $matches[0];

        return (int) end($numericSegments);
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
