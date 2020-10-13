<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Mapper\LocationMapper;

class BeneficiaryMapper
{
    public function toMinimalArray(?AbstractBeneficiary $beneficiary): ?array
    {
        if (!$beneficiary) {
            return null;
        }

        return [
            'id' => $beneficiary->getId(),
        ];
    }

    public function toMinimalArrays(iterable $beneficiaries): iterable
    {
        foreach ($beneficiaries as $beneficiary) {
            yield $this->toMinimalArray($beneficiary);
        }
    }
}
