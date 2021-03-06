<?php

namespace CommonBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use BeneficiaryBundle\Entity\Household;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CommonController
 * @package CommonBundle\Controller
 *
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class CommonController extends Controller
{

    /**
     * @Rest\Get("/summary", name="get_summary")
     *
     * @SWG\Tag(name="Common")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="HTTP_BAD_REQUEST"
     * )
     * @param Request $request
     * @return Response
     */
    public function getSummaryAction(Request $request)
    {
        $country = $request->request->get('__country');
        
        try {
            $total_beneficiaries = $this->get('beneficiary.beneficiary_service')->countAll($country);
            $active_projects = $this->get('project.project_service')->countActive($country);
            $enrolled_households = $this->getDoctrine()->getRepository(Household::class)
                ->countUnarchivedByCountryProjects($country);

            $total_beneficiary_served = $this->get('beneficiary.beneficiary_service')->countAllServed($country);

            $total_completed_distributions = $this->get('distribution.assistance_service')->countCompleted($country);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        return $this->json([
            $total_beneficiaries,
            $active_projects,
            $enrolled_households,
            $total_beneficiary_served,
            $total_completed_distributions
        ]);
    }

    /**
     * @Rest\Get("/logs", name="get_logs")
     *
     * @SWG\Tag(name="Common")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="HTTP_BAD_REQUEST"
     * )
     * @param Request $request
     * @return Response
     */
    public function getLogs(Request $request)
    {
        try {
            $logs = $this->get('log_service')->getLogs();
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        
        $json = $this->get('serializer')->serialize($logs, 'json', ['groups' => ['FullLogs'], 'datetime_format' => 'd-m-Y H:i']);
        
        return new Response($json);
    }

    /**
     * @Rest\Get("/offline-app/v1/master-key")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Common")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="key",
     *             type="string"
     *         ),
     *         @SWG\Property(
     *             property="version",
     *             type="string"
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="HTTP_BAD_REQUEST"
     * )
     *
     * @return JsonResponse
     */
    public function masterKeyOfflineApp()
    {
        return $this->json([
            'MASTER_KEY' => $this->getParameter('mobile_app_master_key'),
            'APP_VERSION' => $this->getParameter('mobile_app_version'),
            'APP_ID' => $this->getParameter('mobile_app_id'),
        ]);
    }

    /**
     * @Rest\Get("/vendor-app/v1/master-key")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @SWG\Tag(name="Common")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *             property="key",
     *             type="string"
     *         ),
     *         @SWG\Property(
     *             property="version",
     *             type="string"
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="HTTP_BAD_REQUEST"
     * )
     *
     * @return JsonResponse
     */
    public function masterKeyVendorApp()
    {
        return $this->masterKeyOfflineApp();
    }
}
