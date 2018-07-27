<?php

namespace DistributionBundle\Controller;

use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Utils\DistributionBeneficiaryService;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use DistributionBundle\Entity\DistributionData;
use ProjectBundle\Entity\Project;

use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

class DistributionController extends Controller
{
    /**
     * Create a distribution
     * @Rest\Put("/distributions", name="add_distribution")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="object",
     *      required=true,
     *      description="Body of the request",
     * 	  @SWG\Schema(ref=@Model(type=DistributionData::class))
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Project created",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $distributionArray = $request->request->all();
        try
        {
            $listReceivers = $this->get('distribution.distribution_service')
                ->create($distributionArray['__country'], $distributionArray);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $listReceivers,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups([
                    "FullReceivers",
                    "FullDistribution"
                ])
            );

        return new Response($json);
    }

    /**
     * @Rest\Put("/distributions/{id}/beneficiary", name="add_beneficiary_in_distribution")
     *
     * @param Request $request
     * @param DistributionData $distributionData
     * @return Response
     * @throws \Exception
     */
    public function addBeneficiaryAction(Request $request, DistributionData $distributionData)
    {
        $data = $request->request->all();
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $distributionBeneficiary = $distributionBeneficiaryService->addBeneficiary($distributionData, $data);

        $json = $this->get('jms_serializer')
            ->serialize(
                $distributionBeneficiary,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups([
                    "FullDistributionBeneficiary",
                    "FullDistribution",
                    "FullBeneficiary"
                ])
            );

        return new Response($json);
    }

    /**
     * @Rest\Delete("/distributions/{id}/beneficiary", name="remove_beneficiary_in_distribution")
     *
     * @param DistributionBeneficiary $distributionBeneficiary
     * @return Response
     */
    public function removeBeneficiaryAction(DistributionBeneficiary $distributionBeneficiary)
    {
        /** @var DistributionBeneficiaryService $distributionBeneficiaryService */
        $distributionBeneficiaryService = $this->get('distribution.distribution_beneficiary_service');
        $return = $distributionBeneficiaryService->remove($distributionBeneficiary);

        return new Response(json_encode($return));
    }


    /**
     * @Rest\Get("/distributions", name="get_all_distributions")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All distributions",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DistributionData::class))
     *     )
     * )
     *
     * @return Response
     */
    public function getAllAction()
    {
        $distributions = $this->get('distribution.distribution_service')->findAll();
        $json = $this->get('jms_serializer')->serialize($distributions, 'json');

        return new Response($json);
    }

    /**
     * @Rest\Get("/distributions/{id}", name="get_one_distributions")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="one distribution",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=DistributionData::class))
     *     )
     * )
     *
     * @param DistributionData $DistributionData
     * @return Response
     */
    public function getOneAction(DistributionData $DistributionData)
    {
        $json = $this->get('jms_serializer')
            ->serialize(
                $DistributionData,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups(["FullDistribution"])
            );

        return new Response($json);
    }


    /**
     * Edit a distribution
     * @Rest\Post("/distributions/{id}", name="update_distribution")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *     name="DistributionData",
     *     in="body",
     *     required=true,
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="distribution updated",
     *     @Model(type=DistributionData::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param DistributionData $DistributionData
     * @return Response
     */
    public function updateAction(Request $request, DistributionData $DistributionData)
    {
        $distributionArray = $request->request->all();
        try
        {
            $DistributionData = $this->get('distribution.distribution_service')
                ->edit($DistributionData, $distributionArray);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($DistributionData, 'json', SerializationContext::create()->setSerializeNull(true));
        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Archive a distribution
     * @Rest\Post("/distributions/archive/{id}", name="archived_project")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param DistributionData $distribution
     * @return Response
     */
    public function archivedAction(DistributionData $distribution)
    {
        try
        {
            $archivedDistribution = $this->get('distribution.distribution_service')
                ->archived($distribution);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        $json = $this->get('jms_serializer')
            ->serialize($archivedDistribution, 'json', SerializationContext::create()->setSerializeNull(true));
        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Get distributions of one project
     * @Rest\Get("/distributions/projects/{id}", name="get_distributions_of_project")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     * @param Project $project
     * @return Response
     */
    public function getDistributionsAction(Project $project)
    {
        try
        {
            $distributions = $project->getDistributions();
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $distributions,
                'json',
                SerializationContext::create()->setGroups(['FullDistribution'])->setSerializeNull(true)
            );

        return new Response($json, Response::HTTP_OK);
    }

}