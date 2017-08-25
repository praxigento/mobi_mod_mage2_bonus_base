<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusBase\Service\Period\Calc\Get;

/**
 * Get period for first calculation in the chain.
 *
 * This service registers new calculation if it is possible.
 *
 * TODO: move under "\Praxigento\BonusBase\Service\Period\Calc\Get" namespace.
 */
interface IBasis
    extends \Praxigento\Core\Service\IProcess
{
    const CTX_IN_ASSET_TYPE_CODE = 'inAssetTypeCode';
    const CTX_IN_CALC_CODE = 'inCalcCode';
    const CTX_IN_PERIOD_TYPE = 'inPeriodType';
    const CTX_OUT_CALC_ID = 'outCalcId';
    const CTX_OUT_ERROR_CODE = 'outErrCode';
    const CTX_OUT_PERIOD_ID = 'outPeriodId';

    /**
     * Error codes for self::CTX_OUT_ERROR_CODE
     */
    const ERR_CALC_NOT_COMPLETE = 'errCalcNotComplete';
    const ERR_NO_TRANS_YET = 'errNoTransYet';

}