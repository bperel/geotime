<?php

namespace geotime;

use Exception;
use Logger;

Logger::configure("lib/geotime/logger.xml");

class Util {
    public static $data_dir_sparql = "data/sparql/";

    public static $cache_dir_svg = "cache/svg/";
    public static $cache_dir_thumbnails = "cache/thumbnails/";
    public static $cache_dir_json = "cache/json/";
    public static $rasterize_script_path = "js/headless/rasterize.js";
    public static $invertpath_script_path = "js/headless/invertpath.js";
    public static $rasterize_script_success_output = "thumbnail created";
    public static $thumbnailSize = 400;

    public static $thumbnail_url_template = "https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/<<svg>>";
    public static $thumbnail_extension = "png";

    /** @var \Logger */
    static $log;

    static function getPhantomJsPath() {
        return implode(DIRECTORY_SEPARATOR, array('bin', 'phantomjs'));
    }

    /**
     * @param string $url
     * @param string $type
     * @param array $parameters
     * @param bool $headersOnly
     * @return bool|string
     */
    static function curl_get_contents($url, $type = "GET", $parameters = array(), $headersOnly = false) {

        $ch = curl_init();
        if ($type === "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        } else {
            $url .= '?' . http_build_query($parameters);
        }
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13');
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($headersOnly) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");

        $page = curl_exec($ch);

        curl_close($ch);

        return $page;
    }

    static function getImageExtension($imageFullName) {
        return substr($imageFullName, strrpos($imageFullName, "."));
    }

    static function cleanupImageName($imageFullName) {
        $imageExtension = self::getImageExtension($imageFullName);
        $imageName = substr($imageFullName, 0, strlen($imageFullName) - strlen($imageExtension));
        return trim($imageName).$imageExtension;
    }

    /**
     * @param $url
     * @param $svgFullName
     * @param $newFileName
     * @return boolean
     */
    static function fetchSvgWithThumbnail($url, $svgFullName, $newFileName = null)
    {
        if (!is_null($url)) {
            $svg = self::curl_get_contents($url);
            if (!empty($svg)) {
                if (!is_null($newFileName)) {
                    return self::storeSvgWithThumbnail($svg, $svgFullName);
                }
                else {
                    return true;
                }
            }
        }
        return false;
    }

    static function storeSvgWithThumbnail($svg, $fileName) {
        if (false !== @file_put_contents(self::$cache_dir_svg . $fileName, $svg)) {
            self::$log->info('Successfully stored SVG file '.$fileName);
            return self::generateThumbnailFromSvg($fileName);
        }
        else {
            self::$log->error('Failed to store SVG file '.$fileName);
            return false;
        }
    }

    static function generateThumbnailFromSvg($svgName) {
        $thumbnailOutput = self::$cache_dir_thumbnails . $svgName . '.png';
        $command = implode(' ', array(self::getPhantomJsPath(), self::$rasterize_script_path, '"' . self::$cache_dir_svg . $svgName . '"', '"' . $thumbnailOutput . '"', '2>&1'));
        $output = shell_exec($command);
        if (trim($output) === self::$rasterize_script_success_output) {
            if (self::resizeImage($thumbnailOutput, self::$thumbnailSize)) {
                self::$log->info('Successfully stored thumbnail for SVG file ' . $svgName);
                return true;
            }
            else {
                self::$log->error('Failed to resize thumbnail for SVG file '.$svgName);
                return false;
            }
        }
        else {
            self::$log->error('Failed to store thumbnail for SVG file '.$svgName);
            self::$log->debug($command);
            return false;
        }
    }

    /**
     * @param $svgFileName
     * @param $pathId
     * @param $projectionName
     * @param $projectionCenter
     * @param $projectionScale
     * @param $projectionRotation
     * @return array|null
     */
    static function calculatePathCoordinates($svgFileName, $pathId, $projectionName, $projectionCenter, $projectionScale, $projectionRotation) {
        $command = implode(' ', array(
            self::getPhantomJsPath(),
            self::$invertpath_script_path,
            self::$cache_dir_svg.$svgFileName,
            $pathId,
            $projectionName,
            implode(',',$projectionCenter),
            $projectionScale,
            implode(',',$projectionRotation),
            '2>&1'
        ));
        $result = shell_exec($command);
        return json_decode($result);
    }

    static function resizeImage($thumbnailPath, $size) {
        list($origWidth, $origHeight, $type, $attr) = getimagesize($thumbnailPath);
        if ($origWidth > $origHeight) {
            $ratio = $size / $origWidth;
            $height = $origHeight * $ratio;
            $width = $size;
        }
        else {
            $ratio = $size / $origHeight;
            $width = $origWidth * $ratio;
            $height = $size;
        }

        $oldImage = imagecreatefrompng($thumbnailPath);
        $newImage = imagecreatetruecolor($width, $height);

        // Retain transparency
        $current_transparent = imagecolortransparent($oldImage);
        if($current_transparent != -1) {
            $transparent_color = imagecolorsforindex($oldImage, $current_transparent);
            $current_transparent = imagecolorallocate($newImage, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
            imagefill($newImage, 0, 0, $current_transparent);
            imagecolortransparent($newImage, $current_transparent);
        }
        else {
            imagealphablending($newImage, false);
            $color = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
            imagefill($newImage, 0, 0, $color);
            imagesavealpha($newImage, true);
        }
        // Retain transparency - END

        imagecopyresampled($newImage, $oldImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
        return imagepng($newImage, $thumbnailPath);
    }

    /**
     * @param $page string
     * @param $fileName string
     * @return \stdClass|null
     */
    public static function getStringAsJson($page, $fileName = null) {
        $pageAsJson = json_decode($page);

        if (is_null($pageAsJson)) {
            self::$log->error('Cannot decode JSON '.substr($page, 0, 50).'...');
            return null;
        }

        if (!is_null($fileName) && false !== file_put_contents($fileName, $page)) {
            self::$log->info('Successfully stored JSON file '.$fileName);
        }
        return $pageAsJson;
    }

    /**
     * @param $fileName string
     * @param $callback
     * @return bool
     */
    public static function importFromJson($fileName, $callback)
    {
        if (file_exists($fileName)) {
            $data = json_decode(file_get_contents($fileName));
            if (is_array($data)) {
                array_map($callback, $data);

                return true;
            }
        }

        return false;
    }

    /**
     * @param $str
     * @return \DateTime|null
     */
    public static function createDateTimeFromString($str) {
        try {
            return new \DateTime($str);
        }
        catch (\Exception $e) {
            return null;
        }
    }
}

Util::$log = Logger::getLogger("main");