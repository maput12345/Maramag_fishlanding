<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class FishType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'FishType';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the brokers that can sell this fish type.
     */
    public function brokers(): BelongsToMany
    {
        return $this->belongsToMany(Broker::class, 'BrokerFishTypeAssignment')
            ->withPivot(['display_name', 'display_description'])
            ->withTimestamps();
    }

    /**
     * Get the fish boxes for this fish type.
     */
    public function fishBoxes(): HasMany
    {
        return $this->hasMany(FishBoxStockCycle::class, 'fish_type_id');
    }

    /**
     * Get the recorded prices for this fish type.
     */
    public function prices(): HasManyThrough
    {
        return $this->hasManyThrough(
            FishPriceRecord::class,
            BrokerFishTypeAssignment::class,
            'fish_type_id',
            'broker_fish_type_id',
            'id',
            'id'
        );
    }

    /**
     * Get the broker assignments for this fish type.
     */
    public function brokerFishTypes(): HasMany
    {
        return $this->hasMany(BrokerFishTypeAssignment::class, 'fish_type_id');
    }

    public function getDisplayNameAttribute(): ?string
    {
        return ($this->attributes['broker_display_name'] ?? null) ?: ($this->attributes['name'] ?? null);
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return ($this->attributes['broker_display_description'] ?? null) ?: ($this->attributes['description'] ?? null);
    }

    /**
     * Check if this fish type is already used by any fish boxes.
     */
    public function isUsed(?int $brokerId = null): bool
    {
        if ($brokerId !== null && array_key_exists('broker_fish_boxes_count', $this->attributes)) {
            return (int) $this->attributes['broker_fish_boxes_count'] > 0;
        }

        if ($brokerId === null && array_key_exists('fish_boxes_count', $this->attributes)) {
            return (int) $this->attributes['fish_boxes_count'] > 0;
        }

        if ($brokerId === null && $this->relationLoaded('fishBoxes')) {
            return $this->fishBoxes->isNotEmpty();
        }

        $query = $this->fishBoxes();

        if ($brokerId) {
            $query->whereHas('fishBox', function ($fishBoxQuery) use ($brokerId) {
                $fishBoxQuery->where('broker_id', $brokerId);
            });
        }

        return $query->exists();
    }

    // =============== DATABASE OPERATIONS =============== //

    /**
     * Get fish types by broker id
     *
     * @param int|null $brokerId
     *
     * @return Collection
     */
    public static function getFishTypeByBrokerId(?int $brokerId = null): Collection
    {
        $query = static::query();

        if ($brokerId) {
            $query->select([
                    'FishType.*',
                    'BrokerFishTypeAssignment.display_name as broker_display_name',
                    'BrokerFishTypeAssignment.display_description as broker_display_description',
                ])
                ->join('BrokerFishTypeAssignment', 'BrokerFishTypeAssignment.fish_type_id', '=', 'FishType.id')
                ->where('BrokerFishTypeAssignment.broker_id', $brokerId);
        }

        return $query->orderBy('FishType.name')->get();
    }

    /**
     * Get paginated fish types with search functionality
     *
     * @param string|null $search
     * @param int|null $brokerId
     * @param int $perPage
     *
     * @return LengthAwarePaginator
     */
    public static function getPaginatedWithSearch(?string $search = null, ?int $brokerId = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = static::query();

        if ($brokerId) {
            $query->select([
                    'FishType.id',
                    'FishType.name',
                    'FishType.description',
                    'FishType.created_at',
                    'BrokerFishTypeAssignment.display_name as broker_display_name',
                    'BrokerFishTypeAssignment.display_description as broker_display_description',
                ])
                ->join('BrokerFishTypeAssignment', 'BrokerFishTypeAssignment.fish_type_id', '=', 'FishType.id')
                ->where('BrokerFishTypeAssignment.broker_id', $brokerId)
                ->withCount([
                    'fishBoxes as broker_fish_boxes_count' => function ($fishBoxQuery) use ($brokerId) {
                        $fishBoxQuery->whereHas('fishBox', function ($query) use ($brokerId) {
                            $query->where('broker_id', $brokerId);
                        });
                    },
                ]);
        } else {
            $query->select([
                    'FishType.id',
                    'FishType.name',
                    'FishType.description',
                    'FishType.created_at',
                ])
                ->withCount('fishBoxes');
        }

        if ($search) {
            $query->where(function ($q) use ($search, $brokerId) {
                $q->where('FishType.name', 'like', '%' . $search . '%');

                if ($brokerId) {
                    $q->orWhere('BrokerFishTypeAssignment.display_name', 'like', '%' . $search . '%');
                }
            });
        }

        return $query->orderBy('FishType.created_at', 'desc')->paginate($perPage);
    }

}
