<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'code',
        'name',
        'url',
        'icon',
        'parent_id',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function groupAccess()
    {
        return $this->hasMany(GroupMenuAccess::class, 'menu_id');
    }

    public function groups()
    {
        return $this->belongsToMany(UserGroup::class, 'group_menu_access', 'menu_id', 'group_id')
                    ->withPivot(['can_create', 'can_read', 'can_update', 'can_delete'])
                    ->withTimestamps();
    }
}
