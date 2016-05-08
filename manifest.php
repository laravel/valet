<?php

$domain = $valetConfig['domain'];

$paths = [];

foreach ($valetConfig['paths'] as $path) {
    $paths[$path] = [];
    foreach (scandir($path) as $directory) {
        if (is_dir($path.'/'.$directory) && !in_array($directory, ['.', '..'])) {
            $paths[$path][] = "http://{$directory}.$domain";
        }
    }
    $paths[$path] = array_filter($paths[$path]);
}

$paths = array_filter($paths);

?>
<html>
    <head>
        <title>Valet</title>

        <style>
            body {
                font-family: monospace;
                margin: 20px;
            }
            ul {
                padding-top: .2em;
                padding-bottom: .2em;
            }
            li {
                padding: .2em 0;
            }
        </style>
    </head>
    <body>
        <h2>Your <a href="https://laravel.com/docs/valet">Valet</a> Sites</h2>
        <?php

        if (count($paths)) {

            echo '<ul>'.PHP_EOL;
            foreach ($paths as $path => $sites) {
                echo '<li>'.PHP_EOL;
                    echo $path.PHP_EOL;
                    echo '<ul>'.PHP_EOL;
                        foreach ($sites as $site) {
                            echo "<li><a href=\"$site\">$site</a></li>".PHP_EOL;
                        }
                    echo '</ul>'.PHP_EOL;
                echo '</li>'.PHP_EOL;
            }
            echo '</ul>'.PHP_EOL;

        } else {

            echo '<p>No <a href="https://laravel.com/docs/valet#serving-sites">served sites</a> found.</p>'.PHP_EOL;

        }

        ?>
        </ul>
    </body>
</html>
