<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\AssistanceFilterInputType;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceController extends AbstractController
{
    /**
     * @Rest\Get("/assistances")
     *
     * @param Request                   $request
     * @param AssistanceFilterInputType $filter
     * @param Pagination                $pagination
     * @param AssistanceOrderInputType  $orderBy
     *
     * @return JsonResponse
     */
    public function assistances(
        Request $request,
        AssistanceFilterInputType $filter,
        Pagination $pagination,
        AssistanceOrderInputType $orderBy
    ): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $assistances = $this->getDoctrine()->getRepository(Assistance::class)->findByParams(null, $countryIso3, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }

    /**
     * @Rest\Get("/projects/{id}/assistances")
     *
     * @param Project                  $project
     * @param Pagination               $pagination
     * @param AssistanceOrderInputType $orderBy
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(Project $project, Pagination $pagination, AssistanceOrderInputType $orderBy): JsonResponse
    {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        $assistances = $repository->findByParams($project, null, null, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
