<?php

namespace App\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

trait ResolvesMonth
{
    /**
     * Resolve the month being viewed from the "month" query string (YYYY-MM).
     */
    protected function resolveMonth(Request $request): CarbonImmutable
    {
        $month = $request->query('month');

        if (is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return CarbonImmutable::createFromFormat('!Y-m', $month) ?: Date::now()->startOfMonth();
        }

        return Date::now()->startOfMonth();
    }
}
