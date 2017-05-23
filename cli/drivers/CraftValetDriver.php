<?php

class CraftValetDriver extends ValetDriver
{
    /**
     * Determine if the driver serves the request.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return bool
     */
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath.'/craft');
    }

    /**
     * Determine the name of the directory where the front controller lives.
     *
     * @param  string  $sitePath
     * @return string
     */
    public function frontControllerDirectory($sitePath)
    {
        return is_file($sitePath.'/craft') ? 'web' : 'public';
    }

    /**
     * Determine if the incoming request is for a static file.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string|false
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        $frontControllerDirectory = $this->frontControllerDirectory($sitePath);

        if ($this->isActualFile($staticFilePath = $sitePath.'/'.$frontControllerDirectory.$uri)) {
            return $staticFilePath;
        }

        return false;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
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
            $indexPath = $sitePath.'/public/'. $parts[1] .'/index.php';
            $scriptName = '/' . $parts[1] . '/index.php';
        }

        $_SERVER['SCRIPT_FILENAME'] = $indexPath;
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        $_SERVER['SCRIPT_NAME'] = $scriptName;
        $_SERVER['PHP_SELF'] = $scriptName;

        return $indexPath;
    }
}
