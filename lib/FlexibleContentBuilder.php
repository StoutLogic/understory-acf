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
    /**
     * @var array[string]
     */
    private $layoutRegistry;

    public function addLayout($layout, $args = [])
    {
        if ($layout instanceof FieldGroup) {
            $fieldGroup = $layout;
            $layout = clone $fieldGroup->getConfig();

            $layoutName = get_class($fieldGroup);
            if ($fieldGroup instanceof FlexibleContentLayout) {
                $layoutName = $fieldGroup->getLayoutName();
                $this->registerLayout($layoutName, get_class($fieldGroup));
            }

            return parent::addLayout($layout, $args)
                ->setGroupConfig('name', urlencode($layoutName));
        }

        // layout needs to be FieldsBuilder
        return parent::addLayout($layout, $args);
    }

    /**
     * @param string $layoutName
     * @param string $className
     */
    public function registerLayout($layoutName, $className)
    {
        $this->layoutRegistry[$layoutName] = $className;
    }

    /**
     * @param string $layoutName
     * @return bool
     */
    public function layoutExists($layoutName)
    {
        return isset($this->layoutRegistry[$layoutName]);
    }

    /**
     * @param string $layoutName
     * @return string CLas Name
     */
    public function getLayoutClass($layoutName)
    {
        return $this->layoutRegistry[$layoutName];
    }
}
