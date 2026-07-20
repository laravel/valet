<?php

namespace Valet;

class Database
{
    public $cli;

    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    public function setup()
    {
        $user = user();

        $this->cli->passthru(
            'mysql -u root -proot -e ' . escapeshellarg(
                "CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED VIA unix_socket; " .
                "ALTER USER '{$user}'@'localhost' IDENTIFIED VIA unix_socket; " .
                "GRANT ALL PRIVILEGES ON *.* TO '{$user}'@'localhost' WITH GRANT OPTION; " .
                "FLUSH PRIVILEGES;"
            )
        );

        info("Passwordless MySQL access configured for user '{$user}'.");
    }

    public function create($name)
    {
        $this->cli->passthru(
            'mysql -e ' . escapeshellarg("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
        );

        info("Database '{$name}' created successfully.");
    }
}
