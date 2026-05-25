<?php

namespace App\Services;

class StructuredReportDetector
{
    public const REPORT_KEYS = [
        'cover_letter',
        'executive_position',
        'intelligence_dashboard',
        'intelligence_profile',
        'risk_register',
        'priority_plan',
        'final_position',
    ];

    public function hasReportContent(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach (self::REPORT_KEYS as $key) {
            if (array_key_exists($key, $value)) {
                return true;
            }
        }

        return false;
    }
}
