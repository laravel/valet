<?php

use Illuminate\Container\Container;
use Silly\Application;
use Silly\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Valet\Drivers\ValetDriver;

use function Valet\info;
use function Valet\output;
use function Valet\table;
use function Valet\warning;
use function Valet\writer;

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
} elseif (file_exists(__DIR__.'/../../../autoload.php')) {
    require_once __DIR__.'/../../../autoload.php';
} else {
    require_once getenv('HOME').'/.composer/vendor/autoload.php';
}

/**
 * Create the application.
 */
Container::setInstance(new Container);

$version = '4.9.1';

$app = new Application('Laravel Valet', $version);

$app->setDispatcher($dispatcher = new EventDispatcher);

$dispatcher->addListener(
    ConsoleEvents::COMMAND,
    function (ConsoleCommandEvent $event) {
        writer($event->getOutput());
    });

Upgrader::onEveryRun();

$share_tools = [
    'cloudflared',
    'expose',
    'ngrok',
];

/**
 * Install Valet and any required services.
 */
$app->command('install', function (OutputInterface $output) {
    Nginx::stop();

    Configuration::install();
    output();
    Nginx::install();
    output();
    PhpFpm::install();
    output();
    DnsMasq::install(Configuration::read()['tld']);
    output();
    Site::renew();
    Nginx::restart();
    output();
    Valet::symlinkToUsersBin();

    output(PHP_EOL.'<info>Valet installed successfully!</info>');
})->descriptions('Install the Valet services');

/**
 * Output the status of Valet and its installed services and config.
 */
$app->command('status', function (OutputInterface $output) {
    info('Checking status...');

    $status = Status::check();

    if ($status['success']) {
        info("\nValet status: Healthy\n");
    } else {
        warning("\nValet status: Error\n");
    }

    table(['Check', 'Success?'], $status['output']);

    if ($status['success']) {
        return Command::SUCCESS;
    }

    info(PHP_EOL.'Debug suggestions:');
    info($status['debug']);

    return Command::FAILURE;
})->descriptions('Output the status of Valet and its installed services and config.');

/**
 * Most commands are available only if valet is installed.
 */
if (is_dir(VALET_HOME_PATH)) {
    /**
     * Upgrade helper: ensure the tld config exists and the loopback config exists.
     */
    Configuration::ensureBaseConfiguration();

    /**
     * Get or set the TLD currently being used by Valet.
     */
    $app->command('tld [tld]', function (InputInterface $input, OutputInterface $output, $tld = null) {
        if ($tld === null) {
            return output(Configuration::read()['tld']);
        }

        $helper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion(
            'Using a custom TLD is no longer officially supported and may lead to unexpected behavior. Do you wish to proceed? [y/N]',
            false
        );

        if ($helper->ask($input, $output, $question) === false) {
            return warning('No new Valet tld was set.');
        }

        DnsMasq::updateTld(
            $oldTld = Configuration::read()['tld'],
            $tld = trim($tld, '.')
        );

        Configuration::updateKey('tld', $tld);

        Site::resecureForNewConfiguration(['tld' => $oldTld], ['tld' => $tld]);
        PhpFpm::restart();
        Nginx::restart();

        info('Your Valet TLD has been updated to ['.$tld.'].');
    }, ['domain'])->descriptions('Get or set the TLD used for Valet sites.');

    /**
     * Get or set the loopback address currently being used by Valet.
     */
    $app->command('loopback [loopback]', function (InputInterface $input, OutputInterface $output, $loopback = null) {
        if ($loopback === null) {
            return output(Configuration::read()['loopback']);
        }

        if (filter_var($loopback, FILTER_VALIDATE_IP) === false) {
            return warning('['.$loopback.'] is not a valid IP address');
        }

        $oldLoopback = Configuration::read()['loopback'];

        Configuration::updateKey('loopback', $loopback);

        DnsMasq::refreshConfiguration();
        Site::aliasLoopback($oldLoopback, $loopback);
        Site::resecureForNewConfiguration(['loopback' => $oldLoopback], ['loopback' => $loopback]);
        PhpFpm::restart();
        Nginx::installServer();
        Nginx::restart();

        info('Your Valet loopback address has been updated to ['.$loopback.']');
    })->descriptions('Get or set the loopback address used for Valet sites');

    /**
     * Add the current working directory to the paths configuration.
     */
    $app->command('park [path]', function (OutputInterface $output, $path = null) {
        Configuration::addPath($path ?: getcwd());

        info(($path === null ? 'This' : "The [{$path}]")." directory has been added to Valet's paths.", $output);
    })->descriptions('Register the current working (or specified) directory with Valet');

    /**
     * Get all the current sites within paths parked with 'park {path}'.
     */
    $app->command('parked', function (OutputInterface $output) {
        $parked = Site::parked();

        table(['Site', 'SSL', 'URL', 'Path'], $parked->all());
    })->descriptions('Display all the current sites within parked paths');

    /**
     * Remove the current working directory from the paths configuration.
     */
    $app->command('forget [path]', function (OutputInterface $output, $path = null) {
        Configuration::removePath($path ?: getcwd());

        info(($path === null ? 'This' : "The [{$path}]")." directory has been removed from Valet's paths.");
    }, ['unpark'])->descriptions('Remove the current working (or specified) directory from Valet\'s list of paths');

    /**
     * Register a symbolic link with Valet.
     */
    $app->command('link [name] [--secure] [--isolate]', function ($name, $secure, $isolate) {
        $linkPath = Site::link(getcwd(), $name = $name ?: basename(getcwd()));

        info('A ['.$name.'] symbolic link has been created in ['.$linkPath.'].');

        if ($secure) {
            $this->runCommand('secure '.$name);
        }

        if ($isolate) {
            if (Site::phpRcVersion($name, getcwd())) {
                $this->runCommand('isolate --site='.$name);
            } else {
                warning('Valet could not determine which PHP version to use for this site.');
            }
        }
    })->descriptions('Link the current working directory to Valet', [
        '--secure' => 'Link the site with a trusted TLS certificate.',
        '--isolate' => 'Isolate the site to the PHP version specified in the current working directory\'s .valetrc file.',
    ]);

    /**
     * Display all of the registered symbolic links.
     */
    $app->command('links', function (OutputInterface $output) {
        $links = Site::links();

        table(['Site', 'SSL', 'URL', 'Path', 'PHP Version'], $links->all());
    })->descriptions('Display all of the registered Valet links');

    /**
     * Unlink a link from the Valet links directory.
     */
    $app->command('unlink [name]', function (OutputInterface $output, $name) {
        $name = Site::unlink($name);
        info('The ['.$name.'] symbolic link has been removed.');

        if (Site::isSecured($name)) {
            info('Unsecuring '.$name.'...');

            Site::unsecure(Site::domain($name));

            Nginx::restart();
        }
    })->descriptions('Remove the specified Valet link');

    /**
     * Secure the given domain with a trusted TLS certificate.
     */
    $app->command('secure [domain] [--expireIn=]', function (OutputInterface $output, $domain = null, $expireIn = 368) {
        $url = Site::domain($domain);

        Site::secure($url, null, $expireIn);

        Nginx::restart();

        info('The ['.$url.'] site has been secured with a fresh TLS certificate.');
    })->descriptions('Secure the given domain with a trusted TLS certificate', [
        '--expireIn' => 'The amount of days the self signed certificate is valid for. Default is set to "368"',
    ]);

    /**
     * Stop serving the given domain over HTTPS and remove the trusted TLS certificate.
     */
    $app->command('unsecure [domain] [--all]', function (OutputInterface $output, $domain = null, $all = null) {
        if ($all) {
            Site::unsecureAll();

            Nginx::restart();

            info('All Valet sites will now serve traffic over HTTP.');

            return;
        }

        $url = Site::domain($domain);

        Site::unsecure($url);

        Nginx::restart();

        info('The ['.$url.'] site will now serve traffic over HTTP.');
    })->descriptions('Stop serving the given domain over HTTPS and remove the trusted TLS certificate');

    /**
     * Display all of the currently secured sites.
     */
    $app->command('secured [--expiring] [--days=] [--ca]', function (OutputInterface $output, $expiring = null, $days = 60, $ca = null) {
        $now = (new Datetime)->add(new DateInterval('P'.$days.'D'));
        $sites = collect(Site::securedWithDates($ca))
            ->when($expiring, fn ($collection) => $collection->filter(fn ($row) => $row['exp'] < $now))
            ->map(function ($row) {
                return [
                    'Site' => $row['site'],
                    'Valid Until' => $row['exp']->format('Y-m-d H:i:s T'),
                ];
            })
            ->when($expiring, fn ($collection) => $collection->sortBy('Valid Until'));

        return table(['Site', 'Valid Until'], $sites->all());
    })->descriptions('Display all of the currently secured sites', [
        '--expiring' => 'Limits the results to only sites expiring within the next 60 days.',
        '--days' => 'To be used with --expiring. Limits the results to only sites expiring within the next X days. Default is set to 60.',
        '--ca' => 'Include the Certificate Authority certificate in the list of site certificates.',
    ]);

    /**
     * Renews all domains with a trusted TLS certificate.
     */
    $app->command('renew [--expireIn=] [--ca]', function (OutputInterface $output, $expireIn = 368, $ca = null) {
        Site::renew($expireIn, $ca);
        Nginx::restart();
    })->descriptions('Renews all domains with a trusted TLS certificate.', [
        '--expireIn' => 'The amount of days the self signed certificate is valid for. Default is set to "368"',
        '--ca' => 'Renew the Certificate Authority certificate before renewing the site certificates.',
    ]);

    /**
     * Create an Nginx proxy config for the specified domain.
     */
    $app->command('proxy domain host [--secure]', function (OutputInterface $output, $domain, $host, $secure) {
        Site::proxyCreate($domain, $host, $secure);
        Nginx::restart();
    })->descriptions('Create an Nginx proxy site for the specified host. Useful for docker, mailhog etc.', [
        '--secure' => 'Create a proxy with a trusted TLS certificate',
    ]);

    /**
     * Delete an Nginx proxy config.
     */
    $app->command('unproxy domain', function (OutputInterface $output, $domain) {
        Site::proxyDelete($domain);
        Nginx::restart();
    })->descriptions('Delete an Nginx proxy config.');

    /**
     * Display all of the sites that are proxies.
     */
    $app->command('proxies', function (OutputInterface $output) {
        $proxies = Site::proxies();

        table(['Site', 'SSL', 'URL', 'Host'], $proxies->all());
    })->descriptions('Display all of the proxy sites');

    /**
     * Display which Valet driver the current directory is using.
     */
    $app->command('which', function (OutputInterface $output) {
        $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

        if ($driver) {
            info('This site is served by ['.get_class($driver).'].');
        } else {
            warning('Valet could not determine which driver to use for this site.');
        }
    })->descriptions('Display which Valet driver serves the current working directory');

    /**
     * Display all of the registered paths.
     */
    $app->command('paths', function (OutputInterface $output) {
        $paths = Configuration::read()['paths'];

        if (count($paths) > 0) {
            output(json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            info('No paths have been registered.');
        }
    })->descriptions('Get all of the paths registered with Valet');

    /**
     * Open the current or given directory in the browser.
     */
    $app->command('open [domain]', function (OutputInterface $output, $domain = null) {
        $url = 'http://'.Site::domain($domain);
        CommandLine::runAsUser('open '.escapeshellarg($url));
    })->descriptions('Open the site for the current (or specified) directory in your browser');

    /**
     * Generate a publicly accessible URL for your project.
     */
    $app->command('share', function (OutputInterface $output) {
        warning('It looks like you are running `cli/valet.php` directly; please use the `valet` script in the project root instead.');
    })->descriptions('Generate a publicly accessible URL for your project');

    /**
     * Echo the currently tunneled URL.
     */
    $app->command('fetch-share-url [domain]', function ($domain = null) use ($share_tools) {
        $tool = Configuration::read()['share-tool'] ?? null;

        if ($tool && in_array($tool, $share_tools) && class_exists($tool)) {
            try {
                output($tool::currentTunnelUrl(Site::domain($domain)));
            } catch (\Throwable $e) {
                warning($e->getMessage());
            }
        } else {
            info('Please set your share tool with `valet share-tool`.');

            return Command::FAILURE;
        }
    })->descriptions('Get the URL to the current share tunnel');

    /**
     * Echo or set the name of the currently-selected share tool (either "ngrok" or "expose").
     */
    $app->command('share-tool [tool]', function (InputInterface $input, OutputInterface $output, $tool = null) use ($share_tools) {
        if ($tool === null) {
            return output(Configuration::read()['share-tool'] ?? '(not set)');
        }

        $share_tools_list = preg_replace('/,\s([^,]+)$/', ' or $1',
            implode(', ', array_map(fn ($t) => "`$t`", $share_tools)));

        if (! in_array($tool, $share_tools) || ! class_exists($tool)) {
            warning("$tool is not a valid share tool. Please use $share_tools_list.");

            return Command::FAILURE;
        }

        Configuration::updateKey('share-tool', $tool);
        info("Share tool set to $tool.");

        if (! $tool::installed()) {
            $helper = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion(
                'Would you like to install '.ucfirst($tool).' now? [y/N] ',
                false);

            if ($helper->ask($input, $output, $question) === false) {
                info('Proceeding without installing '.ucfirst($tool).'.');

                return;
            }

            $tool::ensureInstalled();
        }

        return Command::SUCCESS;
    })->descriptions('Get the name of the current share tool.');

    /**
     * Set the ngrok auth token.
     */
    $app->command('set-ngrok-token [token]', function (OutputInterface $output, $token = null) {
        output(Ngrok::setToken($token));
    })->descriptions('Set the Ngrok auth token');

    /**
     * Start the daemon services.
     */
    $app->command('start [service]', function (OutputInterface $output, $service) {
        switch ($service) {
            case '':
                DnsMasq::restart();
                PhpFpm::restart();
                Nginx::restart();

                return info('Valet services have been started.');
            case 'dnsmasq':
                DnsMasq::restart();

                return info('dnsmasq has been started.');
            case 'nginx':
                Nginx::restart();

                return info('Nginx has been started.');
            case 'php':
                PhpFpm::restart();

                return info('PHP has been started.');
        }

        return warning(sprintf('Invalid valet service name [%s]', $service));
    })->descriptions('Start the Valet services');

    /**
     * Restart the daemon services.
     */
    $app->command('restart [service]', function (OutputInterface $output, $service) {
        switch ($service) {
            case '':
                DnsMasq::restart();
                PhpFpm::restart();
                Nginx::restart();

                return info('Valet services have been restarted.');
            case 'dnsmasq':
                DnsMasq::restart();

                return info('dnsmasq has been restarted.');
            case 'nginx':
                Nginx::restart();

                return info('Nginx has been restarted.');
            case 'php':
                PhpFpm::restart();

                return info('PHP has been restarted.');
        }

        // Handle restarting specific PHP version (e.g. `valet restart php@8.2`)
        if (str_contains($service, 'php')) {
            PhpFpm::restart($normalized = PhpFpm::normalizePhpVersion($service));

            return info($normalized.' has been restarted.');
        }

        return warning(sprintf('Invalid valet service name [%s]', $service));
    })->descriptions('Restart the Valet services');

    /**
     * Stop the daemon services.
     */
    $app->command('stop [service]', function (OutputInterface $output, $service) {
        switch ($service) {
            case '':
                PhpFpm::stopRunning();
                Nginx::stop();

                return info('Valet core services have been stopped. To also stop dnsmasq, run: valet stop dnsmasq');
            case 'all':
                PhpFpm::stopRunning();
                Nginx::stop();
                Dnsmasq::stop();

                return info('All Valet services have been stopped.');
            case 'nginx':
                Nginx::stop();

                return info('Nginx has been stopped.');
            case 'php':
                PhpFpm::stopRunning();

                return info('PHP has been stopped.');
            case 'dnsmasq':
                Dnsmasq::stop();

                return info('dnsmasq has been stopped.');
        }

        return warning(sprintf('Invalid valet service name [%s]', $service));
    })->descriptions('Stop the core Valet services, or all services by specifying "all".');

    /**
     * Uninstall Valet entirely. Requires --force to actually remove; otherwise manual instructions are displayed.
     */
    $app->command('uninstall [--force]', function (InputInterface $input, OutputInterface $output, $force) {
        if ($force) {
            warning('YOU ARE ABOUT TO UNINSTALL Nginx, PHP, Dnsmasq and all Valet configs and logs.');
            $helper = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion('Are you sure you want to proceed? [y/N]', false);

            if ($helper->ask($input, $output, $question) === false) {
                return warning('Uninstall aborted.');
            }

            info('Removing certificates for all Secured sites...');
            Site::unsecureAll();
            info('Removing certificate authority...');
            Site::removeCa();
            info('Removing Nginx and configs...');
            Nginx::uninstall();
            info('Removing Dnsmasq and configs...');
            DnsMasq::uninstall();
            info('Removing loopback customization...');
            Site::uninstallLoopback();
            info('Removing Valet configs and customizations...');
            Configuration::uninstall();
            info('Removing PHP versions and configs...');
            PhpFpm::uninstall();
            info('Attempting to unlink Valet from bin path...');
            Valet::unlinkFromUsersBin();
            info('Removing sudoers entries...');
            Brew::removeSudoersEntry();
            Valet::removeSudoersEntry();

            return output(Valet::forceUninstallText());
        }

        output(Valet::uninstallText());

        // Stop PHP so the ~/.config/valet/valet.sock file is released so the directory can be deleted if desired
        PhpFpm::stopRunning();
        Nginx::stop();
    })->descriptions('Uninstall the Valet services', ['--force' => 'Do a forceful uninstall of Valet and related Homebrew pkgs']);

    /**
     * Determine if this is the latest release of Valet.
     */
    $app->command('on-latest-version', function (OutputInterface $output) use ($version) {
        if (Valet::onLatestVersion($version)) {
            output('Yes');
        } else {
            output(sprintf('Your version of Valet (%s) is not the latest version available.', $version));
            output('Upgrade instructions can be found in the docs: https://laravel.com/docs/valet#upgrading-valet');
        }
    }, ['latest'])->descriptions('Determine if this is the latest version of Valet');

    /**
     * Install the sudoers.d entries so password is no longer required.
     */
    $app->command('trust [--off]', function (OutputInterface $output, $off) {
        if ($off) {
            Brew::removeSudoersEntry();
            Valet::removeSudoersEntry();

            return info('Sudoers entries have been removed for Brew and Valet.');
        }

        Brew::createSudoersEntry();
        Valet::createSudoersEntry();

        info('Sudoers entries have been added for Brew and Valet.');
    })->descriptions('Add sudoers files for Brew and Valet to make Valet commands run without passwords', [
        '--off' => 'Remove the sudoers files so normal sudo password prompts are required.',
    ]);

    /**
     * Allow the user to change the version of php Valet uses.
     */
    $app->command('use [phpVersion] [--force]', function (OutputInterface $output, $phpVersion, $force) {
        if (! $phpVersion) {
            $site = basename(getcwd());
            $linkedVersion = Brew::linkedPhp();

            if ($phpVersion = Site::phpRcVersion($site, getcwd())) {
                info("Found '{$site}/.valetrc' or '{$site}/.valetphprc' specifying version: {$phpVersion}");
                info("Found '{$site}/.valetphprc' specifying version: {$phpVersion}");
            } else {
                $domain = $site.'.'.data_get(Configuration::read(), 'tld');
                if ($phpVersion = PhpFpm::normalizePhpVersion(Site::customPhpVersion($domain))) {
                    info("Found isolated site '{$domain}' specifying version: {$phpVersion}");
                }
            }

            if (! $phpVersion) {
                return info("Valet is using {$linkedVersion}.");
            }

            if ($linkedVersion == $phpVersion && ! $force) {
                return info("Valet is already using {$linkedVersion}.");
            }
        }

        PhpFpm::useVersion($phpVersion, $force);
    })->descriptions('Change the version of PHP used by Valet', [
        'phpVersion' => 'The PHP version you want to use; e.g. php@8.2',
    ]);

    /**
     * Allow the user to change the version of PHP Valet uses to serve the current site.
     */
    $app->command('isolate [phpVersion] [--site=]', function (OutputInterface $output, $phpVersion, $site = null) {
        if (! $site) {
            $site = basename(getcwd());
        }

        if (is_null($phpVersion)) {
            if ($phpVersion = Site::phpRcVersion($site, getcwd())) {
                info("Found '{$site}/.valetrc' or '{$site}/.valetphprc' specifying version: {$phpVersion}");
            } else {
                info(PHP_EOL.'Please provide a version number. E.g.:');
                info('valet isolate php@8.2');

                return;
            }
        }

        PhpFpm::isolateDirectory($site, $phpVersion);
    })->descriptions('Change the version of PHP used by Valet to serve the current working directory', [
        'phpVersion' => 'The PHP version you want to use; e.g php@8.1',
        '--site' => 'Specify the site to isolate (e.g. if the site isn\'t linked as its directory name)',
    ]);

    /**
     * Allow the user to un-do specifying the version of PHP Valet uses to serve the current site.
     */
    $app->command('unisolate [--site=]', function (OutputInterface $output, $site = null) {
        if (! $site) {
            $site = basename(getcwd());
        }

        PhpFpm::unIsolateDirectory($site);
    })->descriptions('Stop customizing the version of PHP used by Valet to serve the current working directory', [
        '--site' => 'Specify the site to un-isolate (e.g. if the site isn\'t linked as its directory name)',
    ]);

    /**
     * List isolated sites.
     */
    $app->command('isolated', function (OutputInterface $output) {
        $sites = PhpFpm::isolatedDirectories();

        table(['Path', 'PHP Version'], $sites->all());
    })->descriptions('List all sites using isolated versions of PHP.');

    /**
     * Get the PHP executable path for a site.
     */
    $app->command('which-php [site]', function (OutputInterface $output, $site) {
        $phpVersion = Site::customPhpVersion(
            Site::host($site ?: getcwd()).'.'.Configuration::read()['tld']
        );

        if (! $phpVersion) {
            $phpVersion = Site::phpRcVersion($site ?: basename(getcwd()));
        }

        return output(Brew::getPhpExecutablePath($phpVersion));
    })->descriptions('Get the PHP executable path for a given site', [
        'site' => 'The site to get the PHP executable path for',
    ]);

    /**
     * Proxy commands through to an isolated site's version of PHP.
     */
    $app->command('php [--site=] [command]', function (OutputInterface $output, $command) {
        warning('It looks like you are running `cli/valet.php` directly; please use the `valet` script in the project root instead.');
    })->descriptions("Proxy PHP commands with isolated site's PHP executable", [
        'command' => "Command to run with isolated site's PHP executable",
        '--site' => 'Specify the site to use to get the PHP version (e.g. if the site isn\'t linked as its directory name)',
    ]);

    /**
     * Proxy commands through to an isolated site's version of Composer.
     */
    $app->command('composer [--site=] [command]', function (OutputInterface $output, $command) {
        warning('It looks like you are running `cli/valet.php` directly; please use the `valet` script in the project root instead.');
    })->descriptions("Proxy Composer commands with isolated site's PHP executable", [
        'command' => "Composer command to run with isolated site's PHP executable",
        '--site' => 'Specify the site to use to get the PHP version (e.g. if the site isn\'t linked as its directory name)',
    ]);

    /**
     * Tail log file.
     */
    $app->command('log [-f|--follow] [-l|--lines=] [key]', function (OutputInterface $output, $follow, $lines, $key = null) {
        $defaultLogs = [
            'php-fpm' => BREW_PREFIX.'/var/log/php-fpm.log',
            'nginx' => VALET_HOME_PATH.'/Log/nginx-error.log',
        ];

        $configLogs = data_get(Configuration::read(), 'logs');
        if (! is_array($configLogs)) {
            $configLogs = [];
        }

        $logs = array_merge($defaultLogs, $configLogs);
        ksort($logs);

        if (! $key) {
            info(implode(PHP_EOL, [
                'In order to tail a log, pass the relevant log key (e.g. "nginx")',
                'along with any optional tail parameters (e.g. "-f" for follow).',
                null,
                'For example: "valet log nginx -f --lines=3"',
                null,
                'Here are the logs you might be interested in.',
                null,
            ]));

            table(
                ['Keys', 'Files'],
                collect($logs)->map(function ($file, $key) {
                    return [$key, $file];
                })->toArray()
            );

            info(implode(PHP_EOL, [
                null,
                'Tip: Set custom logs by adding a "logs" key/file object',
                'to your "'.Configuration::path().'" file.',
            ]));

            return;
        }

        if (! isset($logs[$key])) {
            return warning('No logs found for ['.$key.'].');
        }

        $file = $logs[$key];
        if (! file_exists($file)) {
            return warning('Log path ['.$file.'] does not (yet) exist.');
        }

        $options = [];
        if ($follow) {
            $options[] = '-f';
        }
        if ((int) $lines) {
            $options[] = '-n '.(int) $lines;
        }

        $command = implode(' ', array_merge(['tail'], $options, [$file]));

        passthru($command);
    })->descriptions('Tail log file');

    /**
     * Configure or display the directory-listing setting.
     */
    $app->command('directory-listing [status]', function (OutputInterface $output, $status = null) {
        $key = 'directory-listing';
        $config = Configuration::read();

        if (in_array($status, ['on', 'off'])) {
            $config[$key] = $status;
            Configuration::write($config);

            return output('Directory listing setting is now: '.$status);
        }

        $current = isset($config[$key]) ? $config[$key] : 'off';
        output('Directory listing is '.$current);
    })->descriptions('Determine directory-listing behavior. Default is off, which means a 404 will display.', [
        'status' => 'on or off. (default=off) will show a 404 page; [on] will display a listing if project folder exists but requested URI not found',
    ]);

    /**
     * Output diagnostics to aid in debugging Valet.
     */
    $app->command('diagnose [-p|--print] [--plain]', function (OutputInterface $output, $print, $plain) {
        info('Running diagnostics... (this may take a while)');

        Diagnose::run($print, $plain);

        info('Diagnostics output has been copied to your clipboard.');
    })->descriptions('Output diagnostics to aid in debugging Valet.', [
        '--print' => 'print diagnostics output while running',
        '--plain' => 'format clipboard output as plain text',
    ]);
}

return $app;
