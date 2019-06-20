<?php
/**
 * Description of Encrypter
 *
 * @author Daniel
 */
class Encrypter {
    /**
     * @param $controlName
     * @return int
     */
    public static function checkControlOf($controlName) {
        $version = self::versionContent();

        if(!isset($version[$controlName]))
            die("$controlName doesn't exists in version control");

        if(!is_int((int) $version[$controlName]))
            die("$controlName invalid value");

        return (int) $version[$controlName];
    }

    /**
     * @return array|bool|string
     */
    public static function versionContent() {
        $RoutFile = dirname(getcwd());

        $EncryptedSetting = parse_ini_file("$RoutFile/version/config.ini", true);

        if($EncryptedSetting === FALSE)
            die("<p><b>Error</b> en el registro de configuración de CSDocs $EncryptedSetting</p>");

        return $EncryptedSetting;
    }
}
