<?php

namespace App\Enums;

enum NewsSource: string
{
    case NewsApi = 'newsapi';
    case Guardian = 'guardian';
    case NyTimes = 'nytimes';

    public function label(): string
    {
        return match ($this) {
            self::NewsApi => 'NewsAPI.org',
            self::Guardian => 'The Guardian',
            self::NyTimes => 'New York Times',
        };
    }
}
