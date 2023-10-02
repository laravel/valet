<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class StatamicV2ValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_dir($sitePath.'/statamic');
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        if (strpos($uri, '/site') === 0 && strpos($uri, '/site/themes') !== 0) {
            return false;
        } elseif (strpos($uri, '/local') === 0 || strpos($uri, '/statamic') === 0) {
            return false;
        } elseif ($this->isActualFile($staticFilePath = $sitePath.$uri)) {
            return $staticFilePath;
        } elseif ($this->isActualFile($staticFilePath = $sitePath.'/public'.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $this->isActualFile($staticPath = $this->getStaticPath($sitePath))) {
            return $staticPath;
        }

        if ($uri === '/installer.php') {
            return $sitePath.'/installer.php';
        }

        $scriptName = '/index.php';

        if ($this->isActualFile($sitePath.'/index.php')) {
            $indexPath = $sitePath.'/index.php';
        }

        if ($isAboveWebroot = $this->isActualFile($sitePath.'/public/index.php')) {
            $indexPath = $sitePath.'/public/index.php';
        }

        $sitePathPrefix = ($isAboveWebroot) ? $sitePath.'/public' : $sitePath;

        if ($locale = $this->getUriLocale($uri)) {
            if ($this->isActualFile($localeIndexPath = $sitePathPrefix.'/'.$locale.'/index.php')) {
                // Force trailing slashes on locale roots.
                if ($uri === '/'.$locale) {
                    header('Location: '.$uri.'/');
                    exit;
                }

                $indexPath = $localeIndexPath;
                $scriptName = '/'.$locale.'/index.php';
            }
        }

        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $_SERVER['SCRIPT_FILENAME'] = $sitePathPrefix.$scriptName;

        return $indexPath;
    }

    /**
     * Get the locale from this URI.
     */
    public function getUriLocale(string $uri): ?string
    {
        $parts = explode('/', $uri);
        $locale = $parts[1];

        if (count($parts) < 2 || ! in_array($locale, $this->getLocales())) {
            return null;
        }

        return $locale;
    }

    /**
     * Get the list of possible locales used in the first segment of a URI.
     */
    public function getLocales(): array
    {
        return [
            'af', 'ax', 'al', 'dz', 'as', 'ad', 'ao', 'ai', 'aq', 'ag', 'ar', 'am', 'aw', 'au', 'at', 'az', 'bs', 'bh',
            'bd', 'bb', 'by', 'be', 'bz', 'bj', 'bm', 'bt', 'bo', 'bq', 'ba', 'bw', 'bv', 'br', 'io', 'bn', 'bg', 'bf',
            'bi', 'cv', 'kh', 'cm', 'ca', 'ky', 'cf', 'td', 'cl', 'cn', 'cx', 'cc', 'co', 'km', 'cg', 'cd', 'ck', 'cr',
            'ci', 'hr', 'cu', 'cw', 'cy', 'cz', 'dk', 'dj', 'dm', 'do', 'ec', 'eg', 'sv', 'gq', 'er', 'ee', 'et', 'fk',
            'fo', 'fj', 'fi', 'fr', 'gf', 'pf', 'tf', 'ga', 'gm', 'ge', 'de', 'gh', 'gi', 'gr', 'gl', 'gd', 'gp', 'gu',
            'gt', 'gg', 'gn', 'gw', 'gy', 'ht', 'hm', 'va', 'hn', 'hk', 'hu', 'is', 'in', 'id', 'ir', 'iq', 'ie', 'im',
            'il', 'it', 'jm', 'jp', 'je', 'jo', 'kz', 'ke', 'ki', 'kp', 'kr', 'kw', 'kg', 'la', 'lv', 'lb', 'ls', 'lr',
            'ly', 'li', 'lt', 'lu', 'mo', 'mk', 'mg', 'mw', 'my', 'mv', 'ml', 'mt', 'mh', 'mq', 'mr', 'mu', 'yt', 'mx',
            'fm', 'md', 'mc', 'mn', 'me', 'ms', 'ma', 'mz', 'mm', 'na', 'nr', 'np', 'nl', 'nc', 'nz', 'ni', 'ne', 'ng',
            'nu', 'nf', 'mp', 'no', 'om', 'pk', 'pw', 'ps', 'pa', 'pg', 'py', 'pe', 'ph', 'pn', 'pl', 'pt', 'pr', 'qa',
            're', 'ro', 'ru', 'rw', 'bl', 'sh', 'kn', 'lc', 'mf', 'pm', 'vc', 'ws', 'sm', 'st', 'sa', 'sn', 'rs', 'sc',
            'sl', 'sg', 'sx', 'sk', 'si', 'sb', 'so', 'za', 'gs', 'ss', 'es', 'lk', 'sd', 'sr', 'sj', 'sz', 'se', 'ch',
            'sy', 'tw', 'tj', 'tz', 'th', 'tl', 'tg', 'tk', 'to', 'tt', 'tn', 'tr', 'tm', 'tc', 'tv', 'ug', 'ua', 'ae',
            'gb', 'us', 'um', 'uy', 'uz', 'vu', 've', 'vn', 'vg', 'vi', 'wf', 'eh', 'ye', 'zm', 'zw', 'en', 'zh',
        ];
    }

    /**
     * Get the path to a statically cached page.
     */
    protected function getStaticPath(string $sitePath): string
    {
        $parts = parse_url($_SERVER['REQUEST_URI']);
        $query = isset($parts['query']) ? $parts['query'] : '';

        return $sitePath.'/static'.$parts['path'].'_'.$query.'.html';
    }
}
