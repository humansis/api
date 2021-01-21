<?php

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\HouseholdLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\JsonResponse;

class BeneficiaryController extends AbstractController
{
    /**
     * @Rest\Get("/beneficiaries/{id}")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function beneficiary(Beneficiary $beneficiary): JsonResponse
    {
        if ($beneficiary->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($beneficiary);
    }

    /**
     * @Rest\Get("/beneficiaries/national-ids/{id}")
     *
     * @param NationalId $nationalId
     *
     * @return JsonResponse
     */
    public function nationalId(NationalId $nationalId): JsonResponse
    {
        return $this->json($nationalId);
    }

    /**
     * @Rest\Get("/beneficiaries/phones/{id}")
     *
     * @param Phone $phone
     *
     * @return JsonResponse
     */
    public function phone(Phone $phone): JsonResponse
    {
        return $this->json($phone);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/camps/{id}")
     *
     * @param HouseholdLocation $campAddress
     *
     * @return JsonResponse
     */
    public function camp(HouseholdLocation $campAddress): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_CAMP !== $campAddress->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($campAddress);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/residencies/{id}")
     *
     * @param HouseholdLocation $residence
     *
     * @return JsonResponse
     */
    public function residence(HouseholdLocation $residence): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_RESIDENCE !== $residence->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($residence);
    }

    /**
     * @Rest\Get("/beneficiaries/addresses/temporary-settlements/{id}")
     *
     * @param HouseholdLocation $temporarySettlement
     *
     * @return JsonResponse
     */
    public function temporarySettlement(HouseholdLocation $temporarySettlement): JsonResponse
    {
        if (HouseholdLocation::LOCATION_TYPE_SETTLEMENT !== $temporarySettlement->getType()) {
            throw $this->createNotFoundException();
        }

        return $this->json($temporarySettlement);
    }
}