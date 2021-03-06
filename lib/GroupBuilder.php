<?php

namespace Understory\ACF;

use StoutLogic\AcfBuilder;

/**
 * Extend AcfBuilder\GroupBuilder in order to allow FieldGroups
 * to be added as fields.
 */
class GroupBuilder extends AcfBuilder\GroupBuilder
{
    private $addFieldClass;

    public function __construct($name, $type = 'group', $config = [])
    {
        parent::__construct($name, $type, $config);
        $this->fieldsBuilder = new FieldsBuilder($name);
        $this->fieldsBuilder->setParentContext($this);
    }

    public function addFields($fields)
    {
        if ($fields instanceof FieldGroup) {
            $this->addFieldClass = get_class($fields);
            $fieldGroup = clone $fields;
            $fields = $fieldGroup->getConfig();
        }

        return parent::addFields($fields);
    }
}
