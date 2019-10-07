<?php

use jtl\Connector\Gambio\Mapper\PrimaryKeyMapper;

$loader = require 'vendor/autoload.php';

const TEST_DIR = __DIR__;

function getPrimaryKeyMapper()
{
    return new PrimaryKeyMapper();
}
