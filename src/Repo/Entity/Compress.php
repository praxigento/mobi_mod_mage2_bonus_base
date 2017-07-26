<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\BonusBase\Repo\Entity;

use Praxigento\BonusBase\Data\Entity\Compress as Entity;

class Compress
    extends \Praxigento\Core\Repo\Def\Entity
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\Core\Repo\IGeneric $repoGeneric
    ) {
        parent::__construct($resource, $repoGeneric, Entity::class);
    }

    /**
     * Get compressed tree for given calculation.
     *
     * @TODO: remove usage of this method or change call in callers to expect return result collection of Entities.
     *
     * @deprecated since MOBI-803
     * @param integer $calcId
     * @return array raw data from DB
      */
    public function getTreeByCalcId($calcId)
    {
        $where = Entity::ATTR_CALC_ID . '=' . (int)$calcId;
        $entities = $this->get($where);
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entity->get();
        }
        return $result;
    }
}