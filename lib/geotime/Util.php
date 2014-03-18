<?php

class Util {
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
}