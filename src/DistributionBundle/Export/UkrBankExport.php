<?php

namespace DistributionBundle\Export;

use BeneficiaryBundle\Utils\ExcelColumnsGenerator;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\DistributionBeneficiary;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UkrBankExport
{
    private const HEADERS = [
        ['ORIGINALID', 'Номер по порядку /Ordinal number', 5.73],
        ['F1', 'Прізвище Отримувача / Recipient’s surname', 13.67],
        ['F2', 'Ім’я Отримувача / Recipient’s name', 11.17],
        ['F3', 'По батькові Отримувача / Recipient’s patronymic', 15.58],
        ['DRFO', 'РНОКПП Отримувача / Recipient’s RNTRC', 11.75],
        ['VDOC', 'Тип документа  / Document type', 9.40],
        ['SDOC', 'Серія документа / Document series', 8.23],
        ['NDOC', 'Номер документа / Document number', 10.43],
        ['DEST', 'Призначення переказу / Remittance purpose', 18.81],
        ['SM', 'Сума переказу / Remittance amount', 11.61],
        ['SMS', '№ мобільного телефону Отримувача / Recipient’s mobile telephone number', 13.96],
    ];

    public function export(Assistance $distribution)
    {
        if ('UKR' !== $distribution->getProject()->getIso3()) {
            throw new \InvalidArgumentException('Distribution is not exportable to UKR bank sheet.');
        }

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        self::buildTitle($worksheet);
        list($lastCol, $lastRow) = self::buildBody($worksheet, $distribution);
        list($lastCol, $lastRow) = self::buildFooter($worksheet, $lastCol, $lastRow);

        $worksheet->getStyle('A1:'.$lastCol.($lastRow + 1))->getFont()->setName('Times New Roman');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('ukrbank.xlsx');

        return 'ukrbank.xlsx';
    }

    private static function getValue(DistributionBeneficiary $distributionBeneficiary, string $title)
    {
        static $i = 0;

        $person = $distributionBeneficiary->getBeneficiary()->getPerson();

        $nationalIds = $person->getNationalIds()->toArray();
        $nationalId = reset($nationalIds);

        $sum = 0.0;
        foreach ($distributionBeneficiary->getTransactions() as $transaction) {
            /* @var \TransactionBundle\Entity\Transaction $transaction */
            $sum += $transaction->getAmountSent();
        }

        switch ($title) {
            case 'ORIGINALID':
                return ++$i;
            case 'F1':
                return $person->getLocalFamilyName();
            case 'F2':
                return $person->getLocalGivenName();
            case 'F3':
                return $person->getLocalParentsName();
            case 'VDOC':
                return $nationalId->getIdType();
            case 'NDOC':
                return $nationalId->getIdNumber();
            case 'SM':
                return $sum;
            case 'SMS':
                $phones = $distributionBeneficiary->getBeneficiary()->getPerson()->getPhones()->toArray();
                $phone = reset($phones);

                return $phone ? $phone->getPrefix().$phone->getNumber() : null;
            default:
                return null;
        }
    }

    private static function buildTitle(Worksheet $worksheet)
    {
        $worksheet->getStyle('A1:K7')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'name' => 'Times New Roman',
            ],
            'alignment' => [
                'horizontal' => Style\Alignment::HORIZONTAL_CENTER,
            ],
            '',
        ]);

        $worksheet->setCellValue('A1', 'Реєстр безготівкових термінових переказів від суб’єктів господарювання № 3_OFDA6  від 02.04.2020 ');
        $worksheet->getRowDimension('1')->setRowHeight(20);
        $worksheet->mergeCells('A1:K1');

        $worksheet->setCellValue('A2', 'згідно контракту № 611 від 22.11.2019');
        $worksheet->getRowDimension('2')->setRowHeight(20);
        $worksheet->mergeCells('A2:K2');

        $worksheet->setCellValue('A3', 'Register of non-cash express remittances from economic entities № 3_OFDA6  від 02.04.2020 dated ');
        $worksheet->getRowDimension('3')->setRowHeight(20);
        $worksheet->mergeCells('A3:K3');

        $worksheet->setCellValue('A4', 'according to the contract  No. 611 22.11.19');
        $worksheet->getRowDimension('4')->setRowHeight(20);
        $worksheet->mergeCells('A4:K4');

        $worksheet->setCellValue('A6', '/ IBAN: UA233206490000026008052623065 в у ПАТ КБ «ПриватБанк» / in in Commercial Bank “PrivatBank” PJSC');
        $worksheet->mergeCells('A6:K6');

        $worksheet->setCellValue('A7', '(найменування організації та реквізити рахунку для повернення переказу / name of the organisation '.
            'and essential details of the account for the return of the remittance)');
        $worksheet->mergeCells('A7:K7');

        $worksheet->getStyle('A7:K7')->getFont()->setBold(false);
        $worksheet->getStyle('A6:K7')->getAlignment()->setHorizontal(Style\Alignment::HORIZONTAL_LEFT);

        return 7;
    }

    private static function buildBody(Worksheet $worksheet, Assistance $distribution)
    {
        $dim = [];

        $col = 'A';
        $generator = new ExcelColumnsGenerator();
        foreach (self::HEADERS as list($code, $title, $width)) {
            $dim[$code] = $col = $generator->getNext();
            $worksheet->getColumnDimension($col)->setWidth($width);
            $worksheet->setCellValue($col.'9', $code);
            $worksheet->setCellValue($col.'10', $title);
        }

        $style = $worksheet->getStyle('A9:'.$col.'9');
        $style->getAlignment()
            ->setHorizontal(Style\Alignment::HORIZONTAL_CENTER);
        $style->getFont()
            ->setSize(11.5);

        $worksheet->getRowDimension('10')->setRowHeight(102);
        $style = $worksheet->getStyle('A10:'.$col.'10');
        $style->getAlignment()
            ->setHorizontal(Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $style->getFont()
            ->setSize(10)
            ->setItalic(true);

        $row = 11;
        foreach ($distribution->getDistributionBeneficiaries() as $distributionBeneficiary) {
            foreach (self::HEADERS as list($code)) {
                $coord = $dim[$code].$row;
                $value = self::getValue($distributionBeneficiary, $code);
                $worksheet->setCellValue($coord, $value);

                if (in_array($code, ['F1', 'F2', 'F3'])) {
                    $worksheet->getStyle($coord)->getFont()->setSize(11);
                } else {
                    $worksheet->getStyle($coord)->getFont()->setSize(10);
                }

                if ('SM' === $code) {
                    $worksheet->getStyle($coord)->getNumberFormat()
                        ->setFormatCode(Style\NumberFormat::FORMAT_NUMBER_00);
                    $worksheet->getStyle($coord)->getAlignment()
                        ->setHorizontal(Style\Alignment::HORIZONTAL_CENTER);
                }
            }
            ++$row;
        }

        $worksheet->getStyle('A9:'.$col.($row - 1))->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Style\Border::BORDER_THIN);

        return [$col, $row];
    }

    private static function buildFooter(Worksheet $worksheet, $col, $row)
    {
        ++$row;

        // summaries
        $style = $worksheet->getStyle('A'.$row.':'.$col.($row + 2));
        $style->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Style\Border::BORDER_THIN);
        $style->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(Style\Alignment::VERTICAL_CENTER);
        $style->getFont()
            ->setBold(true);

        $style = $worksheet->getStyle('A'.($row + 2).':'.$col.($row + 14));
        $style->getAlignment()
            ->setWrapText(false);
        $style->getFont()
            ->setSize(10.5)
            ->setBold(true);

        $worksheet->setCellValue('A'.($row), 'Загальна сума, грн. / Total amount, UAH');
        $worksheet->mergeCells('A'.($row).':C'.($row));
        $worksheet->mergeCells('D'.($row).':'.$col.($row));
        $worksheet->getRowDimension($row)->setRowHeight(34.74);

        $worksheet->setCellValue('A'.($row + 1), 'Сума комісії, грн. / Commission amount, UAH');
        $worksheet->mergeCells('A'.($row + 1).':C'.($row + 1));
        $worksheet->mergeCells('D'.($row + 1).':'.$col.($row + 1));
        $worksheet->getRowDimension($row + 1)->setRowHeight(34.74);

        $worksheet->setCellValue('A'.($row + 2), 'Загальна сума до перерахування, грн. / Total amount to be transferred, UAH');
        $worksheet->mergeCells('A'.($row + 2).':C'.($row + 2));
        $worksheet->mergeCells('D'.($row + 2).':'.$col.($row + 2));
        $worksheet->getRowDimension($row + 2)->setRowHeight(44.23);

        $worksheet->setCellValue('B'.($row + 4), 'Date/ Дата___________________________');
        $worksheet->mergeCells('B'.($row + 4).':E'.($row + 4));

        $worksheet->setCellValue('A'.($row + 6), 'Керівник проектів та програм у сфері нематеріального виробництва');

        $worksheet->setCellValue('A'.($row + 7), ' / Program and Non-Material Production Manager');
        $worksheet->setCellValue('J'.($row + 7), 'Kuznetsova Daria / Кузнецова Дар\'я');
        $worksheet->getStyle('A'.($row + 7).':H'.($row + 7))
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);

        $worksheet->setCellValue('A'.($row + 8), '(П.І.Б. уповноваженого представника юридичної особи або фізичної особи-підприємця '.
            '(або представника фізичної особи-підприємця) / Surname, name and patronymic of the authorised representative'.
            ' of the legal entity of the natural person-entrepreneur (or the representative of the natural person-entrepreneur))');
        $worksheet->mergeCells('A'.($row + 8).':J'.($row + 8));
        $worksheet->getStyle('A'.($row + 8))->getAlignment()
            ->setWrapText(true);
        $worksheet->getStyle('A'.($row + 8))->getFont()
            ->setSize(9)
            ->setBold(false);

        $worksheet->setCellValue('A'.($row + 10), 'Менеджер з фінансових питань та бухгалтерії / Finance and Accounting Manager');
        $worksheet->setCellValue('J'.($row + 10), 'Glushkova Olena / Глушкова Олена');
        $style = $worksheet->getStyle('A'.($row + 10).':H'.($row + 10));
        $style
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);
        $style->getAlignment()
            ->setHorizontal(Style\Alignment::HORIZONTAL_GENERAL);

        $worksheet->setCellValue('A'.($row + 11), '(прізвище, ініціали, підпис/surname, initials, signature)');
        $worksheet->getStyle('A'.($row + 11))->getFont()
            ->setSize(9)
            ->setBold(false);

        $worksheet->setCellValue('A'.($row + 13), 'Працівник, який здає відправлення / Employee who hands in');
        $worksheet->setCellValue('J'.($row + 13), 'Yerofieieva Inna / Єрофєєва Інна');
        $worksheet->getStyle('A'.($row + 13).':H'.($row + 13))
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Style\Border::BORDER_THIN);

        $worksheet->setCellValue('A'.($row + 14), '(прізвище, ініціали, підпис/surname, initials, signature)');
        $worksheet->getStyle('A'.($row + 14))->getFont()
            ->setSize(9)
            ->setBold(false);

        $worksheet->getRowDimension($row + 8)->setRowHeight(28.44);

        return [$col, $row + 13];
    }
}
