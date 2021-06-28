<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Controller\ExportController;
use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\AssistanceRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\InputType\AssistanceCreateInputType;
use NewApiBundle\InputType\AssistanceFilterInputType;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\InputType\AssistanceStatisticsFilterInputType;
use NewApiBundle\InputType\ProjectsAssistanceFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssistanceController extends AbstractController
{
    /**
     * @Rest\Get("/web-app/v1/assistances/statistics")
     *
     * @param Request                             $request
     * @param AssistanceStatisticsFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function statistics(Request $request, AssistanceStatisticsFilterInputType $filter): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $statistics = $this->getDoctrine()->getRepository(AssistanceStatistics::class)->findByParams($countryIso3, $filter);

        return $this->json(new Paginator($statistics));
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}/statistics")
     * @ParamConverter("assistance", options={"mapping": {"id": "id"}})
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function assistanceStatistics(Assistance $assistance): JsonResponse
    {
        $statistics = $this->getDoctrine()->getRepository(AssistanceStatistics::class)->findByAssistance($assistance);

        return $this->json($statistics);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances")
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

        $assistances = $this->getDoctrine()->getRepository(Assistance::class)->findByParams($countryIso3, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }

    /**
     * @Rest\Post("/web-app/v1/assistances")
     *
     * @param AssistanceCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(AssistanceCreateInputType $inputType): JsonResponse
    {
        $assistance = $this->get('distribution.assistance_service')->create($inputType);

        return $this->json($assistance);
    }

    /**
     * @Rest\Get("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function item(Assistance $assistance): JsonResponse
    {
        if ($assistance->getArchived()) {
            $this->createNotFoundException();
        }

        return $this->json($assistance);
    }

    /**
     * @Rest\Patch("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function update(Request $request, Assistance $assistance): JsonResponse
    {
        if ($request->request->has('validated')) {
            if ($request->request->get('validated', true)) {
                $this->get('distribution.assistance_service')->validateDistribution($assistance);
            } else {
                $this->get('distribution.assistance_service')->unvalidateDistribution($assistance);
            }
        }

        if ($request->request->get('completed', false)) {
            $this->get('distribution.assistance_service')->complete($assistance);
        }

        if ($request->request->get('dateDistribution')) {
            $this->get('distribution.assistance_service')->updateDateDistribution($assistance,
                new \DateTime($request->request->get('dateDistribution')));
        }

        return $this->json($assistance);
    }

    /**
     * @Rest\Delete("/web-app/v1/assistances/{id}")
     *
     * @param Assistance $assistance
     *
     * @return JsonResponse
     */
    public function delete(Assistance $assistance): JsonResponse
    {
        $this->get('distribution.assistance_service')->delete($assistance);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/assistances/exports")
     *
     * @param Project $project
     * @param Request $request
     *
     * @return Response
     */
    public function exports(Project $project, Request $request): Response
    {
        $request->query->add(['officialDistributions' => $project->getId()]);

        return $this->forward(ExportController::class.'::exportAction', [], $request->query->all());
    }

    /**
     * @Rest\Get("/web-app/v1/projects/{id}/assistances")
     *
     * @param Project                           $project
     * @param Pagination                        $pagination
     * @param ProjectsAssistanceFilterInputType $filter
     * @param AssistanceOrderInputType          $orderBy
     *
     * @return JsonResponse
     */
    public function getProjectAssistances(
        Project $project,
        Pagination $pagination,
        ProjectsAssistanceFilterInputType $filter,
        AssistanceOrderInputType $orderBy
    ): JsonResponse
    {
        /** @var AssistanceRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Assistance::class);

        $assistances = $repository->findByProject($project, null, $filter, $orderBy, $pagination);

        return $this->json($assistances);
    }
}
