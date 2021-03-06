<?php

namespace CommonBundle\Utils;

use BeneficiaryBundle\Utils\ExcelColumnsGenerator;
use CommonBundle\Controller\ExportController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ExportService.
 */
class ExportService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_ODS = 'ods';

    /**
     * Generate file.
     *
     * @param Spreadsheet $spreadsheet
     * @param string      $name
     * @param string      $type
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateFile(Spreadsheet $spreadsheet, string $name, string $type)
    {
        if (self::FORMAT_CSV == $type) {
            $writer = IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->setEnclosure('');
            $writer->setDelimiter(';');
            $writer->setUseBOM(true);
            $filename = $name.'.csv';
        } elseif (self::FORMAT_XLSX == $type) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = $name.'.xlsx';
        } elseif (self::FORMAT_ODS == $type) {
            $writer = IOFactory::createWriter($spreadsheet, 'Ods');
            $filename = $name.'.ods';
        } else {
            return 'An error occured with the type file: '.$type;
        }

        $writer->save($filename);

        return $filename;
    }

    /**
     * Export data to file (csv, xlsx, ods).
     *
     * @param        $exportableTable
     * @param string $name
     * @param string $type
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export($exportableTable, string $name, string $type)
    {
        if (0 === count($exportableTable)) {
            throw new \InvalidArgumentException('No data to export');
        }

        $rows = $this->normalize($exportableTable);

        $rowIndex = 1;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();

        $tableHeaders = $this->getHeaders($rows);

        $generator = new ExcelColumnsGenerator();

        foreach ($tableHeaders as $i => $value) {
            $worksheet->setCellValue($generator->getNext().$rowIndex, $value);
        }

        foreach ($rows as $key => $value) {
            ++$rowIndex;
            $generator->reset();
            foreach ($tableHeaders as $i => $header) {
                $worksheet->setCellValue($generator->getNext().$rowIndex, $value[$header] ?? null);
            }
        }

        return $this->generateFile($spreadsheet, $name, $type);
    }

    /**
     * Export two-dimension array to file (csv, xlsx, ods).
     *
     * @param array[] $exportTable
     * @param string  $name
     * @param string  $type
     *
     * @return string $filename
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportRaw(array $exportTable, string $name, string $type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($exportTable, null, 'A1');
        $filename = $this->generateFile($spreadsheet, $name, $type);

        return $filename;
    }

    /**
     * @param $exportableTable
     *
     * @return array
     */
    private function normalize($exportableTable)
    {
        $normalizedTable = [];

        foreach ($exportableTable as $value) {
            if ($value instanceof ExportableInterface) {
                $normalizedTable[] = $value->getMappedValueForExport();
            } elseif (is_array($value)) {
                $normalizedTable[] = $value;
            } else {
                throw new \InvalidArgumentException("The table to export contains a not allowed content ($value). Allowed content: array, ".ExportableInterface::class);
            }
        }

        return $normalizedTable;
    }

    /**
     * Return list of header names.
     *
     * We get all the keys that will become the column names for the csv.
     * We merge the results because some rows can have more or less columns
     *
     * @param array $exportableTable
     *
     * @return array list of all headers of exported table
     */
    private function getHeaders($exportableTable)
    {
        $headers = [];

        foreach ($exportableTable as $row) {
            foreach ($row as $key => $value) {
                $headers[$key] = true;
            }
        }

        return array_keys($headers);
    }
}
