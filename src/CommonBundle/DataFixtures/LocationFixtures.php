<?php


namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\Kernel;

class LocationFixtures extends Fixture implements FixtureGroupInterface
{
    private $devData = [ // countryIso3, adm1, adm2, adm3, adm4
        ['KHM', 'Rhone-Alpes', 'Savoie', 'Chambery', 'Sainte Hélène sur Isère'],
        ['KHM', 'Banteay Meanchey', 'Mongkol Borei', 'Banteay Neang', 'Trang']
    ];

    /** @var Kernel $kernel */
    private $kernel;


    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }


    /**
     * @param ObjectManager $manager
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() === "dev") {
            $locations = $this->loadSimplyData($manager);
            print_r("\n\n $locations location(s) loaded.\n\n");
        } else {
            $nbFilesLoaded = $this->parseDirectory($manager);
            print_r("\n\n $nbFilesLoaded file(s) loaded.\n\n");
        }
    }

    /**
     * This method must return an array of groups
     * on which the implementing class belongs to
     *
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['location'];
    }

    private function loadSimplyData(ObjectManager $manager)
    {
        $count = 0;
        foreach ($this->devData as $adms) {
            list($countryIso3, $adm1name, $adm2name, $adm3name, $adm4name) = $adms;

            $adm1 = new Adm1();
            $adm1->setCountryISO3($countryIso3)
                ->setName($adm1name);
            $manager->persist($adm1);

            $adm2 = new Adm2();
            $adm2->setAdm1($adm1)
                ->setName($adm2name);
            $manager->persist($adm2);

            $adm3 = new Adm3();
            $adm3->setAdm2($adm2)
                ->setName($adm3name);
            $manager->persist($adm3);

            $adm4 = new Adm4();
            $adm4->setAdm3($adm3)
                ->setName($adm4name);
            $manager->persist($adm4);

            $manager->flush();

            $count++;
        }
        return $count;
    }

    /**
     * @param ObjectManager $manager
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function parseDirectory(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $dir_root = $this->kernel->getRootDir();
        $dir_files = $dir_root . '/../src/CommonBundle/DataFixtures/LocationFiles';
        if (!is_dir($dir_files)) {
            return true;
        }

        $files = scandir($dir_files);
        $nbFilesLoaded = 0;

        foreach ($files as $file) {
            if ("." == $file || ".." == $file) {
                continue;
            }

            print_r("\n\nFILE : $file \n");
            $reader = new Xls();
            $spreadSheet = $reader->load($dir_files . "/" . $file);
            $iso3 = strtoupper(current(explode("_", $file)));
            $countSheets = $spreadSheet->getSheetCount();
            $sheet = $spreadSheet->getSheet($countSheets - 1);
            $rowIterator = $sheet->getRowIterator(2);
            $adm1List = [];
            $adm2List = [];
            $adm3List = [];
            $nbLines = $sheet->getHighestRow();
            $progressBar = new ProgressBar(new ConsoleOutput(), $nbLines);
            $progressBar->start();
            while (!empty($rowIterator->current()->getCellIterator()->current()->getValue())) {
                $rowIndex = $rowIterator->current()->getRowIndex();
                if (!array_key_exists($sheet->getCell('C' . $rowIndex)->getValue(), $adm1List)) {
                    $codeAdm1 = $sheet->getCell('D' . $rowIndex)->getValue();
                    $adm1 = $manager->getRepository(Adm1::class)->findOneByCode($codeAdm1);
                    if (!$adm1 instanceof Adm1) {
                        $adm1 = new Adm1();
                        $adm1->setCountryISO3($iso3)
                            ->setName(trim($sheet->getCell('C' . $rowIndex)->getValue()))
                            ->setCode($codeAdm1);
                        $manager->persist($adm1);
                    }
                    $adm1List[$sheet->getCell('C' . $rowIndex)->getValue()] = $adm1;
                }
                $adm1 = $adm1List[$sheet->getCell('C' . $rowIndex)->getValue()];



                if ($sheet->getCell('E' . $rowIndex)->getValue() == null) {
                    $progressBar->advance();
                    $rowIterator->next();
                    continue;
                }
                if (!array_key_exists($sheet->getCell('E' . $rowIndex)->getValue(), $adm2List)) {
                    $codeAdm2 = $sheet->getCell('F' . $rowIndex)->getValue();
                    $adm2 = $manager->getRepository(Adm2::class)->findOneByCode($codeAdm2);
                    if (!$adm2 instanceof Adm2) {
                        $adm2 = new Adm2();
                        $adm2->setName(trim($sheet->getCell('E' . $rowIndex)->getValue()))
                            ->setAdm1($adm1)
                            ->setCode($codeAdm2);
                        $manager->persist($adm2);
                    }
                    $adm2List[$sheet->getCell('E' . $rowIndex)->getValue()] = $adm2;
                }
                $adm2 = $adm2List[$sheet->getCell('E' . $rowIndex)->getValue()];


                if ($sheet->getCell('G' . $rowIndex)->getValue() == null) {
                    $progressBar->advance();
                    $rowIterator->next();
                    continue;
                }
                if (!array_key_exists($sheet->getCell('G' . $rowIndex)->getValue(), $adm3List)) {
                    $codeAdm3 = $sheet->getCell('H' . $rowIndex)->getValue();
                    $adm3 = $manager->getRepository(Adm3::class)->findOneByCode($codeAdm3);
                    if (!$adm3 instanceof Adm3) {
                        $adm3 = new Adm3();
                        $adm3->setName(trim($sheet->getCell('G' . $rowIndex)->getValue()))
                            ->setAdm2($adm2)
                            ->setCode($codeAdm3);
                        $manager->persist($adm3);
                    }
                    $adm3List[$sheet->getCell('G' . $rowIndex)->getValue()] = $adm3;
                }
                $adm3 = $adm3List[$sheet->getCell('G' . $rowIndex)->getValue()];


                if ($sheet->getCell('I' . $rowIndex)->getValue() == null) {
                    $progressBar->advance();
                    $rowIterator->next();
                    continue;
                }
                $codeAdm4 = $sheet->getCell('J' . $rowIndex)->getValue();
                $adm4 = $manager->getRepository(Adm4::class)->findOneByCode($codeAdm4);
                if (!$adm4 instanceof Adm4) {
                    $adm4 = new Adm4();
                    $adm4->setName(trim($sheet->getCell('I' . $rowIndex)->getValue()))
                        ->setCode($codeAdm4)
                        ->setAdm3($adm3);
                    $manager->persist($adm4);
                }

                $rowIterator->next();

                if ($rowIndex % 1000 == 0) {
                    $manager->flush();
                }

                $progressBar->advance();
            }
            $progressBar->finish();

            $manager->flush();
            $manager->clear();
            $nbFilesLoaded++;
        }
        return $nbFilesLoaded;
    }
}
