<?php

namespace DistributionBundle\Export;

use BeneficiaryBundle\Utils\ExcelColumnsGenerator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\DistributionBeneficiary;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UkrPostExport
{
    private const HEADERS = [
        ['NPP', '0', 4.25],
        ['NPS', '0', 5.00],
        ['LASTNAME', 'ФІЛІЯ КОМПАНІЇ\nЛЮДИНА В БІДІ', 14.50],
        ['FIRSTNAME', '', 12.00],
        ['MIDDLENAME', '', 15.50],
        ['ZIP', '01034', 6.00],
        ['CITY', 'КИЇВ', 13.25],
        ['COUNTRY', 'UA', 4.00],
        ['REGION', 'КИЇВ', 9.75],
        ['DISTRICT', 'КИЇВ', 13.00],
        ['STREET', 'ПРОРІЗНА', 14.25],
        ['BUILDING', '4', 10.75],
        ['APT', '21', 7.50],
        ['AMOUNT', '0,00', 10.25],
        ['LUI', '0,00', 13.75],
        ['PHONE', '0507787276', 23.00],
    ];

    public function export(Assistance $distribution)
    {
        if ('UKR' !== $distribution->getProject()->getIso3()) {
            throw new \InvalidArgumentException('Distribution is not exportable to UKR post sheet.');
        }

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::buildTitle($worksheet);
        list($lastCol, $lastRow) = self::buildBody($worksheet, $distribution);
        self::buildFooter($worksheet, $lastCol, $lastRow);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('ukrposta.xlsx');

        return 'ukrposta.xlsx';
    }

    private static function getValue(DistributionBeneficiary $distributionBeneficiary, string $title)
    {
        $person = $distributionBeneficiary->getBeneficiary()->getPerson();

        $locations = $distributionBeneficiary->getBeneficiary()->getHousehold()->getHouseholdLocations()->toArray();
        $location = reset($locations);

        $sum = 0.0;
        foreach ($distributionBeneficiary->getTransactions() as $transaction) {
            /* @var \TransactionBundle\Entity\Transaction $transaction */
            $sum += $transaction->getAmountSent();
        }

        switch ($title) {
            case 'LASTNAME':
                return $person->getLocalFamilyName();
            case 'FIRSTNAME':
                return $person->getLocalGivenName();
            case 'MIDDLENAME':
                return $person->getLocalParentsName();
            case 'ZIP':
                return $location->getAddress()->getPostcode();
            case 'CITY':
                return $location->getAddress()->getLocation()->getAdm4Name();
            case 'COUNTRY':
                return 'UA';
            case 'REGION':
                return $location->getAddress()->getLocation()->getAdm1Name();
            case 'DISTRICT':
                return $location->getAddress()->getLocation()->getAdm2();
            case 'STREET':
                return $location->getAddress()->getStreet();
            case 'BUILDING':
                return $location->getAddress()->getNumber();
            case 'AMOUNT':
                return $sum;
            case 'LUI':
                return ($sum < 2000) ? $sum * 0.03 : ($sum < 3000) ? $sum * 0.01 : $sum * 0.008;
            case 'PHONE':
                $phones = $distributionBeneficiary->getBeneficiary()->getPerson()->getPhones()->toArray();
                $phone = reset($phones);

                return $phone ? $phone->getPrefix().$phone->getNumber() : null;
            default:
                return null;
        }
    }

    private static function buildTitle(Worksheet $worksheet)
    {
        $worksheet->setCellValue('C2', 'Договор № 11/1070');
        $worksheet->setCellValue('C3', 'від «07» жовтня 2019 року');
        $worksheet->setCellValue('J2', 'to the Agreement No. 11/1070');
        $worksheet->setCellValue('J3', 'dated «07» October 2019');
        $worksheet->setCellValue('A5', 'СПИСОК');
        $worksheet->setCellValue('A6', 'згрупованих поштових переказів електронних ф.103-1/№34/05.05.2020');
        $worksheet->setCellValue('J5', 'LIST');
        $worksheet->setCellValue('J6', 'of grouped electronic postal transfers f.103-1/№34/05.05.2020');
        $worksheet->mergeCells('A5:G5');
        $worksheet->mergeCells('J5:O5');

        $worksheet->getStyle('A1:P6')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Times New Roman',
            ],
        ]);

        return 6;
    }

    private static function buildBody(Worksheet $worksheet, Assistance $distribution)
    {
        $dim = [];

        $col = 'A';
        $generator = new ExcelColumnsGenerator();
        foreach (self::HEADERS as list($title, $example, $width)) {
            $dim[$title] = $col = $generator->getNext();
            $worksheet->getColumnDimension($col)->setWidth($width);
            $worksheet->setCellValue($col.'8', $title);
            $worksheet->setCellValue($col.'9', $example);
        }

        $worksheet->getStyle('A8:'.$col.'9')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 9,
                'name' => 'Sans Serif',
            ],
            'alignment' => [
                'horizontal' => Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Style\Border::BORDER_THIN,
                    'color' => [
                        'argb' => '000000',
                    ],
                ],
            ],
            'fill' => [
                'fillType' => Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FAC090',
                ],
            ],
        ]);

        $worksheet->getRowDimension('9')->setRowHeight(28);

        $row = 10;
        foreach ($distribution->getDistributionBeneficiaries() as $distributionBeneficiary) {
            foreach (self::HEADERS as list($title)) {
                $coord = $dim[$title].$row;
                $value = self::getValue($distributionBeneficiary, $title);
                $worksheet->setCellValue($coord, $value);

                if (in_array($title, ['AMOUNT', 'LUI'])) {
                    $worksheet->getStyle($coord)->getNumberFormat()
                        ->setFormatCode(Style\NumberFormat::FORMAT_NUMBER_00);
                    $worksheet->getStyle($coord)->getAlignment()
                        ->setHorizontal(Style\Alignment::HORIZONTAL_CENTER);
                } else {
                    $worksheet->getStyle($coord)->getAlignment()
                        ->setHorizontal(Style\Alignment::HORIZONTAL_LEFT);
                }
            }
            ++$row;
        }

        $worksheet->getStyle('A10:'.$col.($row - 1))->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Style\Border::BORDER_THIN);

        return [$col, $row];
    }

    private static function buildFooter(Worksheet $worksheet, $col, $row)
    {
        ++$row;

        $worksheet->getStyle('B'.($row).':'.$col.($row + 17))->getFont()
            ->setBold(true);

        $worksheet->setCellValue('B'.($row), 'Разом / Total:');
        $worksheet->setCellValue('B'.($row + 2), 'Плата за пересилання / Transfer fee: ');
        $worksheet->setCellValue('B'.($row + 4), 'Усього / Total: 202 512,72 (Двісті дві тисячі п\'ятсот дванадцять гривень 72 копiйки).');

        $worksheet->getStyle('B'.($row + 6).':'.$col.($row + 17))->getFont()
            ->setName('Times New Roman');
        $worksheet->setCellValue('B'.($row + 6), 'Керівник проекту / Head of Project');
        $worksheet->setCellValue('B'.($row + 7), '(прізвище, ініціали, підпис/surname, initials, signature)');
        $worksheet->getStyle('B'.($row + 7).':H'.($row + 7))->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);
        $worksheet->getStyle('B'.($row + 7).':H'.($row + 7))->getFont()
            ->setSize(9);
        $worksheet->mergeCells('B'.($row + 6).':J'.($row + 6));
        $worksheet->mergeCells('B'.($row + 7).':J'.($row + 7));

        $worksheet->setCellValue('B'.($row + 9), ' Менеджер з фінансових питань та бухгалтерії / Finance and Accounting Manager');
        $worksheet->setCellValue('B'.($row + 10), '(прізвище, ініціали, підпис/surname, initials, signature)');
        $worksheet->getStyle('B'.($row + 10).':H'.($row + 10))->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);
        $worksheet->getStyle('B'.($row + 10).':H'.($row + 10))->getFont()
            ->setSize(9)
            ->setBold(false);
        $worksheet->mergeCells('B'.($row + 9).':J'.($row + 9));
        $worksheet->mergeCells('B'.($row + 10).':J'.($row + 10));

        $worksheet->setCellValue('B'.($row + 12), 'Працівник, який здає відправлення / Employee who hands in');
        $worksheet->setCellValue('B'.($row + 13),
            'до об’єкта поштового зв’язку / the postal item to the postal establishment  (прізвище, ініціали, підпис/surname, initials, signature)');
        $worksheet->getStyle('B'.($row + 12).':H'.($row + 12))->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);
        $worksheet->getStyle('B'.($row + 13).':H'.($row + 13))->getFont()
            ->setSize(9)
            ->setBold(false);
        $worksheet->mergeCells('B'.($row + 12).':J'.($row + 12));
        $worksheet->mergeCells('B'.($row + 13).':J'.($row + 13));

        $worksheet->setCellValue('B'.($row + 15), 'Прийняв/Received by');
        $worksheet->setCellValue('B'.($row + 16),
            '(посада, прізвище, ініціали, підпис працівника зв’язку/title, surname, initials, postal service employee’s signature)');
        $worksheet->getStyle('B'.($row + 15).':H'.($row + 15))->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);
        $worksheet->getStyle('B'.($row + 16).':H'.($row + 16))->getFont()
            ->setSize(9)
            ->setBold(false);
        $worksheet->mergeCells('B'.($row + 15).':J'.($row + 15));
        $worksheet->mergeCells('B'.($row + 16).':J'.($row + 16));

        $worksheet->setCellValue('K'.($row + 6), 'Kuznetsova Daria / Кузнецова Дар\'я');
        $worksheet->setCellValue('K'.($row + 9), 'Glushkova Olena / Глушкова Олена');
        $worksheet->setCellValue('K'.($row + 12), 'Yerofieieva Inna / Єрофєєва Інна');
        $worksheet->setCellValue('K'.($row + 17), 'Date/ Дата___________________________');

        return [$col, $row + 17];
    }
}
