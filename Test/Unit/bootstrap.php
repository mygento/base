<?php

/**
 * @author Mygento Team
 * @copyright 2014-2018 Mygento (https://www.mygento.ru)
 * @package Mygento_Base
 */

namespace Mygento\Base\Test\Unit;

// @codingStandardsIgnoreStart
chdir(__DIR__);
$autoloadFilePath = './../../vendor/autoload.php';

//To run tests inside / outside Magento
require_once file_exists($autoloadFilePath)
    ? $autoloadFilePath
    : './../../../../autoload.php';

ini_set('display_errors', 1);
// @codingStandardsIgnoreEnd
