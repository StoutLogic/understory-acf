<?php

namespace Understory\ACF;

use StoutLogic\AcfBuilder;

/**
 * Extend AcfBuilder\FlexibleContentBuilder in order to allow FieldGroups
 * to be added as layouts. The layout's name will be the FieldGroup's class
 * name so it can be instantiated automatically.
 */
class FlexibleContentBuilder extends AcfBuilder\FlexibleContentBuilder
{
    public function addLayout($layout, $args = [])
    {
        if ($layout instanceof FieldGroup) {
            $fieldGroup = $layout;
            $layout = clone $fieldGroup->getConfig();
            $layout->setGroupConfig('name', get_class($fieldGroup));
        }
        // layout needs to be FieldsBuilder
        return parent::addLayout($layout, $args);
    }
}
