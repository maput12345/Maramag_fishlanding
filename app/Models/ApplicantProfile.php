<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantProfile extends Model
{
    use HasFactory;

    protected $table = 'ApplicantProfile';

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'address',
    ];

    protected $appends = ['name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter()->implode(' ');
    }

    public static function createProfile(int $userId, array $data): self
    {
        $nameParts = User::extractNameParts($data);

        return static::create([
            'user_id' => $userId,
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }

    public function updateProfile(array $data): bool
    {
        $nameParts = User::extractNameParts($data, [
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
        ]);

        return $this->update([
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'suffix' => $data['suffix'] ?? $this->suffix,
            'contact_number' => $data['contact_number'] ?? $this->contact_number,
            'address' => $data['address'] ?? $this->address,
        ]);
    }
}
