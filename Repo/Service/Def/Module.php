<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusBase\Repo\Service\Def;

use Praxigento\Accounting\Repo\Data\Account as EAccount;
use Praxigento\Accounting\Repo\Data\Transaction as ETransaction;
use Praxigento\Accounting\Repo\Data\Type\Asset as ETypeAsset;
use Praxigento\BonusBase\Config as Cfg;
use Praxigento\BonusBase\Repo\Data\Calculation as ECalculation;
use Praxigento\BonusBase\Repo\Data\Period as EPeriod;

/**
 * @deprecated this class should be transformed into set of Query/Repo classes/methods.
 */
class Module
    extends \Praxigento\Core\App\Repo\Db
    implements \Praxigento\BonusBase\Repo\Service\IModule
{
    /** @var \Praxigento\Core\Api\Helper\Date */
    protected $hlpDate;
    /** @var \Praxigento\BonusBase\Repo\Dao\Calculation */
    protected $daoCalc;
    /** @var \Praxigento\BonusBase\Repo\Dao\Period */
    protected $daoPeriod;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\BonusBase\Repo\Dao\Calculation $daoCalc,
        \Praxigento\BonusBase\Repo\Dao\Period $daoPeriod,
        \Praxigento\Core\Api\Helper\Date $hlpDate
    ) {
        parent::__construct($resource);
        $this->daoCalc = $daoCalc;
        $this->daoPeriod = $daoPeriod;
        $this->hlpDate = $hlpDate;
    }

    public function getFirstDateForPvTransactions()
    {
        $asAcc = 'paa';
        $asTrans = 'pat';
        $asType = 'pata';
        $tblAcc = $this->resource->getTableName(EAccount::ENTITY_NAME);
        $tblTrans = $this->resource->getTableName(ETransaction::ENTITY_NAME);
        $tblType = $this->resource->getTableName(ETypeAsset::ENTITY_NAME);
        // SELECT FROM prxgt_acc_transaction pat
        $query = $this->conn->select();
        $query->from([$asTrans => $tblTrans], [ETransaction::A_DATE_APPLIED]);
        // LEFT JOIN prxgt_acc_account paa ON paa.id = pat.debit_acc_id
        $on = $asAcc . '.' . EAccount::A_ID . '=' . $asTrans . '.' . ETransaction::A_DEBIT_ACC_ID;
        $query->joinLeft([$asAcc => $tblAcc], $on, null);
        // LEFT JOIN prxgt_acc_type_asset pata ON paa.asset_type_id = pata.id
        $on = $asAcc . '.' . EAccount::A_ASSET_TYPE_ID . '=' . $asType . '.' . ETypeAsset::A_ID;
        $query->joinLeft([$asType => $tblType], $on, null);
        // WHERE
        $where = $asType . '.' . ETypeAsset::A_CODE . '=' . $this->conn->quote(Cfg::CODE_TYPE_ASSET_PV);
        $query->where($where);
        // ORDER & LIMIT
        $query->order($asTrans . '.' . ETransaction::A_DATE_APPLIED . ' ASC');
        $query->limit(1);
        //
        $result = $this->conn->fetchOne($query);
        return $result;
    }

    public function getLastCalcForPeriodByDates($calcTypeId, $dsBegin, $dsEnd)
    {
        $result = null;
        $conn = $this->conn;
        $asPeriod = 'pbbp';
        $asCalc = 'pbbc';
        $tblPeriod = $this->resource->getTableName(EPeriod::ENTITY_NAME);
        $tblCalc = $this->resource->getTableName(ECalculation::ENTITY_NAME);
        // SELECT FROM prxgt_bon_base_period pbbp
        $query = $conn->select();
        $query->from([$asPeriod => $tblPeriod], []);
        // LEFT JOIN prxgt_bon_base_calc pbbc ON pbbp.id = pbbc.period_id
        $on = $asPeriod . '.' . EPeriod::A_ID . '=' . $asCalc . '.' . ECalculation::A_PERIOD_ID;
        $cols = '*';
        $query->joinLeft([$asCalc => $tblCalc], $on, $cols);
        // ORDER
        $query->order(ECalculation::A_ID . ' DESC');
        // LIMIT
        $query->limit(1);
        // WHERE
        $whereTypeId = $asPeriod . '.' . EPeriod::A_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $whereFrom = $asPeriod . '.' . EPeriod::A_DSTAMP_BEGIN . '=' . $conn->quote($dsBegin);
        $whereTo = $asPeriod . '.' . EPeriod::A_DSTAMP_END . '=' . $conn->quote($dsEnd);
        $query->where("$whereTypeId AND $whereFrom AND $whereTo");
        //
        $rs = $conn->fetchAll($query);
        if (is_array($rs)) {
            $data = reset($rs);
            $result = new ECalculation($data);
        }
        return $result;
    }

    public function getLastCalcForPeriodById($periodId)
    {
        $result = null;
        $where = ECalculation::A_PERIOD_ID . '=' . (int)$periodId;
        $limit = 1;
        $order = [ECalculation::A_ID . ' ASC'];
        $rs = $this->daoCalc->get($where, $order, $limit);
        if (is_array($rs) && count($rs)) {
            $data = reset($rs);
            $result = $data;
        }
        return $result;
    }

    public function getLastPeriodByCalcType($calcTypeId)
    {
        $result = null;
        /* set WHERE and ORDER BY clauses */
        $where = EPeriod::A_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $order = [EPeriod::A_DSTAMP_BEGIN . ' DESC'];
        /* get one only period with the biggest begin date stamp */
        $rs = $this->daoPeriod->get($where, $order, 1);
        if (is_array($rs) && count($rs)) {
            $data = reset($rs);
            $result = $data;
        }
        return $result;
    }

}