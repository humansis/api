<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

use CommonBundle\DBAL\AbstractEnum;

final class SettlementTypeEnum extends AbstractEnum
{
    public const NON_DISPLACED_OWNER_OCCUPIED = 'nonDisplaceOwnerOccupied';
    public const NON_DISPLACED_RENTAL_ACCOMMODATION = 'nonDisplaceRentalAccommodation';
    public const NON_DISPLACED_INFORMALLY_OCCUPIED = 'nonDisplaceInformallyOccupied';
    public const DISPLACED_DISPERSED_RENTAL = 'displacedDispersedRental';
    public const DISPLACED_DISPERSED_HOSTED = 'displacedDispersedHosted';
    public const DISPLACED_DISPERSED_SPONTANEOUS = 'displacedDispersedSpontaneous';
    public const DISPLACED_COMMUNAL_COLLECTIVE_CENTER = 'displacedCommunalCollectiveCenter';
    public const DISPLACED_COMMUNAL_PLANNED_CAMP = 'displacedCommunalPlannedCamp';
    public const DISPLACED_COMMUNAL_UNPLANNED_INFORMAL_SETTLEMENT = 'displacedCommunalUnplannedInformalSettlement';

    public static function all()
    {
        return [
            self::NON_DISPLACED_OWNER_OCCUPIED,
            self::NON_DISPLACED_RENTAL_ACCOMMODATION,
            self::NON_DISPLACED_INFORMALLY_OCCUPIED,
            self::DISPLACED_DISPERSED_RENTAL,
            self::DISPLACED_DISPERSED_HOSTED,
            self::DISPLACED_DISPERSED_SPONTANEOUS,
            self::DISPLACED_COMMUNAL_COLLECTIVE_CENTER,
            self::DISPLACED_COMMUNAL_PLANNED_CAMP,
            self::DISPLACED_COMMUNAL_UNPLANNED_INFORMAL_SETTLEMENT,
        ];
    }

    public function getName()
    {
        return 'enum_settlement_type';
    }
}
