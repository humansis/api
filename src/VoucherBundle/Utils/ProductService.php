<?php

namespace VoucherBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Logs;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\InputType\ProductCreateInputType;
use NewApiBundle\InputType\ProductUpdateInputType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Voucher;
use Psr\Container\ContainerInterface;

class ProductService
{

  /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * UserService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
    }


    /**
     * Creates a new Product entity
     *
     * @deprecated Use ProductService::create instead
     * @param array $productData
     * @return mixed
     * @throws \Exception
     */
    public function createFromArray($productData)
    {
        try {
            $product = new Product();

            $product->setImage($productData['image'])
              ->setName($productData['name'])
              ->setUnit($productData['unit'] ?? null)
              ->setArchived(false)
              ->setCountryISO3($productData['__country']);

            $this->em->persist($product);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception('Error while creating a product' . $e->getMessage());
        }

        return $product;
    }

    /**
     * Creates a new Product entity.
     *
     * @param ProductCreateInputType $productData
     *
     * @return Product
     */
    public function create(ProductCreateInputType $productData)
    {
        $product = (new Product())
            ->setName($productData->getName())
            ->setImage($productData->getImage())
            ->setUnit($productData->getUnit())
            ->setCountryISO3($productData->getIso3())
            ->setArchived(false);

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Returns all the products
     *
     * @return array
     */
    public function findAll($countryIso3)
    {
        return $this->em->getRepository(Product::class)->findBy(['archived' => false, 'countryISO3' => $countryIso3]);
    }

    /**
     * Updates a product according to the $productData
     *
     * @deprecated Use ProductService::update instead
     * @param Product $product
     * @param array $productData
     * @return Product
     */
    public function updateFromArray(Product $product, array $productData)
    {
        $product->setUnit($productData['unit'])
            ->setImage($productData['image']);

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Updates a product according to the $productData.
     *
     * @param Product                $product
     * @param ProductUpdateInputType $productData
     *
     * @return Product
     */
    public function update(Product $product, ProductUpdateInputType $productData)
    {
        $product
            ->setUnit($productData->getUnit())
            ->setImage($productData->getImage());

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * Archives a product
     *
     * @param Product $product
     * @return string
     */
    public function archive(Product $product)
    {
        $product->setArchived(true);

        $this->em->persist($product);
        $this->em->flush();

        return "Product suppressed";
    }

    /**
     * Export all products in a CSV file
     * @param string $type
     * @param string $countryIso3
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryIso3)
    {
        $exportableTable = $this->em->getRepository(Product::class)->findBy(['archived' => false, 'countryISO3' => $countryIso3]);

        return $this->container->get('export_csv_service')->export($exportableTable, 'products', $type);
    }
}
