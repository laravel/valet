<?php

namespace Valet;

class ServerBlock
{
    /**
     * Prepares the ServerBlocks folder in VALET_HOME_PATH/ServerBlocks
     */
    public static function install()
    {
        if (! is_dir($serverBlocksDirectory = VALET_HOME_PATH.'/ServerBlocks')) {
            mkdir($serverBlocksDirectory, 0755);

            chown($serverBlocksDirectory, $_SERVER['SUDO_USER']);
        }

        touch($serverBlocksDirectory.'/.keep');

        chown($serverBlocksDirectory.'/.keep', $_SERVER['SUDO_USER']);
    }

    /**
     * Adds a custom server block to VALET_HOME/Caddy based on a stub file
     * @param $domain
     * @param $file
     * @param $data
     */
    public static function add($domain, $file, $data)
    {
        $serverBlockContent = file_get_contents($file);

        $dataArray = ['keys' => [], 'values' => []];
        foreach($data as $datapoint) {
            $keyValue = explode('=', $datapoint);
            $dataArray['keys'] = $keyValue[0];
            $dataArray['values'] = $keyValue[1];
        }

        $serverBlockContent = str_replace($dataArray['keys'], $dataArray['values'], $serverBlockContent);

        $serverBlockContent = str_replace([
            'DOMAIN', 'CWD', 'PUBLIC_PATH', 'HOME_PATH'
        ], [
            $domain, getcwd(), 'public', $_SERVER['HOME']
        ], $serverBlockContent);

        file_put_contents(VALET_HOME_PATH.'/Caddy/'.$domain.'.conf', $serverBlockContent);

        PhpFpm::restart();
        Caddy::restart();
    }

    /**
     * Removes a custom server block from VALET_HOME/Caddy
     * @param $domain
     */
    public static function remove($domain)
    {
        unlink(VALET_HOME_PATH.'/Caddy/'.$domain.'.conf');

        PhpFpm::restart();
        Caddy::restart();
    }


    /**
     * Rename all server blocks to match the new domain
     * @param $oldDomain
     * @param $domain
     */
    public static function renameBlocks($oldDomain, $domain)
    {
        if ($handle = opendir(VALET_HOME_PATH.'/Caddy')) {
            while (false !== ($filename = readdir($handle))) {
                if($filename === '.' || $filename === '..' || $filename === '.keep') {
                    continue;
                }

                $content = file_get_contents(VALET_HOME_PATH.'/Caddy/'.$filename);
                file_put_contents(VALET_HOME_PATH.'/Caddy/'.$filename,
                    str_replace($oldDomain.':80', $domain.':80', $content));
                rename(VALET_HOME_PATH.'/Caddy/'.$filename,
                    str_replace('.'.$oldDomain.'.conf', '.'.$domain.'.conf', VALET_HOME_PATH.'/Caddy/'.$filename));
            }
            closedir($handle);
        }
    }

    /**
     * Trim the string and append current tld
     * @param $domain
     * @return string
     */
    public static function generateDomain($domain)
    {
        return trim($domain).'.'.Configuration::read()['domain'];
    }
}
