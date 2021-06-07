<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\AssistanceSelection;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\SelectionCriteria\FieldDbTransformer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class AssistanceSelectionController extends AbstractController
{
    /** @var FieldDbTransformer */
    private $fieldDbTransformer;

    public function __construct(FieldDbTransformer $fieldDbTransformer)
    {
        $this->fieldDbTransformer = $fieldDbTransformer;
    }

    /**
     * @Rest\Get("/assistance-selections/{id}")
     *
     * @return JsonResponse
     */
    public function assistanceSelection(AssistanceSelection $assistanceSelection): JsonResponse
    {
        return $this->json($assistanceSelection);
    }

    /**
     * @Rest\Get("/assistance-selections/{id}/selection-criteria")
     *
     * @return JsonResponse
     */
    public function selectionCriteria(AssistanceSelection $assistanceSelection): JsonResponse
    {
        $data = [];
        foreach ($assistanceSelection->getSelectionCriteria() as $selectionCriterion) {
            $data[] = $this->fieldDbTransformer->toResponseArray($selectionCriterion);
        }

        return $this->json(new Paginator($data));
    }
}
