<?php


namespace BeneficiaryBundle\Utils\Mapper;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractMapper
{
    /** @var EntityManagerInterface $em */
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    protected $moreThanZ = false;

    /**
     * Load the mapping CSV for a specific country. Some columns can move because on the number of country specifics
     *
     * @param $countryIso3
     * @return array
     */
    protected function loadMappingCSVOfCountry($countryIso3)
    {
        /** @var CountrySpecific[] $countrySpecifics */
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($countryIso3);
        // Get the number of country specific for the specific country countryIso3
        $nbCountrySpecific = count($countrySpecifics);
        $mappingCSVCountry = [];
        foreach (Household::MAPPING_CSV as $indexFormatted => $indexCSV) {
            // For recursive array (allowed only 1 level of recursivity)
            if (is_array($indexCSV)) {
                foreach ($indexCSV as $indexFormatted2 => $indexCSV2) {
                    $mappingCSVCountry[$indexFormatted][$indexFormatted2] = $indexCSV2;
                }
            } else {
                $mappingCSVCountry[$indexFormatted] = $indexCSV;
            }
        }

        // SUMOfLetter is not correct if it is start from "AI"
        // Workaround "AA" + 9
        $lastIndexCSV = 'AA';
        $nbstaticField = 0;
        $staticFields = [
            'f-0-2', 'f-2-5', 'f-6-17', 'f-18-64', 'f-65-99',
            'm-0-2', 'm-2-5', 'm-6-17', 'm-18-64', 'm-65-99',
        ];
        for ($i = 0; $i < count($staticFields); $i++) {
            $mappingCSVCountry['member_' . $staticFields[$i]] = $this->SUMOfLetter($lastIndexCSV, $i + 9);
        }

        $nbstaticField = count($staticFields);

        // Add each country specific column in the mapping
        for ($i = 0; $i < $nbCountrySpecific; $i++) {
            $mappingCSVCountry["tmp_country_specific" . $i] =
                $this->SUMOfLetter($lastIndexCSV, $i + $nbstaticField + 9);
        }

        return $mappingCSVCountry;
    }

    /**
     * Make an addition of a letter and a number
     * Example : A + 2 = C  Or  Z + 1 = AA  OR  AY + 2 = BA
     * @param $letter1
     * @param $number
     * @return string
     */
    protected function SUMOfLetter($letter1, $number)
    {
        $ascii = ord($letter1) + $number;
        $prefix = '';
        if ($letter1 == 'AA' || $this->moreThanZ) {
            $prefix = 'A';
            $this->moreThanZ = true;
            $ascii = 26 + ord(substr($letter1[1], 0, 1)) + $number;
        }

        if ($ascii > 90) {
            $prefix = 'A';
            $ascii -= 26;
            while ($ascii > 90) {
                $prefix++;
                $ascii -= 90;
            }
        }
        return $prefix . chr($ascii);
    }
}
