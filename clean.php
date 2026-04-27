<?php
$files = ['database.sql', 'config.php', 'style.css', 'index.php', 'coaches.php', 'rooms.php', 'activities.php'];
$diacs = ['ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t','Ă'=>'A','Â'=>'A','Î'=>'I','Ș'=>'S','Ț'=>'T'];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $content = preg_replace('/^\s*(--|\/\/).*$/m', '', $content);
        $content = str_replace(array_keys($diacs), array_values($diacs), $content);
        file_put_contents($file, $content);
    }
}
echo "Gata!";
