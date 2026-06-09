<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\GroupMenuAccess;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Cache menu permission checks for the current request lifecycle.
     *
     * @var array<string, array<string, bool>>
     */
    protected array $menuPermissionCache = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'group_id',
        'area_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user group that owns the user.
     */
    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    /**
     * Get the area this user is restricted to (null = all areas).
     */
    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'area_id');
    }

    /**
     * Determine whether the user has the requested menu permission.
     */
    public function hasMenuPermission(string $menuCode, string $permission): bool
    {
        if (!$this->group_id) {
            return false;
        }

        $allowedPermissions = ['can_create', 'can_read', 'can_update', 'can_delete'];
        if (!in_array($permission, $allowedPermissions, true)) {
            return false;
        }

        if (isset($this->menuPermissionCache[$menuCode][$permission])) {
            return $this->menuPermissionCache[$menuCode][$permission];
        }

        $hasPermission = GroupMenuAccess::where('group_id', $this->group_id)
            ->whereHas('menu', function ($query) use ($menuCode) {
                $query->where('code', $menuCode);
            })
            ->where($permission, true)
            ->exists();

        $this->menuPermissionCache[$menuCode][$permission] = $hasPermission;

        return $hasPermission;
    }
}
