<?php

require_once './cli/Valet/Drivers/ValetDriver.php';

foreach (scandir('./cli/Valet/Drivers') as $file) {
    $path = './cli/Valet/Drivers/'.$file;
    if (substr($file, 0, 1) !== '.' && ! is_dir($path)) {
        require_once $path;
    }
}

// Unset our loop variables to keep them from leaking into the user's app.
unset($file, $path);
