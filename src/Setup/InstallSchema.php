<?php
/**
 * Create DB schema.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\BonusBase\Setup;

use Praxigento\Bonus\Base\Lib\Entity\Calculation;
use Praxigento\Bonus\Base\Lib\Entity\Cfg\Generation as CfgGeneration;
use Praxigento\Bonus\Base\Lib\Entity\Compress;
use Praxigento\Bonus\Base\Lib\Entity\Level;
use Praxigento\Bonus\Base\Lib\Entity\Log\Customers as LogCustomers;
use Praxigento\Bonus\Base\Lib\Entity\Log\Opers as LogOpers;
use Praxigento\Bonus\Base\Lib\Entity\Log\Rank as LogRank;
use Praxigento\Bonus\Base\Lib\Entity\Log\Sales as LogSales;
use Praxigento\Bonus\Base\Lib\Entity\Period;
use Praxigento\Bonus\Base\Lib\Entity\Rank;
use Praxigento\Bonus\Base\Lib\Entity\Type\Calc as TypeCalc;

class InstallSchema extends \Praxigento\Core\Setup\Schema\Base
{

    protected function _setup()
    {
        /** Read and parse JSON schema. */
        $pathToFile = __DIR__ . '/../etc/dem.json';
        $pathToNode = '/dBEAR/package/Praxigento/package/Bonus/package/Base';
        $demPackage = $this->_toolDem->readDemPackage($pathToFile, $pathToNode);

        /* Type Calculation */
        $entityAlias = TypeCalc::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Type/entity/Calculation');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Period */
        $entityAlias = Period::ENTITY_NAME;
        $demEntity = $demPackage->getData('entity/Period');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Calculation */
        $entityAlias = Calculation::ENTITY_NAME;
        $demEntity = $demPackage->getData('entity/Calculation');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Compression */
        $entityAlias = Compress::ENTITY_NAME;
        $demEntity = $demPackage->getData('entity/Compression');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Level */
        $entityAlias = Level::ENTITY_NAME;
        $demEntity = $demPackage->getData('entity/Level');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Rank */
        $entityAlias = Rank::ENTITY_NAME;
        $demEntity = $demPackage->getData('entity/Rank');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Cfg Generation */
        $entityAlias = CfgGeneration::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Config/entity/Generation');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Log LogCustomers */
        $entityAlias = LogCustomers::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Log/entity/Customer');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Log Operations */
        $entityAlias = LogOpers::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Log/entity/Operation');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Log Rank */
        $entityAlias = LogRank::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Log/entity/Rank');
        $this->_toolDem->createEntity($entityAlias, $demEntity);

        /* Log Sales*/
        $entityAlias = LogSales::ENTITY_NAME;
        $demEntity = $demPackage->getData('package/Log/entity/SaleOrder');
        $this->_toolDem->createEntity($entityAlias, $demEntity);
    }
}