<?php

namespace MP\Fixtures\App;

trait Mage
{
    /**
     * Get initialized application object
     * Path from .travis/sauce
     */
    protected function initMage()
    {
        require_once realpath(__DIR__ . "/../../../../../../../app/Mage.php");
        \Mage::app('default');
    }
}