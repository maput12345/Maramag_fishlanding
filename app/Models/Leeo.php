<?php

namespace App\Models;

use App\Constants\UserStatusConstant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leeo extends Model
{
    use HasFactory;

    protected $table = 'leeos';

    protected $fillable = [
        'office_name',
        'address',
        'contact',
        'status',
    ];

    /**
     * Get all employees assigned to this LEEO office.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'leeo_id');
    }

    /**
     * Get all brokers assigned to this LEEO office.
     */
    public function brokers(): HasMany
    {
        return $this->hasMany(Broker::class, 'leeo_id');
    }

    /**
     * Ensure a default LEEO record exists for single-office deployments.
     */
    public static function ensureDefault(): self
    {
        return static::firstOrCreate(
            ['office_name' => 'Maramag Fish Landing'],
            [
                'address' => 'Maramag Fish Landing',
                'contact' => 'N/A',
                'status' => UserStatusConstant::ACTIVE,
            ]
        );
    }
}
