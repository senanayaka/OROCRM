<?php

namespace Talliance\Bundle\ApiBundle\Helper;

class DateHelper
{
    /**
     * formatDateTimeUTC
     *
     * @param $string
     * @return string
     */
    public function formatDateTimeUTC($string)
    {
        $validTimestamp = 0;
        $timezoneLength = 6;
        if (($timestamp = strtotime($string)) !== false) {
            $validTimestamp = $timestamp;
        } elseif (strlen($string) > $timezoneLength) {
            $firstString     = substr($string, 0, strlen($string) - $timezoneLength);
            $last3Characters = substr($string, strlen($string) - $timezoneLength, $timezoneLength);
            $repairedString  =  $firstString . str_replace(' ', '+', $last3Characters);

            if (($timestamp = strtotime($repairedString)) !== false) {
                $validTimestamp = $timestamp;
            }
        }

        if ($validTimestamp) {
            $utcDataTime = new \DateTime();
            $utcDataTime->setTimezone(new \DateTimeZone('UTC'));
            $utcDataTime->setTimestamp($validTimestamp);

            return $utcDataTime->format('c');
        }

        return $string;
    }

}
