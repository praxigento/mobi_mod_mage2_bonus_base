<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusBase\Service\Period\Sub;

use Praxigento\BonusBase\Config as Cfg;
use Praxigento\BonusBase\Data\Entity\Calculation as ECalculation;
use Praxigento\BonusBase\Data\Entity\Period as EPeriod;

/**
 * Period service's internal code for depended calculations.
 */
class Depended
{
    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;
    /** @var \Praxigento\BonusBase\Repo\Entity\ICalculation */
    protected $_repoCalc;
    /** @var \Praxigento\BonusBase\Repo\Entity\IPeriod */
    protected $_repoPeriod;
    /** @var \Praxigento\BonusBase\Repo\Service\IModule */
    protected $_repoService;
    /** @var \Praxigento\Core\Tool\IDate */
    protected $_toolDate;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Praxigento\BonusBase\Repo\Entity\ICalculation $repoCalc,
        \Praxigento\BonusBase\Repo\Entity\IPeriod $repoPeriod,
        \Praxigento\BonusBase\Repo\Service\IModule $repoService,
        \Praxigento\Core\Tool\IDate $toolDate

    ) {
        $this->_logger = $logger;
        $this->_repoCalc = $repoCalc;
        $this->_repoPeriod = $repoPeriod;
        $this->_repoService = $repoService;
        $this->_toolDate = $toolDate;
    }

    /**
     * Analyze depended calculation data and to the results if state is incomplete.
     *
     * This function is created for CRAP reducing and is used from this class only.
     *
     * @param \Praxigento\BonusBase\Service\Period\Response\GetForDependentCalc $result
     * @param string $dependentCalcTypeCode
     * @param int $dependentCalcTypeId
     * @param string $dependentDsBegin
     * @param string $dependentDsEnd
     */
    public function _analyzeDependedCalc(
        $result,
        $dependentCalcTypeCode,
        $dependentCalcTypeId,
        $dependentDsBegin,
        $dependentDsEnd
    ) {
        $dependentCalcData = $this->_repoService->getLastCalcForPeriodByDates(
            $dependentCalcTypeId,
            $dependentDsBegin,
            $dependentDsEnd
        );
        if (
            $dependentCalcData &&
            ($dependentCalcData->getState() == Cfg::CALC_STATE_COMPLETE)
        ) {
            /* complete dependent period for complete base period */
            $this->_logger->warning("There is '$dependentCalcTypeCode' period with complete calculation. No more '$dependentCalcTypeCode' could be calculated.");
        } else {
            /* incomplete dependent period for complete base period */
            $this->_logger->warning("There is '$dependentCalcTypeCode' period without complete calculation. Continue calculation for this period.");
            $result->setDependentCalcData($dependentCalcData);
        }
    }

    /**
     * Create new depended period and calculation or return existing data.
     *
     * This function is created for CRAP reducing and is used from this class only.
     *
     * @param \Praxigento\BonusBase\Service\Period\Response\GetForDependentCalc $result
     * @param string $baseCalcTypeCode
     * @param string $baseDsBegin
     * @param string $baseDsEnd
     * @param string $dependentCalcTypeCode
     * @param int $dependentCalcTypeId
     * @param string $dependentDsBegin
     * @param string $dependentDsEnd
     */
    public function _getDependedCalcForExistingPeriod(
        $result,
        $baseCalcTypeCode,
        $baseDsBegin,
        $baseDsEnd,
        $dependentCalcTypeCode,
        $dependentCalcTypeId,
        $dependentDsBegin,
        $dependentDsEnd
    ) {
        if (
            ($dependentDsBegin == $baseDsBegin) &&
            ($dependentDsEnd == $baseDsEnd)
        ) {
            /* dependent period has the same begin/end as related base period, get calc data */
            $this->_logger->info("There is base '$baseCalcTypeCode' period for dependent '$dependentCalcTypeCode' period ($dependentDsBegin-$dependentDsEnd).");
            $this->_analyzeDependedCalc(
                $result,
                $dependentCalcTypeCode,
                $dependentCalcTypeId,
                $dependentDsBegin,
                $dependentDsEnd
            );
        } else {
            /* dependent period has different begin/end than related base period */
            $this->_logger->warning("There is no period for '$dependentCalcTypeCode' calculation based on '$baseCalcTypeCode' ($baseDsBegin-$baseDsEnd). New period and related calculation will be created.");
            /* create new depended period & calc */
            $period = new EPeriod();
            $period->setCalcTypeId($dependentCalcTypeId);
            $period->setDstampBegin($baseDsBegin);
            $period->setDstampEnd($baseDsEnd);
            $periodId = $this->_repoPeriod->create($period);
            $period->setId($periodId);
            /* create related calculation */
            $calc = new ECalculation();
            $calc->setPeriodId($periodId);
            $dateStarted = $this->_toolDate->getUtcNowForDb();
            $calc->setDateStarted($dateStarted);
            $calc->setState(Cfg::CALC_STATE_STARTED);
            $calcId = $this->_repoCalc->create($calc);
            $calc->setId($calcId);
            /* place new objects into response */
            $result->setDependentPeriodData($period);
            $result->setDependentCalcData($calc);
        }
    }

    /**
     * This function is created for CRAP reducing and is used from this class only.
     *
     * @param \Praxigento\BonusBase\Service\Period\Response\GetForDependentCalc $result
     * @param string $baseCalcTypeCode
     * @param string $baseDsBegin
     * @param string $baseDsEnd
     * @param string $dependentCalcTypeCode
     * @param int $dependentCalcTypeId
     */
    public function _getForCompleteBase(
        $result,
        $baseCalcTypeCode,
        $baseDsBegin,
        $baseDsEnd,
        $dependentCalcTypeCode,
        $dependentCalcTypeId
    ) {
        $dependPeriodData = $this->_repoService->getLastPeriodByCalcType($dependentCalcTypeId);
        if (is_null($dependPeriodData)) {
            /* there is no dependent period, create new period and calc */
            $msg = "There is no period data for calculation '$dependentCalcTypeCode'."
                . " New period and related calculation will be created.";
            $this->_logger->warning($msg);
            /* create new period for given calculation type */
            $period = new EPeriod();
            $period->setCalcTypeId($dependentCalcTypeId);
            $period->setDstampBegin($baseDsBegin);
            $period->setDstampEnd($baseDsEnd);
            $periodId = $this->_repoPeriod->create($period);
            $period->setId($periodId);
            /* create related calculation */
            $calc = new ECalculation();
            $calc->setPeriodId($periodId);
            $dateStarted = $this->_toolDate->getUtcNowForDb();
            $calc->setDateStarted($dateStarted);
            $calc->setState(Cfg::CALC_STATE_STARTED);
            $calcId = $this->_repoCalc->create($calc);
            $calc->setId($calcId);
            /* place newly created objects into the response */
            $result->setDependentPeriodData($period);
            $result->setDependentCalcData($calc);
        } else {
            /* there is depended period, place period data into response */
            $result->setDependentPeriodData($dependPeriodData);
            /* then analyze base/depended periods begin/end  */
            $dependentDsBegin = $dependPeriodData->getDstampBegin();
            $dependentDsEnd = $dependPeriodData->getDstampEnd();
            $this->_getDependedCalcForExistingPeriod(
                $result,
                $baseCalcTypeCode,
                $baseDsBegin,
                $baseDsEnd,
                $dependentCalcTypeCode,
                $dependentCalcTypeId,
                $dependentDsBegin,
                $dependentDsEnd
            );
        }
    }

    /**
     * Sub-functionality to get period and calculation data for depended calculation.
     *
     * @param \Praxigento\BonusBase\Service\Period\Response\GetForDependentCalc $result
     * @param int $basePeriodId
     * @param string $baseCalcTypeCode
     * @param string $baseDsBegin
     * @param string $baseDsEnd
     * @param string $dependentCalcTypeCode
     * @param int $dependentCalcTypeId
     * @return \Praxigento\BonusBase\Service\Period\Response\GetForDependentCalc
     */
    public function getDependedCalc(
        $result,
        $basePeriodId,
        $baseCalcTypeCode,
        $baseDsBegin,
        $baseDsEnd,
        $dependentCalcTypeCode,
        $dependentCalcTypeId
    ) {
        $baseCalcData = $this->_repoService->getLastCalcForPeriodById($basePeriodId);
        $result->setBaseCalcData($baseCalcData);
        /* get depended data for complete base calculation */
        if (
            $baseCalcData &&
            ($baseCalcData->getState() == Cfg::CALC_STATE_COMPLETE)
        ) {
            /* there is complete base calculation, get period for depended calc */
            $this->_getForCompleteBase(
                $result,
                $baseCalcTypeCode,
                $baseDsBegin,
                $baseDsEnd,
                $dependentCalcTypeCode,
                $dependentCalcTypeId
            );
        } else {
            /* there is no complete Base Calculation */
            $msg = "There is no complete base '$baseCalcTypeCode' calculation for dependent "
                . "'$dependentCalcTypeCode' calculation. New period could not be created.";
            $this->_logger->warning($msg);
        }
        return $result;
    }

}