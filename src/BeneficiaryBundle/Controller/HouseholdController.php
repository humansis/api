<?php

namespace BeneficiaryBundle\Controller;

use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\HouseholdCSVService;
use BeneficiaryBundle\Utils\HouseholdService;
use BeneficiaryBundle\Utils\Mapper\SyriaFileToTemplateMapper;
use CommonBundle\Response\CommonBinaryFileResponse;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BeneficiaryBundle\Entity\Household;

//Annotations
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

/**
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class HouseholdController extends Controller
{
    /**
     * @Rest\Get("/households/{id}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Household $household
     * @return Response
     */
    public function showAction(Household $household)
    {
        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/households/get/all", name="all_households")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All households",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Household::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function allAction(Request $request)
    {
        $filters = $request->request->all();
        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');

        try {
            $households = $householdService->getAll($filters['__country'], $filters);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $json = $this->get('jms_serializer')
            ->serialize(
                $households,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Put("/households", name="add_household_projects")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="household",
     *     in="body",
     *     required=true,
     *     @Model(type=Household::class, groups={"FullHousehold"})
     * )
     *
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Household created",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $requestArray = $request->request->all();
        $projectsArray = $requestArray['projects'];

        $householdArray = $requestArray['household'];
        $householdArray['__country'] = $requestArray['__country'];

        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');
        try {
            $household = $householdService->createOrEdit($householdArray, $projectsArray, null);
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }


    /**
     * @Rest\Post("/households/{id}", name="edit_household", requirements={"id": "\d+"})
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="household",
     *     in="body",
     *     required=true,
     *     @Model(type=Household::class, groups={"FullHousehold"})
     * )
     *
     * @SWG\Parameter(
     *     name="projects",
     *     in="body",
     *     required=true,
     *     type="array",
     *     schema={}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Household edited",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Request $request
     * @param Household $household
     * @return Response
     */
    public function updateAction(Request $request, Household $household)
    {

        $requestArray = $request->request->all();
        $projectsArray = $requestArray['projects'];

        $householdArray = $requestArray['household'];
        $householdArray['__country'] = $requestArray['__country'];

        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');
        try {
            $household = $householdService->createOrEdit($householdArray, $projectsArray, $household);
        } catch (ValidationException $exception) {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/import/households/project/{id}", name="import_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return Household (old and new) if similarity founded",
     *      examples={
     *          "application/json": {{
     *              "old": @Model(type=Household::class),
     *              "new": @Model(type=Household::class)
     *          }}
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function importAction(Request $request, Project $project)
    {
        set_time_limit(0); // 0 = no limits
        $token = empty($request->query->get('token')) ? null : $request->query->get('token');

        $contentJson = $request->request->get('errors');

        $email = $request->query->get('email');
        $countryIso3 = $request->request->get('__country');

        /** @var HouseholdCSVService $householdService */
        $householdService = $this->get('beneficiary.household_csv_service');

        if ($token === null) {
            if (!$request->files->has('file')) {
                return new Response('You must upload a file.', Response::HTTP_BAD_REQUEST);
            }
            try {
                $return = $householdService->saveCSV($countryIso3, $project, $request->files->get('file'), $token, $email);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            try {
                if (!$contentJson) {
                    $contentJson = [];
                }
                $return = $householdService->foundErrors($countryIso3, $project, $contentJson, $token, $email);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }


        $json = $this->get('jms_serializer')
            ->serialize($return, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold']));
        return new Response($json);
    }

    /**
     * @Rest\Get("/csv/households/export", name="get_pattern_csv_household")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Return Household (old and new) if similarity founded",
     *      examples={
     *          "application/json": {
     *              {
     *                  "'Household','','','','','','','','','','','Beneficiary','','','','','',''\n'Address street','Address number','Address postcode','Livelihood','Notes','Latitude','Longitude','Adm1','Adm2','Adm3','Adm4','Family name','Gender','Status','Date of birth','Vulnerability criteria','Phones','National IDs'\n",
     *                  "pattern_household_fra.csv"
     *              }
     *          }
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getPatternCSVAction(Request $request)
    {
        $countryIso3 = $request->request->get('__country');

        $type = $request->query->get('type') ?: 'csv';
        /** @var ExportCSVService $exportCSVService */
        $exportCSVService = $this->get('beneficiary.household_export_csv_service');
        try {
            $filename = $exportCSVService->generate($countryIso3, $type);

            // Create binary file to send
            $response = new BinaryFileResponse(getcwd() . '/' . $filename);

            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
            if ($mimeTypeGuesser->isSupported()) {
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd() . '/' . $filename));
            } else {
                $response->headers->set('Content-Type', 'text/plain');
            }
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Delete("/households/{id}")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Household $household
     * @return Response
     */
    public function deleteAction(Household $household)
    {
        /** @var HouseholdService $householdService */
        $householdService = $this->get("beneficiary.household_service");
        $household = $householdService->remove($household);
        $json = $this->get('jms_serializer')
            ->serialize(
                $household,
                'json',
                SerializationContext::create()->setSerializeNull(true)->setGroups(["FullHousehold"])
            );
        return new Response($json);
    }

    /**
     * @Rest\Post("/households/delete")
     * @Security("is_granted('ROLE_DISTRIBUTIONS_DIRECTOR')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @return Response
     */
    public function removeManyAction(Request $request)
    {
        try {
            /** @var HouseholdService $householdService */
            $householdService = $this->get("beneficiary.household_service");
            $ids = $request->request->get('ids');
            $response = $householdService->removeMany($ids);
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        return new Response(json_encode($response));

    }

    /**
     * @Rest\Post("/import/api/households/project/{id}", name="get_all_beneficiaries_via_api")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @param Project $project
     * @return Response
     */
    public function importBeneficiariesFromAPIAction(Request $request, Project $project)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];
        $provider = strtolower($body['provider']);
        $params = $body['params'];

        try {
            $response = $this->get('beneficiary.api_import_service')->import($countryIso3, $provider, $params, $project);

            $json = $this->get('jms_serializer')
                ->serialize($response, 'json');

            return new Response($json);
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Post(
     *     "/import/households",
     *     name="import_household_by_model"
     * )
     *
     * @ Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function importBeneficiariesFromSyriaFileAction(
        Request $request
    ): Response
    {
        if (!$request->files->has('file')) {
            return new JsonResponse("You must upload a file.", Response::HTTP_BAD_REQUEST);
        }
        if (!$request->query->has('adm1')) {
            return new JsonResponse("A location is required.", Response::HTTP_BAD_REQUEST);
        }

        $location = [$request->query->get('adm1'), $request->query->get('adm2'), $request->query->get('adm3'), $request->query->get('adm4')];

        try {
            // get mapper and map
            $output = $this->container
                ->get('beneficiary.syria_file_to_template_mapper')
                ->map([
                    'file' => $request->files->get('file'),
                    'location' => $location,
                ]);

            // Create binary file to send
            $response = new CommonBinaryFileResponse($output['outputFile'], getcwd() . '/');
            $response->headers->set('X-times-loadingTime', $output['loadingTime']);
            $response->headers->set('X-times-executionTime', $output['executionTime']);
            $response->headers->set('X-times-writeTime', $output['writeTime']);
            return $response;
        } catch (Throwable $exception) {
            return new JsonResponse($exception->getMessage() . ' - ' . $exception->getFile() . " - " . $exception->getLine(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\Get("/import/api/households/list", name="get_all_api_available_for_country")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @SWG\Tag(name="Beneficiary")
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAPIAction(Request $request)
    {
        $body = $request->request->all();
        $countryIso3 = $body['__country'];

        $APINames = $this->get('beneficiary.api_import_service')->getAllAPI($countryIso3);

        return new Response(json_encode($APINames));
    }

    /**
     * @Rest\Post("/households/get/imported", name="get_all_households_imported")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Get all households imported by the API",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Household::class))
     *     )
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getImportedAction(Request $request)
    {
        $householdsArray = $request->request->all();
        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');
        try {
            $households = $householdService->getAllImported($householdsArray);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $json = $this->get('jms_serializer')
            ->serialize(
                $households,
                'json',
                SerializationContext::create()->setGroups("SmallHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }

}
