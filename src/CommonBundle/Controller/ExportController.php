<?php

namespace CommonBundle\Controller;

use DistributionBundle\DBAL\AssistanceTypeEnum;
use DistributionBundle\Entity\Assistance;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;

/**
 * Class ExportController
 * @package CommonBundle\Controller
 *
 * @SWG\Parameter(
 *      name="country",
 *      in="header",
 *      type="string",
 *      required=true
 * )
 */
class ExportController extends Controller
{
    /**
     * @Rest\Post("/export", name="export_data")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK"
     * )
     *
     * @SWG\Response(
     *     response=204,
     *     description="HTTP_NO_CONTENT"
     * )
     *
     * @param Request $request
     *
     * @return Response
     *
     * @deprecated export action must be refactorized. Please make own export action instead.
     */
    public function exportAction(Request $request)
    {
        try {
            set_time_limit(600);
            // Format of the file (csv, xlsx, ods, pdf)
            $type = $request->query->get('type');
            // Generate corresponding file depending on request
            if ($request->query->get('distributions')) {
                $idProject = $request->query->get('distributions');
                if ($type === 'pdf') {
                    return $this->get('distribution.distribution_service')->exportToPdf($idProject);
                }
                $filename = $this->get('distribution.distribution_service')->exportToCsv($idProject, $type);
            } elseif ($request->query->get('officialDistributions')) {
                $idProject = $request->query->get('officialDistributions');
                if ($type === 'pdf') {
                    return $this->get('distribution.distribution_service')->exportToPdf($idProject);
                }
                $filename = $this->get('distribution.distribution_service')->exportToOfficialCsv($idProject, $type);
            } elseif ($request->query->get('beneficiaries')) {
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                $ids = $request->request->get('ids');
                $filename = $this->get('beneficiary.beneficiary_service')->exportToCsv($type, $countryIso3, $filters, $ids);
            } elseif ($request->query->get('beneficiariesInDistribution')) {
                $idDistribution = $request->query->get('beneficiariesInDistribution');
                if ($type === 'pdf') {
                    return $this->get('distribution.distribution_service')->exportOneToPdf($idDistribution);
                }
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('beneficiary.beneficiary_service')->exportToCsvBeneficiariesInDistribution($distribution, $type);
            } elseif ($request->query->get('users')) {
                $filename = $this->get('user.user_service')->exportToCsv($type);
            } elseif ($request->query->get('countries')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.country_specific_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('donors')) {
                $filename = $this->get('project.donor_service')->exportToCsv($type);
            } elseif ($request->query->get('projects')) {
                $country = $request->query->get('projects');
                $filename = $this->get('project.project_service')->exportToCsv($country, $type);
            } elseif ($request->query->get('distributionSample')) {
                $arrayObjectBeneficiary = $request->request->get('sample');
                $filename = $this->get('distribution.distribution_beneficiary_service')->exportToCsv($arrayObjectBeneficiary, $type);
            } elseif ($request->query->get('householdsTemplate')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('beneficiary.household_export_csv_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('transactionDistribution')) {
                $idDistribution = $request->query->get('transactionDistribution');
                if ($type === 'pdf') {
                    return $this->get('distribution.distribution_service')->exportOneToPdf($idDistribution);
                }
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('transaction.transaction_service')->exportToCsv($distribution, $type);
            } elseif ($request->query->get('bookletCodes')) {
                $ids = $request->request->get('ids');
                $countryIso3 = $request->request->get("__country");
                $filters = $request->request->get('filters');
                if ($type === 'pdf') {
                    return $this->get('voucher.voucher_service')->exportToPdf($ids, $countryIso3, $filters);
                }
                if ($type === 'csv') {
                    return $this->get('voucher.voucher_service')->exportToCsv($type, $countryIso3, $ids, $filters);
                }
                $filename = $this->get('voucher.voucher_service')->exportToCsv($type, $countryIso3, $ids, $filters);
            } elseif ($request->query->get('reporting')) {
                $filename = $this->get('reporting.reporting_service')->exportToCsv($request->request, $type);
            } elseif ($request->query->get('generalreliefDistribution')) {
                $idDistribution = $request->query->get('generalreliefDistribution');
                if ($type === 'pdf') {
                    return $this->get('distribution.distribution_service')->exportOneToPdf($idDistribution);
                }
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('distribution.distribution_service')->exportGeneralReliefDistributionToCsv($distribution, $type);
            } elseif ($request->query->get('voucherDistribution')) {
                $idDistribution = $request->query->get('voucherDistribution');
                if ($type === 'pdf') {
                    return $this->get('distribution.distribution_service')->exportOneToPdf($idDistribution);
                }
                $distribution = $this->get('distribution.distribution_service')->findOneById($idDistribution);
                $filename = $this->get('voucher.booklet_service')->exportVouchersDistributionToCsv($distribution, $type);
            } elseif ($request->query->get('products')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('voucher.product_service')->exportToCsv($type, $countryIso3);
            } elseif ($request->query->get('vendors')) {
                $countryIso3 = $request->request->get("__country");
                $filename = $this->get('voucher.vendor_service')->exportToCsv($type, $countryIso3);
            }

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
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage(), $exception->getCode() >= 200 ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Rest\Get("/export/distribution", name="export_distribution")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Parameter(name="id",
     *     type="string",
     *     in="query",
     *     required=true,
     *     description="ID of distribution to export"
     * )
     *
     * @SWG\Parameter(name="type",
     *     type="string",
     *     in="query",
     *     required=true,
     *     description="requested file type (pdf only is support now)"
     * )
     *
     * @SWG\Parameter(name="locale",
     *     type="string",
     *     in="query",
     *     default="en"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="streamed file"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="invalid query parameters"
     * )
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws
     */
    public function exportDistributionToPdf(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw $this->createNotFoundException("Missing distribution ID.");
        }

        $distribution = $this->getDoctrine()->getRepository(Assistance::class)->find($request->query->get('id'));
        if (null == $distribution) {
            throw $this->createNotFoundException("Invalid distribution requested.");
        }

        if (!$request->query->has('type') || 'pdf' !== $request->query->get('type')) {
            throw $this->createNotFoundException("Invalid file type requested.");
        }

        $locale = $request->query->get('locale', 'en');
        $this->get('translator')->setLocale($locale);

        $direction = ('left-to-right' === \Punic\Misc::getCharacterOrder($locale)) ? 'ltr' : 'rtl';
        $template = ('left-to-right' === \Punic\Misc::getCharacterOrder($locale)) ? '@Distribution/Pdf/distributionTable.html.twig' : '@Distribution/Pdf/distributionTable.rtl.html.twig';

        $html = $this->get('templating')->render($template, [
            'direction' => $direction,
            'distribution' => $distribution,
        ]);

        return $this->container->get('pdf_service')->printPdf($html, 'portrait', 'distribution');
    }

    /**
     * @Rest\Get("/export/distribution/ukr-post")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Parameter(name="id",
     *     type="string",
     *     in="query",
     *     required=true,
     *     description="ID of distribution to export"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="streamed file"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="invalid query parameters"
     * )
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws
     */
    public function exportUkrPostDistribution(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw $this->createNotFoundException("Missing distribution ID.");
        }

        $distribution = $this->getDoctrine()->getRepository(Assistance::class)->find($request->query->get('id'));
        if (null == $distribution || AssistanceTypeEnum::DISTRIBUTION !== $distribution->getAssistanceType()) {
            throw $this->createNotFoundException("Invalid distribution requested.");
        }

        if ('UKR' !== $distribution->getProject()->getIso3()) {
            throw $this->createNotFoundException("Export allows only UKR distriburions.");
        }

        $filename = $this->get('distribution.export.ukr_post')->export($distribution);

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd().'/'.$filename));
        }

        return $response;
    }

    /**
     * @Rest\Get("/export/distribution/ukr-bank")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Parameter(name="id",
     *     type="string",
     *     in="query",
     *     required=true,
     *     description="ID of distribution to export"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="streamed file"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="invalid query parameters"
     * )
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws
     */
    public function exportUkrBankDistribution(Request $request): Response
    {
        if (!$request->query->has('id')) {
            throw $this->createNotFoundException("Missing distribution ID.");
        }

        $distribution = $this->getDoctrine()->getRepository(Assistance::class)->find($request->query->get('id'));
        if (null == $distribution || AssistanceTypeEnum::DISTRIBUTION !== $distribution->getAssistanceType()) {
            throw $this->createNotFoundException("Invalid distribution requested.");
        }

        if ('UKR' !== $distribution->getProject()->getIso3()) {
            throw $this->createNotFoundException("Export allows only UKR distriburions.");
        }

        $filename = $this->get('distribution.export.ukr_bank')->export($distribution);

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd().'/'.$filename));
        }

        return $response;
    }
}
