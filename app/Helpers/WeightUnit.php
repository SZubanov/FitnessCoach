<?php

namespace App\Helpers;

use Exception;

class WeightUnit {
    const KG = 'kg';
    const G = 'g';
    const LB = 'lb';
    const OZ = 'oz';

    private static $conversionRates = [
        self::KG => [
            self::KG => 1,
            self::G => 1000,
            self::LB => 2.20462,
            self::OZ => 35.274,
        ],
        self::G => [
            self::KG => 0.001,
            self::G => 1,
            self::LB => 0.00220462,
            self::OZ => 0.035274,
        ],
        self::LB => [
            self::KG => 0.453592,
            self::G => 453.592,
            self::LB => 1,
            self::OZ => 16,
        ],
        self::OZ => [
            self::KG => 0.0283495,
            self::G => 28.3495,
            self::LB => 0.0625,
            self::OZ => 1,
        ],
    ];

    public static function getConversionRate($fromUnit, $toUnit) {
        if (!isset(self::$conversionRates[$fromUnit]) || !isset(self::$conversionRates[$fromUnit][$toUnit])) {
            throw new Exception("Invalid units or conversion not defined.");
        }
        return self::$conversionRates[$fromUnit][$toUnit];
    }
}

