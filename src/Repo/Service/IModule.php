<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusBase\Repo\Service;


/**
 * Common repo service for BonusBase module.
 */
interface IModule
{
    /**
     * Get the last period data by calculation type.
     *
     * @param int $calcTypeId
     * @return \Praxigento\BonusBase\Data\Entity\Period|null
     */
    public function getLastPeriodByCalcType($calcTypeId);

    /**
     * Get the last calculation for period.
     *
     * @param int $periodId
     * @return \Praxigento\BonusBase\Data\Entity\Calculation|null
     */
    public function getLastCalcForPeriod($periodId);


    /**
     * Set complete state for given calculation and save 'date_ended' as current UTC date.
     *
     * @param int $calcId
     * @return null
     */
    public function markCalcComplete($calcId);
}