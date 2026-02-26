<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;

if (! function_exists('formatDate')) {

    function formatDate($timestamp, $checkTimezone = true, $format = 'l, j F Y, g:i A')
    {
        // format Day Text, Day Number, Month Text, Year, Time AM/PM
        $date = Carbon::parse($timestamp);

        if ($checkTimezone) {
            $date = $date->timezone(config('app.timezone'));
        }

        return $date->translatedFormat($format);
    }
}

// untuk value input form
if (! function_exists('formatDateValue')) {

    function formatDateValue($val, $toFormat = 'd/m/Y', $fromFormat = 'Y-m-d')
    {
        if (empty($val)) {
            return '';
        }

        // change format from db Y-m-d to d/m/Y
        return Carbon::createFromFormat($fromFormat, $val)->translatedFormat($toFormat);
    }
}

if (! function_exists('reformatDate')) {

    /*
    * @param $date is required format d/m/Y
    * @return format to save db
    **/
    function reformatDate($date, $format = 'd/m/Y', bool $parse = false)
    {
        if ($parse) {
            return Carbon::parse($date);
        }

        return Carbon::createFromFormat($format, $date);
    }
}

if (! function_exists('randomString')) {

    function randomString($length, $val)
    {
        return substr(str_shuffle(str_repeat($val, $length)), 0, $length);
    }
}

if (! function_exists('collectToObject')) {

    function collectToObject(Collection $collect)
    {
        return json_decode(json_encode($collect));
    }
}

if (! function_exists('formatAngka')) {
    function formatAngka($value)
    {
        return number_format($value, 0, ',', '.');
    }
}

if (!function_exists('secureEncrypt')) {

    function secureEncrypt($text)
    {
        $key = "89665fed99e19cdedd2785d4a1f94cce"; // key harus sama dengan node js
        $iv  = "afb9a11d48e56bc9"; // key harus sama dengan node js
        $method = "AES-256-CBC";

        // Step 1: encrypt (RAW BINARY)
        $encrypted = openssl_encrypt(
            $text,
            $method,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        // Step 2: binary -> hex  (sama seperti Node)
        $hex = bin2hex($encrypted);

        // Step 3: hex -> binary
        $binaryFromHex = hex2bin($hex);

        // Step 4: binary -> base64
        $base64 = base64_encode($binaryFromHex);

        // Step 5: base64 lagi (karena Node convertAscii)
        return base64_encode($base64);
    }
}

if (!function_exists('secureDecrypt')) {

    function secureDecrypt($string)
    {
        $secret_key = "6718946466464847";
        $secret_iv  = "9292812645284535";
        $method = "AES-256-CBC";

        // Step 1: base64 decode (karena Node pakai atob)
        $decoded1 = base64_decode($string);

        // Step 2: base64 decode lagi
        $decoded2 = base64_decode($decoded1);

        // Step 3: binary -> hex
        $hex = bin2hex($decoded2);

        // Step 4: hex string -> base64
        $encryptedv3 = base64_encode($hex);

        // Step 5: generate key & iv sama seperti Node decrypt
        $key = substr(hash('sha256', $secret_key), 0, 32);
        $iv  = substr(hash('sha256', $secret_iv), 0, 16);

        $buff = base64_decode($encryptedv3);

        return openssl_decrypt(
            $buff,
            $method,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}

if (!function_exists('getMediaImageUrl')) {
    function getMediaImageUrl($path, $width = 300, $height = 300)
    {
        if (empty($path)) {
            return null;
        }

        // jika path sudah full url, langsung return
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // jika path tidak full url, tambahkan base url media storage
        // api/media?type=image&path=images/movies/sample_cover.jpg
        return rtrim(config('app.app_service_api'), '/') . '/media?type=image&path=' . urlencode($path) . '&w=' . $width . '&h=' . $height;
    }
}
