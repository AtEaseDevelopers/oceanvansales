<?php

namespace App\Support;

class NumberToWords
{
    private static $ones = [
        '', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
        'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
        'SEVENTEEN', 'EIGHTEEN', 'NINETEEN',
    ];

    private static $tens = [
        '', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY',
    ];

    /**
     * Render a Malaysian Ringgit amount as words, e.g.
     * "RINGGIT MALAYSIA ONE THOUSAND FOUR HUNDRED EIGHTY SEVEN AND CENTS FIFTY ONLY"
     */
    public static function ringgit(float $amount): string
    {
        $ringgit = (int) floor($amount);
        $cents = (int) round(($amount - $ringgit) * 100);

        $words = 'RINGGIT MALAYSIA ' . ($ringgit > 0 ? self::convert($ringgit) : 'ZERO');

        if ($cents > 0) {
            $words .= ' AND CENTS ' . self::convert($cents);
        }

        return $words . ' ONLY';
    }

    private static function convert(int $number): string
    {
        if ($number == 0) {
            return '';
        }

        if ($number < 20) {
            return self::$ones[$number];
        }

        if ($number < 100) {
            return trim(self::$tens[intdiv($number, 10)] . ' ' . self::$ones[$number % 10]);
        }

        if ($number < 1000) {
            return trim(self::$ones[intdiv($number, 100)] . ' HUNDRED ' . self::convert($number % 100));
        }

        if ($number < 1000000) {
            return trim(self::convert(intdiv($number, 1000)) . ' THOUSAND ' . self::convert($number % 1000));
        }

        return trim(self::convert(intdiv($number, 1000000)) . ' MILLION ' . self::convert($number % 1000000));
    }
}
