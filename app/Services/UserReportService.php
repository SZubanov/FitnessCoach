<?php

namespace App\Services;

class UserReportService
{
    public function __construct()
    {

    }

    /**
     * @return int
     */
    private function getNumberDateFromStart(): int
    {
        return (int)floor(time() / 86400) - 1; // 19416
    }
}
