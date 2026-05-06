<?php

namespace App\Models;

use App\Constants\RoleStatusConstant;
use App\Constants\UserStatusConstant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'User';

    protected $fillable = [
        'email',
        'password',
        'status',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'contact_number',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
        'role',
        'address',
        'stall_name',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the broker profile for this user.
     */
    public function broker(): HasOne
    {
        return $this->hasOne(Broker::class);
    }

    /**
     * Get the employee profile for this user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get all roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'UserRoleAssignment')->withTimestamps();
    }

    /**
     * Get broker applications submitted by this user.
     */
    public function brokerApplications(): HasMany
    {
        return $this->hasMany(BrokerApplication::class, 'user_id');
    }

    /**
     * Compatibility accessor for legacy views expecting $model->user.
     */
    public function getUserAttribute(): self
    {
        return $this;
    }

    /**
     * Get the full display name from the related profile.
     */
    public function getNameAttribute(): string
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->name;
        }

        if ($this->relationLoaded('broker') && $this->broker) {
            return $this->broker->name;
        }

        if ($this->employee) {
            return $this->employee->name;
        }

        if ($this->broker) {
            return $this->broker->name;
        }

        if ($this->first_name || $this->last_name) {
            return collect([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->suffix,
            ])->filter()->implode(' ');
        }

        return Str::of(Str::before($this->email ?? ('user-' . $this->id), '@'))
            ->replace(['.', '_'], ' ')
            ->title()
            ->toString();
    }

    /**
     * Return the first assigned role for older code paths.
     */
    public function getRoleAttribute(): ?string
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->first()?->role_name;
        }

        return $this->roles()->value('role_name');
    }

    /**
     * Expose broker address through the user model.
     */
    public function getAddressAttribute(): ?string
    {
        return $this->broker?->address ?? ($this->attributes['address'] ?? null);
    }

    /**
     * Expose broker stall name through the user model.
     */
    public function getStallNameAttribute(): ?string
    {
        return $this->broker?->stall_name;
    }

    /**
     * Check whether the user has a given role.
     */
    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('role_name', $role);
        }

        return $this->roles()->where('role_name', $role)->exists();
    }

    /**
     * Check whether the user is a broker.
     */
    public function isBroker(): bool
    {
        return $this->hasRole(RoleStatusConstant::BROKER);
    }

    /**
     * Check whether the user is an applicant.
     */
    public function isApplicant(): bool
    {
        return $this->hasRole(RoleStatusConstant::APPLICANT);
    }

    /**
     * Check whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RoleStatusConstant::ADMIN);
    }

    /**
     * Check whether the user is a staff member.
     */
    public function isStaff(): bool
    {
        return $this->hasRole(RoleStatusConstant::STAFF);
    }

    /**
     * Check whether the account is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatusConstant::ACTIVE;
    }

    /**
     * Check whether the account is deactivated.
     */
    public function isDeactivated(): bool
    {
        return $this->status === UserStatusConstant::DEACTIVATED;
    }

    /**
     * Scope broker accounts.
     */
    public function scopeBrokers($query): Builder
    {
        return $query->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('role_name', RoleStatusConstant::BROKER);
        });
    }

    /**
     * Scope admin accounts.
     */
    public function scopeAdmins($query): Builder
    {
        return $query->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('role_name', RoleStatusConstant::ADMIN);
        });
    }

    /**
     * Scope staff accounts.
     */
    public function scopeStaff($query): Builder
    {
        return $query->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('role_name', RoleStatusConstant::STAFF);
        });
    }

    /**
     * Scope applicant accounts.
     */
    public function scopeApplicants($query): Builder
    {
        return $query->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('role_name', RoleStatusConstant::APPLICANT);
        });
    }

    /**
     * Scope active accounts.
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', UserStatusConstant::ACTIVE);
    }

    /**
     * Scope deactivated accounts.
     */
    public function scopeDeactivated($query): Builder
    {
        return $query->where('status', UserStatusConstant::DEACTIVATED);
    }

    /**
     * Create a new user together with its role-specific profile.
     */
    public static function createUserWithRole(array $userData, array $profileData = []): self
    {
        $nameParts = static::extractNameParts($profileData + $userData);
        $isApplicant = ($userData['role'] ?? null) === RoleStatusConstant::APPLICANT;

        $user = static::create([
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'status' => UserStatusConstant::ACTIVE,
            'first_name' => $isApplicant ? $nameParts['first_name'] : null,
            'middle_name' => $isApplicant ? $nameParts['middle_name'] : null,
            'last_name' => $isApplicant ? $nameParts['last_name'] : null,
            'suffix' => $isApplicant ? static::normalizeNullableName($profileData['suffix'] ?? $userData['suffix'] ?? null) : null,
            'contact_number' => $isApplicant ? static::normalizeNullableName($profileData['contact_number'] ?? $userData['contact_number'] ?? null) : null,
            'address' => $isApplicant ? static::normalizeNullableName($profileData['address'] ?? $userData['address'] ?? null) : null,
        ]);

        $role = Role::firstOrCreate(
            ['role_name' => $userData['role']],
            ['description' => ucfirst($userData['role']) . ' role']
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        $profilePayload = array_merge($profileData, $nameParts);

        if ($role->role_name === RoleStatusConstant::BROKER) {
            Broker::createProfile($user->id, $profilePayload);
        } elseif (in_array($role->role_name, [RoleStatusConstant::ADMIN, RoleStatusConstant::STAFF], true)) {
            Employee::createProfile($user->id, $profilePayload);
        }

        return $user->load('roles', 'broker', 'employee');
    }

    /**
     * Update the account status.
     */
    public function updateStatus(string $status): bool
    {
        $this->status = $status;

        return $this->save();
    }

    /**
     * Delete or deactivate the role-specific profile.
     */
    public function deleteProfile(): bool
    {
        if ($this->isBroker() && $this->broker) {
            return $this->broker->deleteBroker();
        }

        if ($this->employee) {
            $this->employee->delete();
        }

        $this->roles()->detach();

        return (bool) $this->delete();
    }

    /**
     * Update the role-specific profile details.
     */
    public function updateProfile(array $profileData): bool
    {
        if ($this->broker) {
            return $this->broker->updateProfile($profileData);
        }

        if ($this->employee) {
            return $this->employee->updateProfile($profileData);
        }

        return $this->update([
            'first_name' => trim((string) ($profileData['first_name'] ?? $this->first_name)),
            'middle_name' => static::normalizeNullableName($profileData['middle_name'] ?? $this->middle_name),
            'last_name' => static::normalizeNullableName($profileData['last_name'] ?? $this->last_name),
            'suffix' => static::normalizeNullableName($profileData['suffix'] ?? $this->suffix),
            'contact_number' => static::normalizeNullableName($profileData['contact_number'] ?? $this->contact_number),
            'address' => static::normalizeNullableName($profileData['address'] ?? $this->address),
        ]);
    }

    /**
     * Get the most useful profile object for forms and lists.
     */
    public function getProfile()
    {
        return $this->broker ?: ($this->employee ?: $this);
    }

    /**
     * Split a full name into first, middle, and last components.
     */
    public static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));

        if (count($parts) === 0) {
            return [
                'first_name' => '',
                'middle_name' => null,
                'last_name' => null,
            ];
        }

        if (count($parts) === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => null,
            ];
        }

        if (count($parts) === 2) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[1],
            ];
        }

        return [
            'first_name' => array_shift($parts),
            'middle_name' => implode(' ', array_slice($parts, 0, -1)),
            'last_name' => end($parts) ?: null,
        ];
    }

    /**
     * Extract name parts from explicit fields or a legacy full name field.
     */
    public static function extractNameParts(array $data, array $fallback = []): array
    {
        if (
            array_key_exists('first_name', $data) ||
            array_key_exists('middle_name', $data) ||
            array_key_exists('last_name', $data)
        ) {
            return [
                'first_name' => trim((string) ($data['first_name'] ?? ($fallback['first_name'] ?? ''))),
                'middle_name' => static::normalizeNullableName($data['middle_name'] ?? ($fallback['middle_name'] ?? null)),
                'last_name' => static::normalizeNullableName($data['last_name'] ?? ($fallback['last_name'] ?? null)),
            ];
        }

        if (!empty($data['name'])) {
            return static::splitName((string) $data['name']);
        }

        return [
            'first_name' => trim((string) ($fallback['first_name'] ?? '')),
            'middle_name' => static::normalizeNullableName($fallback['middle_name'] ?? null),
            'last_name' => static::normalizeNullableName($fallback['last_name'] ?? null),
        ];
    }

    /**
     * Normalize optional name fields to null when blank.
     */
    protected static function normalizeNullableName($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
