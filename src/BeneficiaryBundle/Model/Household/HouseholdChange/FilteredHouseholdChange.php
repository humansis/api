<?php

namespace BeneficiaryBundle\Model\Household\HouseholdChange;

use BeneficiaryBundle\Entity\HouseholdActivity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Class FilteredHouseholdChange allowes only defined fields to be shown as changed.
 */
class FilteredHouseholdChange extends AbstractHouseholdChange
{
    const ALLOWED_FIELDS = [
        'income_level',
        'debt_level',
        'food_consumption_score',
        'coping_strategies_index',
        'support_date_received',
    ];

    public function __construct(HouseholdActivity $activity, HouseholdActivity $previousActivity)
    {
        parent::__construct($activity, $previousActivity);
    }

    /**
     * @SymfonyGroups({"HouseholdChanges"})
     */
    public function getChanges(): array
    {
        $diff = parent::getChanges();

        // only allowed fields can be shown
        foreach ($diff as $field => $value) {
            if (!in_array($field, self::ALLOWED_FIELDS)) {
                unset($diff[$field]);
            }
        }

        return $diff;
    }
}
