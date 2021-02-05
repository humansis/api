<?php

namespace ReportingBundle\Utils\DataFillers;

use ReportingBundle\Utils\DataFillers\DataFillersInterface;
use ReportingBundle\Utils\DataFillers\Country\DataFillersCountry;
use ReportingBundle\Utils\DataFillers\Project\DataFillersProject;
use ReportingBundle\Utils\DataFillers\Distribution\DataFillersDistribution;
use ReportingBundle\Utils\Model\IndicatorInterface;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DataFillers
 * @package ReportingBundle\Utils\DataFillers
 */
class DataFillers implements DataFillersInterface
{

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * DataFillers constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param IndicatorInterface $indicator
     * @return mixed
     */
    public function fill(IndicatorInterface $indicator)
    {
        if (preg_match("#^BMSU?_C#", $indicator->getCode())) {
            if (is_callable(array(new DataFillersCountry($this->em), $indicator->getCode()))) {
                return call_user_func_array([new DataFillersCountry($this->em), $indicator->getCode()], []);
            }
        }

        if (preg_match("#^BMSU?_P#", $indicator->getCode())) {
            if (is_callable(array(new DataFillersProject($this->em), $indicator->getCode()))) {
                return call_user_func_array([new DataFillersProject($this->em), $indicator->getCode()], []);
            }
        }

        if (preg_match("#^BMSU?_D#", $indicator->getCode())) {
            if (is_callable(array(new DataFillersDistribution($this->em), $indicator->getCode()))) {
                return call_user_func_array([new DataFillersDistribution($this->em), $indicator->getCode()], []);
            }
        }
    }
}
