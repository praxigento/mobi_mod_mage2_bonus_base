<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusBase\Service\Period\Response;


use Praxigento\BonusBase\Repo\Data\Calculation as ECalculation;
use Praxigento\BonusBase\Repo\Data\Period as EPeriod;

/**
 * @method \Praxigento\BonusBase\Repo\Data\Period getPeriod()
 * @method \Praxigento\BonusBase\Repo\Data\Calculation getCalculation()
 */
class AddCalc
    extends \Praxigento\Core\App\Service\Response
{

    public function setCalculation($data)
    {
        if ($data instanceof ECalculation) {
            parent::setCalculation($data);
        } else {
            $dataObj = new ECalculation($data);
            parent::setCalculation($dataObj);
        }
    }

    public function setPeriod($data)
    {
        if ($data instanceof EPeriod) {
            parent::setPeriod($data);
        } else {
            $dataObject = new EPeriod($data);
            parent::setPeriod($dataObject);
        }
    }
}