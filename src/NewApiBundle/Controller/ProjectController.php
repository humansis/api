<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\ProjectCreateInputType;
use NewApiBundle\InputType\ProjectFilterInputType;
use NewApiBundle\InputType\ProjectOrderInputType;
use NewApiBundle\InputType\ProjectUpdateInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProjectController extends AbstractController
{
    /**
     * @Rest\Get("/projects/{id}/summaries")
     *
     * @param Request $request
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function summaries(Request $request, Project $project): JsonResponse
    {
        if (true === $project->getArchived()) {
            throw $this->createNotFoundException();
        }

        $repository = $this->getDoctrine()->getRepository(Beneficiary::class);

        $result = [];
        foreach ($request->query->get('code', []) as $code) {
            switch ($code) {
                case 'reached_beneficiaries':
                    $result[] = ['code' => $code, 'value' => $repository->countAllInProject($project)];
                    break;
                default:
                    throw new BadRequestHttpException('Invalid query parameter code.'.$code);
            }
        }

        return $this->json(new Paginator($result));
    }

    /**
     * @Rest\Get("/projects/{id}")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function item(Project $project): JsonResponse
    {
        if (true === $project->getArchived()) {
            throw $this->createNotFoundException();
        }

        return $this->json($project);
    }

    /**
     * @Rest\Get("/projects")
     *
     * @param Request                $request
     * @param ProjectFilterInputType $filter
     * @param ProjectOrderInputType  $orderBy
     * @param Pagination             $pagination
     *
     * @return JsonResponse
     */
    public function list(Request $request, ProjectFilterInputType $filter, ProjectOrderInputType $orderBy, Pagination $pagination): JsonResponse
    {
        $countryIso3 = $request->headers->get('country', false);
        if (!$countryIso3) {
            throw new BadRequestHttpException('Missing country header');
        }

        $projects = $this->getDoctrine()->getRepository(Project::class)->findByParams($countryIso3, $filter, $orderBy, $pagination);

        return $this->json($projects);
    }

    /**
     * @Rest\Post("/projects")
     *
     * @param ProjectCreateInputType $inputType
     *
     * @return JsonResponse
     */
    public function create(ProjectCreateInputType $inputType): JsonResponse
    {
        $object = $this->get('project.project_service')->create($inputType, $this->getUser());

        return $this->json($object);
    }

    /**
     * @Rest\Put("/projects/{id}")
     *
     * @param Project                $project
     * @param ProjectUpdateInputType $inputType
     *
     * @return JsonResponse
     */
    public function update(Project $project, ProjectUpdateInputType $inputType): JsonResponse
    {
        if ($project->getArchived()) {
            throw new BadRequestHttpException('Unable to update archived project.');
        }

        $object = $this->get('project.project_service')->update($project, $inputType);

        return $this->json($object);
    }

    /**
     * @Rest\Delete("/projects/{id}")
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function delete(Project $project): JsonResponse
    {
        $this->get('project.project_service')->delete($project);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
