<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'group_id');
    }

    public function menuAccess()
    {
        return $this->hasMany(GroupMenuAccess::class, 'group_id');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'group_menu_access', 'group_id', 'menu_id')
                    ->withPivot(['can_create', 'can_read', 'can_update', 'can_delete'])
                    ->withTimestamps();
    }
}
