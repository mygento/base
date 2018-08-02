<?php

/**
 * @author Mygento Team
 * @copyright 2016-2018 Mygento (https://www.mygento.ru)
 * @package Mygento_Base
 */

namespace Mygento\Base\Model\Source;

class Attributes extends AbstractAttributes
{
    protected $filterTypesNotEqual = [
        'hidden',
        'multiselect',
        'boolean',
        'date',
        'image',
        'price'
    ];
}
