<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menu "Dokumentasi" — panduan alur data SIREP sampai saldo kanban.
 *
 * Ditempatkan tepat di bawah Dashboard. Menu induk lain digeser satu urutan agar
 * posisinya tidak bergantung pada celah nomor yang kebetulan kosong.
 *
 * Aksesnya diberikan ke SELURUH grup: isinya panduan kerja, bukan data, dan justru
 * paling dibutuhkan operator yang haknya paling terbatas.
 */
return new class extends Migration
{
    private const KODE = 'documentation';

    public function up(): void
    {
        if (DB::table('menus')->where('code', self::KODE)->exists()) {
            return;
        }

        // Geser menu induk yang urutannya >= 2 supaya urutan 2 kosong untuk Dokumentasi.
        DB::table('menus')->whereNull('parent_id')->where('order', '>=', 2)->increment('order');

        $menuId = DB::table('menus')->insertGetId([
            'code'       => self::KODE,
            'name'       => 'Dokumentasi',
            'url'        => '/dokumentasi',
            'icon'       => 'fa-solid fa-book-open',
            'parent_id'  => null,
            'order'      => 2,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $akses = DB::table('user_groups')->pluck('id')->map(fn ($groupId) => [
            'group_id'   => $groupId,
            'menu_id'    => $menuId,
            'can_create' => 0,
            'can_read'   => 1,
            'can_update' => 0,
            'can_delete' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if (!empty($akses)) {
            DB::table('group_menu_access')->insert($akses);
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('code', self::KODE)->value('id');

        if (!$menuId) {
            return;
        }

        DB::table('group_menu_access')->where('menu_id', $menuId)->delete();
        DB::table('menus')->where('id', $menuId)->delete();
        DB::table('menus')->whereNull('parent_id')->where('order', '>', 2)->decrement('order');
    }
};
