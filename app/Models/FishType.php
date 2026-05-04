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
        return $this->belongsToMany(Broker::class, 'BrokerFishTypeAssignment')->withTimestamps();
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

    /**
     * Check if this fish type is already used by any fish boxes.
     */
    public function isUsed(?int $brokerId = null): bool
    {
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
            $query->whereHas('brokers', function ($brokerQuery) use ($brokerId) {
                $brokerQuery->where('Broker.id', $brokerId);
            });
        }

        return $query->get();
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
        $query = static::query()
            ->select(['id', 'name', 'description', 'created_at'])
            ->withCount('fishBoxes');

        if ($brokerId) {
            $query->whereHas('brokers', function ($brokerQuery) use ($brokerId) {
                $brokerQuery->where('Broker.id', $brokerId);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

}
