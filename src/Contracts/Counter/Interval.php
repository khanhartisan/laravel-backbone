<?php

namespace KhanhArtisan\LaravelBackbone\Contracts\Counter;

enum Interval: string
{
    case ONE_MINUTE = '1m';
    case FIVE_MINUTES = '5m';
    case TEN_MINUTES = '10m';
    case FIFTEEN_MINUTES = '15m';
    case THIRTY_MINUTES = '30m';
    case HOURLY = '1h';
    case DAILY = 'day';
    case WEEKLY = 'week';
    case MONTHLY = 'month';
    case YEARLY = 'year';
}