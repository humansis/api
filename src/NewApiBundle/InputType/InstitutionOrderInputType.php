<?php

namespace NewApiBundle\InputType;

use NewApiBundle\Request\OrderInputType\AbstractSortInputType;

class InstitutionOrderInputType extends AbstractSortInputType
{
    const SORT_BY_ID = 'id';
    const SORT_BY_NAME = 'name';
    const SORT_BY_LONGITUDE = 'longitude';
    const SORT_BY_LATITUDE = 'latitude';
    const SORT_BY_CONTACT_GIVEN_NAME = 'contactGivenName';
    const SORT_BY_CONTACT_FAMILY_NAME = 'contactFamilyName';
    const SORT_BY_TYPE = 'type';

    protected function getValidNames(): array
    {
        return [
            self::SORT_BY_ID,
            self::SORT_BY_NAME,
            self::SORT_BY_LONGITUDE,
            self::SORT_BY_LATITUDE,
            self::SORT_BY_CONTACT_GIVEN_NAME,
            self::SORT_BY_CONTACT_FAMILY_NAME,
            self::SORT_BY_TYPE,
        ];
    }
}
