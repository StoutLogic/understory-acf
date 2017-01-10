<?php

namespace Understory\ACF;

use StoutLogic\AcfBuilder;

/**
 * Extend AcfBuilder\FieldsBuilder in order to utilize our customized
 * FlexibleContentBuilder class.
 */
class FieldsBuilder extends AcfBuilder\FieldsBuilder
{
    public function addFlexibleContent($name, array $args = [])
    {
        return $this->initializeField(new FlexibleContentBuilder($name, 'flexible_content', $args));
    }

    public function addRepeater($name, array $args = [])
    {
        return $this->initializeField(new RepeaterBuilder($name, 'repeater', $args));
    }

    /**
     * Add multiple fields either via an array or from another builder
     * @param FieldBuilder|FieldGroup $fields
     * @return $this
     */
    public function addFields($fields)
    {
        if ($fields instanceof FieldGroup) {
            $fieldGroup = clone $fields;
            $fields = $fieldGroup->getConfig();
        }

        return parent::addFields($fields);
    }
}
