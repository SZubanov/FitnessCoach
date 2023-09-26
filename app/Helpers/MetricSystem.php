<?php
declare(strict_types=1);

namespace App\Helpers;

class MetricSystem
{
    public const SI = 1;
    public const UMS = 2;

    public static function getWeightUnitsByMetricSystem(int $metricSystem): array
    {
        return match ($metricSystem) {
            self::UMS => self::getUMSWeightUnits(),
            default => self::getSIWeightUnits(),
        };
    }

    public static function getSIWeightUnits(): array
    {
        return [
            WeightUnit::KG => WeightUnit::KG,
            WeightUnit::G => WeightUnit::G
        ];
    }

    public static function getUMSWeightUnits(): array
    {
        return [
            WeightUnit::LB => WeightUnit::LB,
            WeightUnit::OZ => WeightUnit::OZ
        ];
    }

    public static function getDefaultWeightUnitByMetricSystem(int $metricSystem): string
    {
        return match ($metricSystem) {
            self::UMS => WeightUnit::OZ,
            default => WeightUnit::G,
        };
    }
}
