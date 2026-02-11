<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMenuAccess extends Model
{
    protected $table = 'group_menu_access';

    protected $fillable = [
        'group_id',
        'menu_id',
        'can_create',
        'can_read',
        'can_update',
        'can_delete'
    ];

    protected $casts = [
        'can_create' => 'boolean',
        'can_read' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
