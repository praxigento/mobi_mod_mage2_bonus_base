<?php
/**
 * Create DB schema.
 * User: Alex Gusev <alex@flancer64.com>
 */
namespace Praxigento\Bonus\Base\Setup;

class InstallData extends \Praxigento\Core\Setup\Data\Base {

    public function __construct() {
        parent::__construct('Praxigento\Bonus\Base\Lib\Setup\Data');
    }
}