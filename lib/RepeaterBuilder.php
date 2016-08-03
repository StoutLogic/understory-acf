<?php

namespace Understory\ACF;

use StoutLogic\AcfBuilder;

/**
 * Extend AcfBuilder\FlexibleContentBuilder in order to allow FieldGroups
 * to be added as layouts. The layout's name will be the FieldGroup's class
 * name so it can be instantiated automatically.
 */
class RepeaterBuilder extends AcfBuilder\RepeaterBuilder
{
    public function __construct($name, $type = 'repeater', $config = [])
    {
        parent::__construct($name, $type, $config);
        $this->fieldsBuilder = new FieldsBuilder($name);
        $this->fieldsBuilder->setParentContext($this);
    }
}
