<?php

namespace App\Models;

use App\Constants\FishBoxStatusConstant;
use App\Constants\UserStatusConstant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Broker extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'Broker';

    public const ADMIN_IMPERSONATION_SESSION_KEY = 'admin_impersonated_broker_id';
    public const ADMIN_IMPERSONATION_RETURN_URL_SESSION_KEY = 'admin_impersonation_return_url';
    public const ADMIN_SUPPORT_ACTIONS_SESSION_KEY = 'admin_broker_support_actions_enabled';

    protected $fillable = [
        'user_id',
        'application_id',
        'stall_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'business_name',
        'address',
        'contact_number',
        'stall_name',
        'broker_status',
        'approval_date',
    ];

    protected $appends = ['name', 'status'];

    protected $casts = [
        'approval_date' => 'date',
    ];

    /**
     * Get the linked user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the application that produced this broker profile.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(BrokerApplication::class, 'application_id');
    }

    /**
     * Get the assigned stall for this broker.
     */
    public function stall(): BelongsTo
    {
        return $this->belongsTo(Stall::class, 'stall_id');
    }

    /**
     * Get sales for this broker.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(SalesTransaction::class, 'broker_id');
    }

    /**
     * Get reusable fish boxes owned by this broker.
     */
    public function fishBoxes(): HasMany
    {
        return $this->hasMany(FishBox::class, 'broker_id');
    }

    /**
     * Get reusable fish boxes currently marked as missing.
     */
    public function missingFishBoxes(): HasMany
    {
        return $this->hasMany(FishBox::class, 'broker_id')
            ->where('box_status', FishBoxStatusConstant::MISSING);
    }

    /**
     * Get fish type assignments for this broker.
     */
    public function brokerFishTypes(): HasMany
    {
        return $this->hasMany(BrokerFishTypeAssignment::class, 'broker_id');
    }

    /**
     * Get fish types handled by this broker.
     */
    public function fishTypes(): BelongsToMany
    {
        return $this->belongsToMany(FishType::class, 'BrokerFishTypeAssignment')
            ->withPivot(['display_name', 'display_description'])
            ->withTimestamps();
    }

    /**
     * Get price history through fish type assignments.
     */
    public function fishPrices(): HasManyThrough
    {
        return $this->hasManyThrough(
            FishPriceRecord::class,
            BrokerFishTypeAssignment::class,
            'broker_id',
            'broker_fish_type_id',
            'id',
            'id'
        );
    }

    /**
     * Provide a computed broker name from the broker profile.
     */
    public function getNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter()->implode(' ') ?: ('Broker #' . $this->id);
    }

    /**
     * Provide a compatibility accessor for older stall-name based views.
     */
    public function getStallNameAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        return $this->stall?->display_name;
    }

    /**
     * Mirror the account status from the user.
     */
    public function getStatusAttribute(): ?string
    {
        return $this->user?->status;
    }

    /**
     * Scope active Broker.
     */
    public function scopeActive($query): Builder
    {
        return $query->whereHas('user', function ($userQuery) {
            $userQuery->where('status', UserStatusConstant::ACTIVE);
        });
    }

    /**
     * Scope deactivated Broker.
     */
    public function scopeDeactivated($query): Builder
    {
        return $query->whereHas('user', function ($userQuery) {
            $userQuery->where('status', UserStatusConstant::DEACTIVATED);
        });
    }

    /**
     * Scope brokers by user.
     */
    public function scopeByUser($query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Create a new broker profile.
     */
    public static function createProfile(int $userId, array $data): self
    {
        $nameParts = User::extractNameParts($data);

        return static::create([
            'user_id' => $userId,
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'suffix' => $data['suffix'] ?? null,
            'business_name' => $data['business_name'] ?? null,
            'address' => $data['address'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'stall_name' => $data['stall_name'] ?? null,
            'stall_id' => $data['stall_id'] ?? null,
            'application_id' => $data['application_id'] ?? null,
            'broker_status' => $data['broker_status'] ?? 'Active',
            'approval_date' => $data['approval_date'] ?? null,
        ]);
    }

    /**
     * Update broker profile data.
     */
    public function updateProfile(array $data): bool
    {
        $nameParts = User::extractNameParts($data, [
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
        ]);

        $updateData = [
            'first_name' => $nameParts['first_name'],
            'middle_name' => $nameParts['middle_name'],
            'last_name' => $nameParts['last_name'],
            'suffix' => $data['suffix'] ?? $this->suffix,
            'business_name' => $data['business_name'] ?? $this->business_name,
            'address' => $data['address'] ?? $this->address,
            'contact_number' => $data['contact_number'] ?? $this->contact_number,
        ];

        if (isset($data['stall_name'])) {
            $updateData['stall_name'] = $data['stall_name'];
        }

        if (array_key_exists('stall_id', $data)) {
            $updateData['stall_id'] = $data['stall_id'];
        }

        if (array_key_exists('broker_status', $data)) {
            $updateData['broker_status'] = $data['broker_status'];
        }

        if (array_key_exists('approval_date', $data)) {
            $updateData['approval_date'] = $data['approval_date'];
        }

        return $this->update($updateData);
    }

    /**
     * Update broker status by syncing the underlying user account.
     */
    public function updateStatus(string $status): bool
    {
        return (bool) $this->user?->update(['status' => $status]);
    }

    /**
     * Delete broker and deactivate the user account.
     */
    public function deleteBroker(): bool
    {
        $this->user->update(['status' => UserStatusConstant::DEACTIVATED]);

        return (bool) $this->delete();
    }

    /**
     * Resolve the broker profile ID for a user.
     */
    public static function getBrokerIdByUserId($userId): ?int
    {
        $authenticatedUser = Auth::user();

        if ($authenticatedUser && (int) $authenticatedUser->id === (int) $userId && $authenticatedUser->isAdmin()) {
            $impersonatedBroker = static::getImpersonatedBrokerForAdmin($authenticatedUser);

            if ($impersonatedBroker) {
                return $impersonatedBroker->id;
            }
        }

        $broker = self::where('user_id', $userId)->first();

        if ($broker) {
            return $broker->id;
        }

        return BrokerStaff::query()
            ->active()
            ->where('user_id', $userId)
            ->value('broker_id');
    }

    /**
     * Resolve the broker currently being impersonated by an admin user.
     */
    public static function getImpersonatedBrokerForAdmin(?User $user = null): ?self
    {
        $user ??= Auth::user();

        if (!$user || !$user->isAdmin()) {
            return null;
        }

        $brokerId = session(self::ADMIN_IMPERSONATION_SESSION_KEY);

        if (!$brokerId) {
            return null;
        }

        $broker = static::query()
            ->with('user:id,email,status')
            ->find($brokerId);

        if (!$broker) {
            static::stopAdminImpersonation();

            return null;
        }

        return $broker;
    }

    /**
     * Determine whether the given admin is currently impersonating a broker.
     */
    public static function isAdminImpersonatingBroker(?User $user = null): bool
    {
        return static::getImpersonatedBrokerForAdmin($user) !== null;
    }

    /**
     * Determine whether admin support actions are enabled for the current broker-view session.
     */
    public static function areAdminBrokerSupportActionsEnabled(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (!$user || !$user->isAdmin() || !static::isAdminImpersonatingBroker($user)) {
            return false;
        }

        return (bool) session(self::ADMIN_SUPPORT_ACTIONS_SESSION_KEY, false);
    }

    /**
     * Determine whether the current admin broker-view session should stay read-only.
     */
    public static function isAdminBrokerViewReadOnly(?User $user = null): bool
    {
        $user ??= Auth::user();

        return static::isAdminImpersonatingBroker($user)
            && !static::areAdminBrokerSupportActionsEnabled($user);
    }

    /**
     * Store the broker view context for the current admin session.
     */
    public static function startAdminImpersonation(self $broker, ?string $returnUrl = null): void
    {
        session([
            self::ADMIN_IMPERSONATION_SESSION_KEY => $broker->id,
            self::ADMIN_IMPERSONATION_RETURN_URL_SESSION_KEY => $returnUrl,
            self::ADMIN_SUPPORT_ACTIONS_SESSION_KEY => false,
        ]);
    }

    /**
     * Enable broker write actions for the current admin broker-view session.
     */
    public static function enableAdminBrokerSupportActions(): void
    {
        session([self::ADMIN_SUPPORT_ACTIONS_SESSION_KEY => true]);
    }

    /**
     * Disable broker write actions for the current admin broker-view session.
     */
    public static function disableAdminBrokerSupportActions(): void
    {
        session([self::ADMIN_SUPPORT_ACTIONS_SESSION_KEY => false]);
    }

    /**
     * Clear the broker view context for the current admin session.
     */
    public static function stopAdminImpersonation(): void
    {
        session()->forget([
            self::ADMIN_IMPERSONATION_SESSION_KEY,
            self::ADMIN_IMPERSONATION_RETURN_URL_SESSION_KEY,
            self::ADMIN_SUPPORT_ACTIONS_SESSION_KEY,
        ]);
    }

    /**
     * Get the admin return URL saved when broker view started.
     */
    public static function getAdminImpersonationReturnUrl(): ?string
    {
        return session(self::ADMIN_IMPERSONATION_RETURN_URL_SESSION_KEY);
    }

    /**
     * Create a broker profile from a winning application.
     */
    public static function createFromApplication(BrokerApplication $application): self
    {
        $selectedStall = $application->selectedStall ?: $application->applicationOpening?->stall;

        return static::create([
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'stall_id' => $selectedStall?->id,
            'first_name' => $application->first_name,
            'middle_name' => $application->middle_name,
            'last_name' => $application->last_name,
            'suffix' => $application->suffix,
            'business_name' => $application->business_name,
            'address' => $application->address,
            'contact_number' => $application->contact_number,
            'stall_name' => $selectedStall?->display_name,
            'broker_status' => 'Active',
            'approval_date' => now()->toDateString(),
        ]);
    }
}
