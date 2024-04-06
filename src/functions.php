<?php
namespace Bermuda\Clock;

function now(string|\DateTimeZone $timeZone = null, bool $immutable = true): CarbonInterface
{
    return Clock::now($timeZone, $immutable);
}

function timestamp(string|\DateTimeInterface $time = 'now'): int 
{
    return Clock::timestamp($time);
}
