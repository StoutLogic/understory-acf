<?php

namespace Understory\ACF;

use Understory\MetaDataBinding;
use Understory\DelegatesMetaDataBinding;
use Understory\Registry;
use Understory\Registerable;

/**
 * Extend for your custom options pages
 */
abstract class CustomOptionsPage implements MetaDataBinding, Registry, Registerable
{
    private $optionsPage;
    private $registry;

    public function __construct(OptionsPage $optionsPage = null)
    {
        if (is_a($optionsPage, OptionsPage::class)) {
            $this->setOptionsPage($optionsPage);
        }

        $this->registry = [];
    }

    private function generateOptionsPage()
    {
        $reflection = new \ReflectionClass(static::class);

        // Convert class name to Options Page Title
        $title = join(preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+|[A-Z][A-Z]+)/',
            $reflection->getShortName(),
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /*don't return empty elements*/
            | PREG_SPLIT_DELIM_CAPTURE /*don't strip anything from output array*/
        ), " ");

        $title = str_replace('_', ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title);

        return new OptionsPage($title);
    }

    public function getOptionsPage()
    {
        if (!$this->optionsPage) {
            $this->setOptionsPage($this->generateOptionsPage());
        }

        return $this->optionsPage;
    }

    public function setOptionsPage(OptionsPage $optionsPage)
    {
        $this->optionsPage = $this->configure($optionsPage);
        $this->addToRegistry('optionsPage', $this->optionsPage);
    }

    protected function configure(OptionsPage $optionsPage) {
        return $optionsPage;
    }

    public function addToRegistry($key, Registerable $registerable)
    {
        $this->registry[$key] = $registerable;
    }

    public function registerItemsInRegistry()
    {
        foreach($this->registry as $registerable) {
            $registerable->register();
        }
    }

    public function register()
    {
        $this->setOptionsPage($this->generateOptionsPage());
        $this->registerItemsInRegistry();
    }

    public function __get($field)
    {
        // i.e. getField()
        if (method_exists($this, 'get'.$field)) {
            return $this->{'get'.$field}();
        }

        // private field
        echo 'isset';
        if (isset($this->$field)) {
            return $this->$field;
        }

        // OptionsPage public field
        if (isset($this->getOptionsPage()->$field)) {
            return $this->getOptionsPage()->$field;
        }

        // is a meta value
        if ($meta_value = $this->getMetaValue($field)) {
            return $this->$field = $meta_value;
        }

        //  i.e. field()
        if (method_exists($this, $field)) {
            return $this->$field = $this->$field();
        }

        return false;
    }

    protected function has($field, $value)
    {
        if ($value instanceof Registerable) {
            $this->addToRegistry($field, $value);
        }

        if ($value instanceof DelegatesMetaDataBinding) {
            $value->setMetaDataBinding($this);
        }

        $this->$field = $value;
    }

    public function __call($method, $args)
    {
        if (method_exists($this, 'get'.$method)) {
            $method = 'get'.$method;
            return $this->$method($args);
        } else if (method_exists($this->getOptionsPage(), $method)) {
            return $this->getOptionsPage()->$method($args);
        } else {
            \trigger_error('Call to undefined method '.__CLASS__.'::'.$method.'()', E_USER_ERROR);
        }
    }

    public function getMetaValue($field)
    {
        // Delegate to OptionsPage
        return $this->getOptionsPage()->getMetaValue($field);
    }

    public function setMetaValue($field, $value)
    {
        // Delegate to OptionsPage
        return $this->getOptionsPage()->setMetaValue($field, $value);
    }

    public function getBindingName()
    {
        $this->getOptionsPage()->getBindingName();
    }

}
