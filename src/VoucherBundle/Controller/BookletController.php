<?php

namespace VoucherBundle\Controller;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VoucherBundle\Entity\Booklet;

/**
 * Class BookletController
 * @package VoucherBundle\Controller
 */
class BookletController extends Controller
{
    /**
     * Create a new Booklet.
     *
     * @Rest\Put("/new_booklet", name="add_booklet")
     *
     * @SWG\Tag(name="Booklets")
     *
     * @SWG\Parameter(
     *     name="booklet",
     *     in="body",
     *     required=true,
     *     @Model(type=Booklet::class, groups={"FullBooklet"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Booklet created",
     *     @Model(type=Booklet::class)
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
    public function createBooklet(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $booklet = $request->request->all();
        $bookletData = $booklet;
        $booklet = $serializer->deserialize(json_encode($request->request->all()), Booklet::class, 'json');

        try {
            $bookletBatch = $this->get('booklet.booklet_service')->getBookletBatch();
            $currentBatch = $bookletBatch;
            $counter = 1;
            for ($x = 0; $x < $bookletData['numberBooklets']; $x++) {
                $return = $this->get('booklet.booklet_service')->create($booklet, $bookletData, $currentBatch, $bookletBatch);
                $counter++;
                $currentBatch++;
            };
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), 500);
        }

        // $vendorJson = $serializer->serialize(
        //     $return,
        //     'json',
        //     SerializationContext::create()->setGroups(['FullVendor'])->setSerializeNull(true)
        // );
        // return new Response($booklet);
    }

    
}
