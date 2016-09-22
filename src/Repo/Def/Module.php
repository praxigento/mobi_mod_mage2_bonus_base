<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusBase\Repo\Def;

use Flancer32\Lib\DataObject;
use Praxigento\Accounting\Data\Entity\Account;
use Praxigento\Accounting\Data\Entity\Transaction;
use Praxigento\Accounting\Data\Entity\Type\Asset as TypeAsset;
use Praxigento\BonusBase\Config as Cfg;
use Praxigento\BonusBase\Data\Entity\Calculation;
use Praxigento\BonusBase\Data\Entity\Cfg\Generation as CfgGeneration;
use Praxigento\BonusBase\Data\Entity\Compress;
use Praxigento\BonusBase\Data\Entity\Period;
use Praxigento\BonusBase\Data\Entity\Rank;
use Praxigento\BonusBase\Data\Entity\Type\Calc as TypeCalc;
use Praxigento\BonusBase\Repo\IModule;
use Praxigento\Core\Data\Entity\Type\Base as TypeBase;
use Praxigento\Core\Repo\Def\Db;
use Praxigento\Downline\Data\Entity\Snap;

class Module extends Db implements IModule
{
    /** @var \Praxigento\Core\Tool\IDate */
    protected $_toolDate;
    /** @var \Praxigento\Core\Repo\IGeneric */
    protected $_repoBasic;
    /** @var  \Praxigento\Core\Transaction\Database\IManager */
    protected $_manTrans;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\Core\Transaction\Database\IManager $manTrans,
        \Praxigento\Core\Repo\IGeneric $repoBasic,
        \Praxigento\Core\Tool\IDate $toolDate
    ) {
        parent::__construct($resource);
        $this->_manTrans = $manTrans;
        $this->_repoBasic = $repoBasic;
        $this->_toolDate = $toolDate;
    }

    /**
     * SELECT
     * pbbc.*
     * FROM prxgt_bon_base_period pbbp
     * LEFT JOIN prxgt_bon_base_calc pbbc
     * ON pbbp.id = pbbc.period_id
     * WHERE pbbp.calc_type_id = 16
     * AND pbbp.dstamp_begin = '20160101'
     * AND pbbp.dstamp_end = '20160131'
     *
     * @param int $calcTypeId
     * @param string $dsBegin 'YYYYMMDD'
     * @param string $dsEnd 'YYYYMMDD'
     * @param bool $shouldGetLatestCalc
     */
    public function getCalcsForPeriod($calcTypeId, $dsBegin, $dsEnd, $shouldGetLatestCalc = false)
    {
        $conn = $this->_conn;
        $asPeriod = 'pbbp';
        $asCalc = 'pbbc';
        $tblPeriod = $this->_resource->getTableName(Period::ENTITY_NAME);
        $tblCalc = $this->_resource->getTableName(Calculation::ENTITY_NAME);
        // SELECT FROM prxgt_bon_base_period pbbp
        $query = $conn->select();
        $query->from([$asPeriod => $tblPeriod], []);
        // LEFT JOIN prxgt_bon_base_calc pbbc ON pbbp.id = pbbc.period_id
        $on = $asPeriod . '.' . Period::ATTR_ID . '=' . $asCalc . '.' . Calculation::ATTR_PERIOD_ID;
        $cols = '*';
        $query->joinLeft([$asCalc => $tblCalc], $on, $cols);
        if ($shouldGetLatestCalc) {
            // LIMIT
            $query->limit(1);
        }
        // WHERE
        $whereTypeId = $asPeriod . '.' . Period::ATTR_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $whereFrom = $asPeriod . '.' . Period::ATTR_DSTAMP_BEGIN . '=' . $conn->quote($dsBegin);
        $whereTo = $asPeriod . '.' . Period::ATTR_DSTAMP_END . '=' . $conn->quote($dsEnd);
        $query->where("$whereTypeId AND $whereFrom AND $whereTo");
        // $sql = (string)$query;
        $result = $conn->fetchAll($query);
        if ($shouldGetLatestCalc && is_array($result)) {
            $result = reset($result);
        }
        return $result;
    }

    public function getConfigGenerationsPercents($calcTypeId)
    {
        $result = [];
        $where = CfgGeneration::ATTR_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $rows = $this->_repoBasic->getEntities(CfgGeneration::ENTITY_NAME, null, $where);
        foreach ($rows as $row) {
            $rankId = $row[CfgGeneration::ATTR_RANK_ID];
            $gen = $row[CfgGeneration::ATTR_GENERATION];
            $percent = $row[CfgGeneration::ATTR_PERCENT];
            $result[$rankId][$gen] = $percent;
        }
        return $result;
    }

    /**
     * Return timestamp for the first transaction related to PV.
     */
    public function getFirstDateForPvTransactions()
    {
        $asAcc = 'paa';
        $asTrans = 'pat';
        $asType = 'pata';
        $tblAcc = $this->_resource->getTableName(Account::ENTITY_NAME);
        $tblTrans = $this->_resource->getTableName(Transaction::ENTITY_NAME);
        $tblType = $this->_resource->getTableName(TypeAsset::ENTITY_NAME);
        // SELECT FROM prxgt_acc_transaction pat
        $query = $this->_conn->select();
        $query->from([$asTrans => $tblTrans], [Transaction::ATTR_DATE_APPLIED]);
        // LEFT JOIN prxgt_acc_account paa ON paa.id = pat.debit_acc_id
        $on = $asAcc . '.' . Account::ATTR_ID . '=' . $asTrans . '.' . Transaction::ATTR_DEBIT_ACC_ID;
        $query->joinLeft([$asAcc => $tblAcc], $on, null);
        // LEFT JOIN prxgt_acc_type_asset pata ON paa.asset_type_id = pata.id
        $on = $asAcc . '.' . Account::ATTR_ASSET_TYPE_ID . '=' . $asType . '.' . TypeAsset::ATTR_ID;
        $query->joinLeft([$asType => $tblType], $on, null);
        // WHERE
        $where = $asType . '.' . TypeAsset::ATTR_CODE . '=' . $this->_conn->quote(Cfg::CODE_TYPE_ASSET_PV);
        $query->where($where);
        // ORDER & LIMIT
        $query->order($asTrans . '.' . Transaction::ATTR_DATE_APPLIED . ' ASC');
        $query->limit(1);
        // $sql = (string)$query;
        $result = $this->_conn->fetchOne($query);
        return $result;
    }

    public function getLatestPeriod($calcTypeId, $shouldGetLatestCalc = true, $shouldGetAllCalcs = false)
    {
        $result = new DataObject();
        /* set WHERE and ORDER BY clauses */
        $wherePeriod = Period::ATTR_CALC_TYPE_ID . '=' . (int)$calcTypeId;
        $orderPeriod = [Period::ATTR_DSTAMP_BEGIN . ' DESC'];
        /* get one only period with the biggest begin date stamp */
        $periodData = $this->_repoBasic->getEntities(Period::ENTITY_NAME, null, $wherePeriod, $orderPeriod, 1);
        if (is_array($periodData) && (count($periodData) > 0)) {
            /* get first (and only) item from result set */
            $periodData = reset($periodData);
            if ($periodData !== false) {
                $result->setData(self::A_PERIOD, $periodData);
                if ($shouldGetAllCalcs || $shouldGetLatestCalc) {
                    /* add period calculations to result set */
                    $where = Calculation::ATTR_PERIOD_ID . '=' . $periodData[Period::ATTR_ID];
                    $limit = ($shouldGetLatestCalc) ? 1 : null;
                    $order = [Calculation::ATTR_ID . ' ASC'];
                    $calcData = $this->_repoBasic->getEntities(Calculation::ENTITY_NAME, null, $where, $order,
                        $limit);
                    if (is_array($calcData) && (count($calcData) > 0)) {
                        if ($shouldGetLatestCalc) {
                            $calcData = reset($calcData);
                        }
                        $result->setData(self::A_CALC, $calcData);
                    }
                }
            }
        }
        return $result;
    }

    public function getRankIdByCode($calcTypeCode)
    {
        $tbl = $this->_resource->getTableName(Rank::ENTITY_NAME);
        $query = $this->_conn->select();
        $query->from($tbl);
        $query->where(TypeBase::ATTR_CODE . '=:code');
        // $sql = (string)$query;
        $data = $this->_conn->fetchRow($query, ['code' => $calcTypeCode]);
        $result = isset($data[TypeBase::ATTR_ID]) ? $data[TypeBase::ATTR_ID] : null;
        return $result;
    }

    public function getTypeAssetIdByCode($assetTypeCode)
    {
        $tbl = $this->_resource->getTableName(TypeAsset::ENTITY_NAME);
        /** @var  $query \Zend_Db_Select */
        $query = $this->_conn->select();
        $query->from($tbl);
        $query->where(TypeBase::ATTR_CODE . '=:code');
        // $sql = (string)$query;
        $data = $this->_conn->fetchRow($query, ['code' => $assetTypeCode]);
        $result = isset($data[TypeBase::ATTR_ID]) ? $data[TypeBase::ATTR_ID] : null;
        return $result;
    }

    public function getTypeCalcIdByCode($calcTypeCode)
    {
        $tbl = $this->_resource->getTableName(TypeCalc::ENTITY_NAME);
        /** @var  $query \Zend_Db_Select */
        $query = $this->_conn->select();
        $query->from($tbl);
        $query->where(TypeBase::ATTR_CODE . '=:code');
        // $sql = (string)$query;
        $data = $this->_conn->fetchRow($query, ['code' => $calcTypeCode]);
        $result = isset($data[TypeBase::ATTR_ID]) ? $data[TypeBase::ATTR_ID] : null;
        return $result;
    }

    /**
     * Save compressed tree.
     *
     * @param $calcId
     * @param $tree
     */
    public function saveCompressedTree($calcId, $tree)
    {
        $def = $this->_manTrans->begin();
        try {
            foreach ($tree as $item) {
                $bind = [
                    Compress::ATTR_CALC_ID => $calcId,
                    Compress::ATTR_CUSTOMER_ID => $item[Snap::ATTR_CUSTOMER_ID],
                    Compress::ATTR_PARENT_ID => $item[Snap::ATTR_PARENT_ID]
                ];
                $this->_repoBasic->addEntity(Compress::ENTITY_NAME, $bind);
            }
            $this->_manTrans->commit($def);
        } finally {
            $this->_manTrans->end($def);
        }
    }

}