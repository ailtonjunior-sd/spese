<?php

namespace App\Concerns;

trait ConvertsMoney
{
    /**
     * Convert a decimal amount (e.g. "12.50") into integer cents.
     */
    protected function toCents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
