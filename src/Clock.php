<?php

namespace Bermuda\Clock;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class Clock
{
    private static string $creator = Carbon::class;
    private static string $immutableCreator = CarbonImmutable::class;
    private static string $locale = 'ru';
    private static ?string $timeZone = null;

    /**
     * @param string|null $creator
     * @param string|null $immutableCreator
     */
    public static function setCreators(string $creator = null, string $immutableCreator = null): void
    {
        if ($creator !== null) {
            if (!is_subclass_of($creator, CarbonInterface::class)) {
                throw new \InvalidArgumentException('Creator must be subclass of ' . CarbonInterface::class);
            }

            self::$creator = $creator;
        }

        if ($immutableCreator !== null) {
            if (!is_subclass_of($immutableCreator, CarbonInterface::class) || !is_subclass_of($immutableCreator, \DateTimeImmutable::class)) {
                throw new \InvalidArgumentException('Creator must be subclass of ' . CarbonInterface::class . ' and '. \DateTimeImmutable::class);
            }

            self::$immutableCreator = $immutableCreator;
        }
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public static function locale(string $locale = null): string
    {
        if ($locale !== null) {
            $old = self::$locale;
            self::$locale = $locale;
            return $old;
        }

        return self::$locale;
    }

    /**
     * @param string|int|float $var
     * @return bool
     */
    public static function isTimestamp(string|int|float $var): bool
    {
        return self::isDate('@' . $var);
    }

    /**
     * @param string|\DateTimeInterface $time
     * @return int
     */
    public static function timestamp(string|\DateTimeInterface $time = 'now'): int
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->getTimestamp();
        }

        $timestamp = @strtotime($time);

        if (($err = error_get_last()) !== null) {
            throw new \RuntimeException($err['message']);
        }

        return $timestamp;
    }

    /**
     * @param $var
     * @return bool
     */
    public static function isDate($var): bool
    {
        try {
            new \DateTime($var);
        } catch(\Throwable) {
            return false;
        }

        return true;
    }

    /**
     * @param string|int|float|array|\DateTimeInterface $time
     * @param \DateTimeZone|null $timeZone
     * @param bool $immutable
     * @param string|null $format
     * @return CarbonInterface
     */
    public static function create(string|int|float|array|\DateTimeInterface $time = 'now', \DateTimeZone $timeZone = null, bool $immutable = true, string $format = null): CarbonInterface
    {
        /**
         * @var CarbonInterface $creator
         */
        $creator = $immutable ? self::$immutableCreator : self::$creator;

        if ($timeZone === null) {
            $timeZone = new \DateTimeZone(self::$timeZone);
        }

        if ($time instanceof \DateTimeInterface) {
            if ($time instanceof \DateTimeImmutable) {
                return $creator::createFromImmutable($time)->locale(self::$locale);
            }

            return $creator::createFromInterface($time)->locale(self::$locale);
        }

        if (self::isTimestamp($time)) {
            return $creator::createFromTimestamp($time, $timeZone)->locale(self::$locale);
        }

        if (is_string($time)) {
            if ($format !== null) {
                return $creator::createFromFormat($format, $time, $timeZone)->locale(self::$locale);
            } elseif (strtolower($time) == 'now') {
                return $creator::now($timeZone)->locale(self::$locale);
            }

            return $creator::createFromTimeString($time, $timeZone)->locale(self::$locale);
        }

        if (is_array($time)) {
            if (($count = count($time)) > 3) {
                return $creator::create(...array_merge($time, $timeZone))->locale(self::$locale);
            } elseif (0 >= $count || $count > 6) {
                throw new \InvalidArgumentException('Invalid time format. Argument [time] must be an array of integers up to 6 elements long.');
            } else {
                return $creator::createFromTime(...array_merge($time, $timeZone))->locale(self::$locale);
            }
        }

        return (new $creator($time, $timeZone))->locale(self::$locale);
    }

    /**
     * @param string|\DateTimeZone|null $timeZone
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function now(string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::now($timeZone ?? new \DateTimeZone(self::$timeZone))->locale(self::$locale);
    }

    /**
     * @param bool $immutable
     * @return CarbonInterface
     */
    private static function creator(bool $immutable): string
    {
        return $immutable ? self::$immutableCreator : self::$creator;
    }

    /**
     * @param \DateTimeInterface $time
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromObject(\DateTimeInterface $time, bool $immutable = true): CarbonInterface
    {
        $creator = self::creator($immutable);
        return $time instanceof \DateTimeImmutable
            ? $creator::createFromImmutable($time)->locale(self::$locale)
            : $creator::createFromInterface($time)->locale(self::$locale);
    }
    
    /**
     * @param string $format
     * @param string $time
     * @param string|\DateTimeZone|null $timeZone
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromFormat(string $format, string $time, string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromFormat($format, $time, $timeZone ?? new \DateTimeZone(self::$timeZone))->locale(self::$locale);
    }

    /**
     * @param string|int|float $timestamp
     * @param string|\DateTimeZone|null $timeZone
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromTimestamp(string|int|float $timestamp, string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromTimestamp($timestamp, $timeZone ?? new \DateTimeZone(self::$timeZone))->locale(self::$locale);
    }

    /**
     * @param string|int|float $timestamp
     * @param string|\DateTimeZone|null $timeZone
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromTimestampMs(string|int|float $timestamp, string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromTimestampMs($timestamp, $timeZone ?? new \DateTimeZone(self::$timeZone))->locale(self::$locale);
    }

    /**
     * @param string|int|float $timestamp
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromTimestampMsUTC(string|int|float $timestamp, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromTimestampMsUTC($timestamp)->locale(self::$locale);
    }

    /**
     * @param string|int|float $timestamp
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromTimestampUTC(string|int|float $timestamp, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromTimestampUTC($timestamp)->locale(self::$locale);
    }

    /**
     * @param int|null $year
     * @param int|null $month
     * @param int|null $day
     * @param int|null $hour
     * @param int|null $minute
     * @param int|null $second
     * @param string|\DateTimeZone|null $timeZone
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromDateTime(?int $year = 0, ?int $month = 1, ?int $day = 1, ?int $hour = 0, ?int $minute = 0, ?int $second = 0, string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::create($year, $month, $day, $hour, $minute, $second, $timeZone ?? new \DateTimeZone(self::$timeZone))->locale(self::$locale);
    }

    /**
     * @param string $format
     * @param string $time
     * @param string|null $locale
     * @param TranslatorInterface|null $translator
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromIsoFormat(string $format, string $time, ?string $locale = 'en', TranslatorInterface $translator = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromIsoFormat($format, $time, $tz, $locale, $translator)->locale(self::$locale);
    }

    /**
     * @param int|null $hour
     * @param int|null $minute
     * @param int|null $second
     * @param string|\DateTimeZone|null $timeZone
     * @param bool $immutable
     * @return CarbonInterface
     */
    public static function fromTime(?int $hour = 0, ?int $minute = 0, ?int $second = 0, string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
    {
        return self::creator($immutable)::createFromTime($hour, $minute, $second, $timeZone ?? new \DateTimeZone(self::$timeZone))->locale(self::$locale);
    }

    /**
     * @param string|\DateTimeZone|null $timeZone
     * @return string
     */
    public static function timeZone(string|\DateTimeZone $timeZone = null): string
    {
        if ($timeZone !== null) {
            $currentTimeZone = date_default_timezone_get();
            self::$timeZone = $timeZone instanceof \DateTimeZone
                ? $timeZone->getName() : $timeZone;
            
            return $currentTimeZone;
        }

        return self::$timeZone ?? date_default_timezone_get();
    }
}
