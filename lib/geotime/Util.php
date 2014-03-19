<?php

namespace geotime;

class Util {
    public static $cache_dir_svg = "cache/svg/";
    public static $cache_dir_json = "cache/json/";

    static function curl_get_contents($url, $type, $parameters = array()) {

        $ch = curl_init();
        if ($type === "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }
        else {
            $url .= '?' . http_build_query($parameters);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");


        $page = curl_exec($ch);

        curl_close($ch);

        return $page;
    }

    static function getImageExtension($imageMapFullName) {
        return substr($imageMapFullName, strrpos($imageMapFullName, "."));
    }

    static function cleanupImageName($imageMapFullName) {
        $imageMapExtension = self::getImageExtension($imageMapFullName);
        $imageMapName = substr($imageMapFullName, 0, strlen($imageMapFullName) - strlen($imageMapExtension));
        return trim($imageMapName).$imageMapExtension;
    }

    /**
     * @param $imageMapUrl
     * @param $fileName
     * @return mixed|null
     */
    static function fetchImage($imageMapUrl, $fileName = null)
    {
        if (!is_null($imageMapUrl)) {
            $svg = self::curl_get_contents($imageMapUrl, "GET", array());
            if (!empty($svg)) {
//                echo 'Fetched ' . $imageMapUrl . '<br />';
                if (!is_null($fileName)) {
                    file_put_contents(self::$cache_dir_svg . $fileName, $svg);
                }
                return $svg;
            }
        }
        return null;
    }
}