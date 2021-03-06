<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class PhoneTypes
{
    const LANDLINE = 'Landline';
    const MOBILE = 'Mobile';

    public static function values(): array
    {
        return [
            self::LANDLINE,
            self::MOBILE,
        ];
    }
}
