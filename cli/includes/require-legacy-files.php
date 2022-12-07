<?php

foreach (scandir('./cli/includes/legacy') as $file) {
    if (substr($file, 0, 1) !== '.') {
        require_once './cli/includes/legacy/'.$file;
    }
}
