<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class CraftValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     */
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return file_exists($sitePath.'/craft');
    }

    /**
     * Determine the name of the directory where the front controller lives.
     */
    public function frontControllerDirectory($sitePath): string
    {
        $dirs = ['web', 'public'];

        foreach ($dirs as $dir) {
            if (is_dir($sitePath.'/'.$dir)) {
                return $dir;
            }
        }

        // Give up, and just return the default
        return is_file($sitePath.'/craft') ? 'web' : 'public';
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $siteName, string $uri)/*: string|false */
    {
        $frontControllerDirectory = $this->frontControllerDirectory($sitePath);

        if ($this->isActualFile($staticFilePath = $sitePath.'/'.$frontControllerDirectory.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $frontControllerDirectory = $this->frontControllerDirectory($sitePath);

        // Default index path
        $indexPath = $sitePath.'/'.$frontControllerDirectory.'/index.php';
        $scriptName = '/index.php';

        // Check if the first URL segment matches any of the defined locales
        $locales = [
            'ar',
            'ar_sa',
            'bg',
            'bg_bg',
            'ca_es',
            'cs',
            'cy_gb',
            'da',
            'da_dk',
            'de',
            'de_at',
            'de_ch',
            'de_de',
            'el',
            'el_gr',
            'en',
            'en_as',
            'en_au',
            'en_bb',
            'en_be',
            'en_bm',
            'en_bw',
            'en_bz',
            'en_ca',
            'en_dsrt',
            'en_dsrt_us',
            'en_gb',
            'en_gu',
            'en_gy',
            'en_hk',
            'en_ie',
            'en_in',
            'en_jm',
            'en_mh',
            'en_mp',
            'en_mt',
            'en_mu',
            'en_na',
            'en_nz',
            'en_ph',
            'en_pk',
            'en_sg',
            'en_shaw',
            'en_tt',
            'en_um',
            'en_us',
            'en_us_posix',
            'en_vi',
            'en_za',
            'en_zw',
            'en_zz',
            'es',
            'es_cl',
            'es_es',
            'es_mx',
            'es_us',
            'es_ve',
            'et',
            'fi',
            'fi_fi',
            'fil',
            'fr',
            'fr_be',
            'fr_ca',
            'fr_ch',
            'fr_fr',
            'fr_ma',
            'he',
            'hr',
            'hr_hr',
            'hu',
            'hu_hu',
            'id',
            'id_id',
            'it',
            'it_ch',
            'it_it',
            'ja',
            'ja_jp',
            'ko',
            'ko_kr',
            'lt',
            'lv',
            'ms',
            'ms_my',
            'nb',
            'nb_no',
            'nl',
            'nl_be',
            'nl_nl',
            'nn',
            'nn_no',
            'no',
            'pl',
            'pl_pl',
            'pt',
            'pt_br',
            'pt_pt',
            'ro',
            'ro_ro',
            'ru',
            'ru_ru',
            'sk',
            'sl',
            'sr',
            'sv',
            'sv_se',
            'th',
            'th_th',
            'tr',
            'tr_tr',
            'uk',
            'vi',
            'zh',
            'zh_cn',
            'zh_tw',
        ];
        $parts = explode('/', $uri);

        if (count($parts) > 1 && in_array($parts[1], $locales)) {
            $indexLocalizedPath = $sitePath.'/'.$frontControllerDirectory.'/'.$parts[1].'/index.php';

            // Check if index.php exists in the localized folder, this is optional in Craft 3
            if (file_exists($indexLocalizedPath)) {
                $indexPath = $indexLocalizedPath;
                $scriptName = '/'.$parts[1].'/index.php';
            }
        }

        $_SERVER['SCRIPT_FILENAME'] = $indexPath;
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $_SERVER['PHP_SELF'] = $scriptName;
        $_SERVER['DOCUMENT_ROOT'] = $sitePath.'/'.$frontControllerDirectory;

        return $indexPath;
    }
}
