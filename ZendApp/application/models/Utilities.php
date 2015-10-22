<?php

class Utilities {

    /**
     * Constructs user class from user identity object from Zend Login
     *
     * @author Craig Knott
     *
     * @param string $date A date as a string in some format
     * @param string $from A string representing the date format
     * @param string $to   A string representing the date format you wish to change the date to
     *
     * @return string      The new date
     */
    public static function convertDate($date, $from, $to) {
        if ($from === $to) {
            return $date;
        }

        $dateParts = array(
            'day'   => 0,
            'month' => 0,
            'year'  => 0
        );

        $parts = array();
        if ($from === "en_gb") {
            $parts = explode("/", $date);
            $dateParts['day'] = $parts[0];
            $dateParts['year'] = $parts[2];
        } else if ($from === "en_us") {
            $parts = explode("-", $date);
            $dateParts['day'] = $parts[2];
            $dateParts['year'] = $parts[0];
        }
        $dateParts['month'] = $parts[1];

        if ($to === "en_gb") {
            return $dateParts['day'] . "/" . $dateParts['month'] . "/" . $dateParts['year'];
        } else if ($to === "en_us") {
            return $dateParts['year'] . "-" . $dateParts['month'] . "-" . $dateParts['day'];
        }

        return $date;
    }

    /**
     * Converts a number of hours into hours and minutes
     *
     * @author Craig Knott
     *
     * @param float $hours the number
     *
     * @return object Contains details of number of hours, minutes, and total minutes
     */
    public static function convertToHoursAndMinutes($hours) {
        $emptyResult = (object)array(
            'hours'        => 0,
            'minutes'      => 0,
            'totalMinutes' => 0
        );
        if ($hours == 0) {
            return $emptyResult;
        }

        $time = $hours * 60;
        settype($time, 'integer');
        if ($time < 1) {
            return $emptyResult;
        }
        $hours = floor($time / 60);
        $minutes = ($time % 60);

        return (object)array(
            'hours'        => $hours,
            'minutes'      => $minutes,
            'totalMinutes' => ($hours * 60) + $minutes
        );
    }

    /**
     * Converts a number of seconds into a hh/mm/ss object
     *
     * @author Craig Knott
     *
     * @param $seconds Number of seconds to convert
     *
     * @return object Contains details of number of hours, minutes, and seconds
     */
    public static function convertToTimeFromSeconds($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        return (object)array(
            'hours'   => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds
        );
    }
}