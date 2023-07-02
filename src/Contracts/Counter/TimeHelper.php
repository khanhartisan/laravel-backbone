<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

use Illuminate\Support\Carbon;

class TimeHelper
{
    /**
     * Interval to seconds
     *
     * @param Interval $interval
     * @return int
     */
    public static function intervalToSeconds(Interval $interval): int
    {
        return match ($interval) {
            Interval::ONE_MINUTE => 60,
            Interval::FIVE_MINUTES => 300,
            Interval::TEN_MINUTES => 600,
            Interval::FIFTEEN_MINUTES => 900,
            Interval::THIRTY_MINUTES => 1800,
            Interval::HOURLY => 3600,
            Interval::DAILY => 86400,
            Interval::WEEKLY => 86400 * 7,
            Interval::MONTHLY => 86400 * 30,
            Interval::YEARLY => 86400 * 365
        };
    }

    /**
     * Get start time for the given interval and time
     *
     * @param Interval $interval
     * @param int|null $time
     * @return int
     */
    public static function startTime(Interval $interval, ?int $time = null): int
    {
        if (!$time) {
            $time = time();
        }

        $time = Carbon::parse($time);

        if ($interval === Interval::DAILY) {
            return strtotime($time->startOfDay());
        }

        if ($interval === Interval::WEEKLY) {
            return strtotime($time->startOfWeek());
        }

        if ($interval === Interval::MONTHLY) {
            return strtotime($time->startOfMonth());
        }

        if ($interval === Interval::YEARLY) {
            return strtotime($time->startOfYear());
        }

        $intervalValue = static::intervalToSeconds($interval);

        $startTime = strtotime((clone $time)->subSeconds($intervalValue)->startOfHour());
        $time = strtotime($time);
        while (true) {
            if ($startTime + $intervalValue > $time) {
                return $startTime;
            }
            $startTime += $intervalValue;
        }
    }
}