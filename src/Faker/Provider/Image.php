<?php

namespace Faker\Provider;

/**
 * Depends on image generation from https://picsum.photos/id/692/640/480
 */
class Image extends Base
{
    /**
     * @var string
     */
    public const BASE_URL = 'https://picsum.photos';


    public static $picsumPhotosInvalidImageIDs = [
        86=>82, 97=>39, 105=>96, 138=>103, 148=>16, 150=>23, 205=>116, 207=>173, 224=>37, 226=>52,
        245=>127, 246=>165, 262=>36, 285=>169, 286=>47, 298=>146, 303=>273, 332=>82, 333=>127, 346=>200,
        359=>42, 394=>356, 414=>269, 422=>131, 438=>254, 462=>114, 463=>419, 470=>198, 489=>104, 540=>249,
        561=>549, 578=>571, 587=>212, 589=>424, 592=>528, 595=>249, 597=>495, 601=>80, 624=>404, 632=>317,
        636=>533, 644=>556, 647=>237, 673=>447, 697=>614, 706=>271, 707=>529, 708=>53, 709=>372, 710=>323,
        711=>18, 712=>403, 713=>425, 714=>488, 720=>30, 725=>264, 734=>81, 745=>251, 746=>437, 747=>158,
        748=>113, 749=>175, 750=>111, 751=>291, 752=>388, 753=>424, 754=>28, 759=>289, 761=>247, 762=>161,
        763=>99, 771=>31, 792=>340, 801=>617, 812=>672, 843=>385, 850=>234, 854=>130, 895=>562, 897=>75,
        899=>594, 917=>445, 920=>501, 934=>485, 956=>886, 963=>371, 968=>913, 1007=>30, 1017=>683,
        1030=>649, 1034=>538, 1046=>47
    ];


    /**
     * Generate the URL that will return a random image
     *
     *
     * @example 'https://picsum.photos/640/480.jpg?random=237'
     * @example 'https://picsum.photos/id/237/640/480.jpg'
     *
     * @param int         $width
     * @param int         $height
     * @param bool        $random
     * @return string
     */
    public static function imageUrl(
        $width = 640,
        $height = 480,
        $random = false
    ) {
        $size = sprintf('%d/%d', $width, $height);

        $number = rand(1, 1084);
        if ( array_key_exists($number, static::$picsumPhotosInvalidImageIDs) ) {
            $number = static::$picsumPhotosInvalidImageIDs[$number];
        }

        If ($random === true) {
            return sprintf(
                '%s/%s.jpg?random=%s',
                self::BASE_URL,
                $size,
                rand(1, 5000)
            );
        }

        return sprintf(
            '%s/id/%s/%s.jpg',
            self::BASE_URL,
            $number,
            $size,
        );
    }

    /**
     * Download a remote random image to disk and return its location
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.png'
     *
     * @return bool|string
     */
    public static function image(
        $dir = null,
        $width = 640,
        $height = 480,
        $fullPath = true
    ) {
        $dir = null === $dir ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name . '.jpg';
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height);

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);

            if (!$success) {
                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }
}
