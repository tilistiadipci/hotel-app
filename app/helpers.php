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
    function formatAngka($value) {
        return number_format($value, 0, ',', '.');
    }
}
