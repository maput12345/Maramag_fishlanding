<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    use HasFactory;

    protected $table = 'Buyer';

    protected $fillable = [
        'broker_id',
        'first_name',
        'middle_name',
        'last_name',
        'contact',
    ];

    protected $appends = [
        'name',
    ];

    /**
     * @return HasMany
     */
    public function sales(): HasMany
    {
        return $this->hasMany(SalesTransaction::class, 'buyer_id');
    }

    public function scopeForBroker(Builder $query, int $brokerId): Builder
    {
        return $query->where('broker_id', $brokerId);
    }

    /**
     * Get the buyer's full name.
     */
    public function getNameAttribute(): string
    {
        return collect([
            static::formatNamePart($this->first_name),
            static::formatNamePart($this->middle_name),
            static::formatNamePart($this->last_name),
        ])->filter()->implode(' ');
    }

    /**
     * Resolve a buyer record from sale form inputs.
     */
    public static function resolveForSale(string $name, ?string $contact = null, ?int $brokerId = null): self
    {
        $nameParts = User::splitName($name);

        return static::resolveForSaleParts(
            $nameParts['first_name'],
            $nameParts['middle_name'],
            $nameParts['last_name'],
            $contact,
            $brokerId
        );
    }

    /**
     * Resolve a buyer record from structured sale form inputs.
     */
    public static function resolveForSaleParts(string $firstName, ?string $middleName, string $lastName, ?string $contact = null, ?int $brokerId = null, ?int $buyerId = null): self
    {
        $buyerData = [
            'broker_id' => $brokerId,
            'first_name' => static::formatNamePart($firstName),
            'middle_name' => static::nullableNamePart($middleName),
            'last_name' => static::formatNamePart($lastName),
            'contact' => static::nullableText($contact),
        ];

        if ($buyerId && $brokerId) {
            $buyer = static::query()
                ->forBroker($brokerId)
                ->whereKey($buyerId)
                ->first();

            if ($buyer) {
                $buyer->fill($buyerData)->save();

                return $buyer;
            }
        }

        return static::firstOrCreate($buyerData);
    }

    public static function getRegularOptionsForBroker(int $brokerId)
    {
        return static::query()
            ->forBroker($brokerId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'contact'])
            ->map(fn (self $buyer): array => [
                'id' => $buyer->id,
                'name' => $buyer->name,
                'first_name' => $buyer->first_name,
                'middle_name' => $buyer->middle_name,
                'last_name' => $buyer->last_name,
                'contact' => $buyer->contact,
            ]);
    }

    public function updateDetails(string $firstName, ?string $middleName, string $lastName, ?string $contact = null): bool
    {
        return $this->fill([
            'first_name' => static::formatNamePart($firstName),
            'middle_name' => static::nullableNamePart($middleName),
            'last_name' => static::formatNamePart($lastName),
            'contact' => static::nullableText($contact),
        ])->save();
    }

    private static function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function nullableNamePart(?string $value): ?string
    {
        return static::nullableText(static::formatNamePart($value));
    }

    private static function formatNamePart(?string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $value !== '' ? mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8') : '';
    }
}
