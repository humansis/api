<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use TransactionBundle\Entity\PurchasedItem;
use TransactionBundle\Repository\PurchasedItemRepository;

class PurchasedItemController extends AbstractController
{
    /**
     * @Rest\Get("/beneficiaries/{id}/purchased-items")
     * @ParamConverter("beneficiary")
     *
     * @param Beneficiary $beneficiary
     *
     * @return JsonResponse
     */
    public function listByBeneficiary(Beneficiary $beneficiary): JsonResponse
    {
        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->getPurchases($beneficiary);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/households/{id}/purchased-items")
     * @ParamConverter("household")
     *
     * @param Household $household
     *
     * @return JsonResponse
     */
    public function listByHousehold(Household $household): JsonResponse
    {
        /** @var PurchasedItemRepository $repository */
        $repository = $this->getDoctrine()->getRepository(PurchasedItem::class);

        $data = $repository->getHouseholdPurchases($household);

        return $this->json(new Paginator($data));
    }
}