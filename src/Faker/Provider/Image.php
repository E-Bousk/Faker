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
            rand(1, 1084),
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
