<?php

declare(strict_types=1);

namespace TransactionBundle\Export;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use DistributionBundle\Entity\Assistance;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Component\Country\Country;
use NewApiBundle\Repository\PurchasedItemRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Translation\TranslatorInterface;

class PurchasedSummarySpreadsheetExport
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var Countries */
    private $countries;

    /** @var PurchasedItemRepository */
    private $repository;

    public function __construct(TranslatorInterface $translator, Countries $countries, PurchasedItemRepository $repository)
    {
        $this->translator = $translator;
        $this->countries = $countries;
        $this->repository = $repository;
    }

    public function export(string $countryIso3, string $filetype)
    {
        $country = $this->countries->getCountry($countryIso3);
        if (!$country) {
            throw new \InvalidArgumentException('Invalid country '.$countryIso3);
        }

        if (!in_array($filetype, ['ods', 'xlsx', 'csv'], true)) {
            throw new \InvalidArgumentException('Invalid file type. Expected one of ods, xlsx, csv. '.$filetype.' given.');
        }

        $filename = sys_get_temp_dir().'/purchased_items.'.$filetype;

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->build($worksheet, $country);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($filetype));
        $writer->save($filename);

        return $filename;
    }

    private function build(Worksheet $worksheet, Country $country)
    {
        $worksheet->getColumnDimension('A')->setWidth(16.852);
        $worksheet->getColumnDimension('B')->setWidth(14.423);
        $worksheet->getColumnDimension('C')->setWidth(16.614);
        $worksheet->getColumnDimension('D')->setWidth(18.136);
        $worksheet->getColumnDimension('E')->setWidth(13.565);
        $worksheet->getColumnDimension('F')->setWidth(13.565);
        $worksheet->getColumnDimension('G')->setWidth(12.565);
        $worksheet->getColumnDimension('H')->setWidth(14.853);
        $worksheet->getColumnDimension('I')->setWidth(14.853);
        $worksheet->getColumnDimension('J')->setWidth(14.853);
        $worksheet->getColumnDimension('K')->setWidth(14.853);
        $worksheet->getColumnDimension('L')->setWidth(14.853);
        $worksheet->getColumnDimension('M')->setWidth(19.136);
        $worksheet->getColumnDimension('N')->setWidth(14.423);
        $worksheet->getColumnDimension('O')->setWidth(14.423);
        $worksheet->getColumnDimension('P')->setWidth(14.423);
        $worksheet->getColumnDimension('Q')->setWidth(08.837);
        $worksheet->getColumnDimension('R')->setWidth(28.997);
        $worksheet->getColumnDimension('S')->setWidth(28.997);
        $worksheet->getColumnDimension('T')->setWidth(50.090);
        $worksheet->getRowDimension(1)->setRowHeight(28.705);
        $worksheet->setRightToLeft('right-to-left' === \Punic\Misc::getCharacterOrder($this->translator->getLocale()));
        $worksheet->getStyle('A1:T1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'font' => [
                'size' => 11,
                'bold' => true,
            ],
        ]);

        $dateFormatter = new \IntlDateFormatter($this->translator->getLocale(), \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

        $worksheet->setCellValue('A1', $this->translator->trans('Beneficiary ID'));
        $worksheet->setCellValue('B1', $this->translator->trans('Beneficiary Type'));
        $worksheet->setCellValue('C1', $this->translator->trans('Beneficiary First Name (local)'));
        $worksheet->setCellValue('D1', $this->translator->trans('Beneficiary Family Name (local)'));
        $worksheet->setCellValue('E1', $this->translator->trans('ID Number'));
        $worksheet->setCellValue('F1', $this->translator->trans('Phone'));
        $worksheet->setCellValue('G1', $this->translator->trans('Project Name'));
        $worksheet->setCellValue('H1', $this->translator->trans('Distribution Name'));
        $worksheet->setCellValue('I1', $this->translator->trans($country->getAdm1Name()));
        $worksheet->setCellValue('J1', $this->translator->trans($country->getAdm2Name()));
        $worksheet->setCellValue('K1', $this->translator->trans($country->getAdm3Name()));
        $worksheet->setCellValue('L1', $this->translator->trans($country->getAdm4Name()));
        $worksheet->setCellValue('M1', $this->translator->trans('Purchase Date & Time'));
        $worksheet->setCellValue('N1', $this->translator->trans('Commodity Type'));
        $worksheet->setCellValue('O1', $this->translator->trans('Carrier No.'));
        $worksheet->setCellValue('P1', $this->translator->trans('Item Purchased'));
        $worksheet->setCellValue('Q1', $this->translator->trans('Unit'));
        $worksheet->setCellValue('R1', $this->translator->trans('Total Cost'));
        $worksheet->setCellValue('S1', $this->translator->trans('Currency'));
        $worksheet->setCellValue('T1', $this->translator->trans('Vendor Name'));

        $i = 1;
        foreach ($this->repository->findByParams($country->getIso3()) as $purchasedItem) {
            $beneficiary = $purchasedItem->getBeneficiary();
            $assistance = $purchasedItem->getAssistance();
            $commodity = $purchasedItem->getCommodity();
            $datetime = $purchasedItem->getDatePurchase();

            $i++;
            $worksheet->setCellValue('A'.$i, $beneficiary->getId());
            $worksheet->setCellValue('B'.$i, $beneficiary->isHead() ? $this->translator->trans('Household') : $this->translator->trans('Individual'));
            $worksheet->setCellValue('C'.$i, $beneficiary->getLocalGivenName());
            $worksheet->setCellValue('D'.$i, $beneficiary->getLocalFamilyName());
            $worksheet->setCellValue('E'.$i, self::nationalId($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('F'.$i, self::phone($beneficiary) ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('G'.$i, $purchasedItem->getProject()->getName());
            $worksheet->setCellValue('H'.$i, $assistance->getName());
            $worksheet->setCellValue('I'.$i, self::adms($assistance)[0]);
            $worksheet->setCellValue('J'.$i, self::adms($assistance)[1]);
            $worksheet->setCellValue('K'.$i, self::adms($assistance)[2]);
            $worksheet->setCellValue('L'.$i, self::adms($assistance)[3]);
            $worksheet->setCellValue('M'.$i, $datetime ? $dateFormatter->format($datetime) : $this->translator->trans('N/A'));
            $worksheet->setCellValue('N'.$i, $purchasedItem->getModalityType());
            $worksheet->setCellValue('O'.$i, $purchasedItem->getCarrierNumber() ?? $this->translator->trans('N/A'));
            $worksheet->setCellValue('P'.$i, $purchasedItem->getProduct()->getName());
            $worksheet->setCellValue('Q'.$i, $commodity->getUnit());
            $worksheet->setCellValue('R'.$i, $purchasedItem->getValue());
            $worksheet->setCellValue('S'.$i, $purchasedItem->getCurrency());
            $worksheet->setCellValue('T'.$i, $purchasedItem->getVendor()->getName() ?? $this->translator->trans('N/A'));
        }
    }

    private static function phone(Beneficiary $beneficiary): ?string
    {
        /** @var Phone $phone */
        foreach ($beneficiary->getPerson()->getPhones() as $phone) {
            if (!$phone->getProxy()) {
                return $phone->getPrefix().' '.$phone->getNumber();
            }
        }

        return null;
    }

    private static function nationalId(Beneficiary $beneficiary): ?string
    {
        /** @var NationalId $nationalId */
        foreach ($beneficiary->getPerson()->getNationalIds() as $nationalId) {
            if (NationalId::TYPE_NATIONAL_ID === $nationalId->getIdType()) {
                return $nationalId->getIdNumber();
            }
        }

        return null;
    }

    private static function adms(Assistance $assistance): array
    {
        $adm = $assistance->getLocation()->getAdm();
        if ($adm instanceof Adm1) {
            return [$adm->getName(), null, null, null];
        }
        if ($adm instanceof Adm2) {
            return [$adm->getAdm1()->getName(), $adm->getName(), null, null];
        }
        if ($adm instanceof Adm3) {
            return [$adm->getAdm2()->getAdm1()->getName(), $adm->getAdm2()->getName(), $adm->getName(), null];
        }
        if ($adm instanceof Adm4) {
            return [$adm->getAdm3()->getAdm2()->getAdm1()->getName(), $adm->getAdm3()->getAdm2()->getName(), $adm->getAdm3()->getName(), $adm->getName()];
        }
    }
}

