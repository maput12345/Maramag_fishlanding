<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    use HasFactory;

    protected $table = 'Buyer';

    protected $fillable = [
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

    /**
     * Get the buyer's full name.
     */
    public function getNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()->implode(' ');
    }

    /**
     * Resolve a buyer record from sale form inputs.
     */
    public static function resolveForSale(string $name, ?string $contact = null): self
    {
        $nameParts = User::splitName($name);

        return static::firstOrCreate([
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'contact' => $contact ?: null,
        ]);
    }
}
