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

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the brokers that can sell this fish type.
     */
    public function brokers(): BelongsToMany
    {
        return $this->belongsToMany(Broker::class, 'broker_fish_type')->withTimestamps();
    }

    /**
     * Get the fish boxes for this fish type.
     */
    public function fishBoxes(): HasMany
    {
        return $this->hasMany(FishBoxPurchase::class, 'fish_type_id');
    }

    /**
     * Get the recorded prices for this fish type.
     */
    public function prices(): HasManyThrough
    {
        return $this->hasManyThrough(
            FishPrice::class,
            BrokerFishType::class,
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
        return $this->hasMany(BrokerFishType::class, 'fish_type_id');
    }

    /**
     * Check if this fish type is already used by any fish boxes.
     */
    public function isUsed(?int $brokerId = null): bool
    {
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
                $brokerQuery->where('brokers.id', $brokerId);
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
        $query = static::query();

        if ($brokerId) {
            $query->whereHas('brokers', function ($brokerQuery) use ($brokerId) {
                $brokerQuery->where('brokers.id', $brokerId);
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
