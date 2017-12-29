<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
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
