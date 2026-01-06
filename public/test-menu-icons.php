<!DOCTYPE html>
<html>
<head>
    <title>Menu Icons Test</title>
    <link rel="stylesheet" href="plugins/fontawesome-free-6.5.2-web/css/all.min.css">
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .icon-test { font-size: 24px; }
    </style>
</head>
<body>
    <h1>Menu Icons Database Test</h1>
    
    <?php
    // Load Laravel
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Get menu data
    $menus = DB::table('menus')
        ->select('id', 'name', 'icon', 'parent_id')
        ->orderBy('parent_id')
        ->orderBy('id')
        ->get();
    ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Icon Class</th>
                <th>Icon Preview</th>
                <th>Parent ID</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($menus as $menu): ?>
            <tr>
                <td><?= $menu->id ?></td>
                <td><?= htmlspecialchars($menu->name) ?></td>
                <td><code><?= htmlspecialchars($menu->icon ?? 'NULL') ?></code></td>
                <td class="icon-test">
                    <?php if($menu->icon): ?>
                        <i class="<?= htmlspecialchars($menu->icon) ?>"></i>
                    <?php else: ?>
                        <span style="color: red;">No icon</span>
                    <?php endif; ?>
                </td>
                <td><?= $menu->parent_id ?? 'NULL' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Icon Test Samples</h2>
    <div style="font-size: 24px; line-height: 2;">
        <i class="fa-solid fa-gauge-high"></i> fa-solid fa-gauge-high<br>
        <i class="fa-solid fa-gear"></i> fa-solid fa-gear<br>
        <i class="fa-solid fa-gears"></i> fa-solid fa-gears<br>
        <i class="fa-solid fa-money-check-dollar"></i> fa-solid fa-money-check-dollar<br>
        <i class="fa-solid fa-inbox"></i> fa-solid fa-inbox<br>
        <i class="fa-solid fa-print"></i> fa-solid fa-print<br>
    </div>
</body>
</html>
