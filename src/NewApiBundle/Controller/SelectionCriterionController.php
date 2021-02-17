<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Enum\SelectionCriteriaTarget;
use NewApiBundle\Utils\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Cache(expires="+5 days", public=true)
 */
class SelectionCriterionController extends AbstractController
{
    /**
     * @Rest\Get("/selection-criteria/targets")
     *
     * @return JsonResponse
     */
    public function targets(): JsonResponse
    {
        $data = CodeLists::mapEnum(SelectionCriteriaTarget::values());

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/selection-criteria/targets/{targetCode}/fields")
     *
     * @param Request $request
     * @param string  $targetCode
     *
     * @return JsonResponse
     */
    public function fields(Request $request, string $targetCode): JsonResponse
    {
        if (!in_array($targetCode, SelectionCriteriaTarget::values())) {
            throw $this->createNotFoundException();
        }

        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $data = $this->get('service.selection_criterion')->findFieldsByTarget($targetCode, $countryIso3);

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/selection-criteria/targets/{targetCode}/fields/{fieldCode}/conditions")
     * @param Request $request
     * @param string  $targetCode
     * @param string  $fieldCode
     * @return JsonResponse
     */
    public function conditions(Request $request, string $targetCode, string $fieldCode): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        try {
            $data = $this->get('service.selection_criterion')->findFieldConditions($fieldCode, $targetCode, $countryIso3);
        } catch (\InvalidArgumentException|\BadMethodCallException $ex) {
            throw $this->createNotFoundException($ex->getMessage(), $ex);
        }

        $data = CodeLists::mapEnum($data);

        return $this->json(new Paginator($data));
    }
}