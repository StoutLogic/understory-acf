<?php


namespace Understory\ACF;

use Understory\DelegatesMetaDataBinding;
use Understory\MetaDataBinding;
use Understory\Registerable;
use Understory\Sequential;
use Understory;

require('PatchACFToUseTermMeta.php');

abstract class FieldGroup implements DelegatesMetaDataBinding, Registerable, Sequential
{
    use Understory\Core;

    /**
     * MetaDataBinding of the Object that has this field.
     * This can be a CustomPostType, CustomTaxonomy, Options Page, or View
     * or it can be an object that implements FieldGroupInterface.
     *
     * @var mixed
     */
    private $metaDataBinding;

    /**
     * Parent Field Group that gets passed to the constructor. We will use this
     * to generate the fully qualified namespace for meta field keys.
     *
     * @var FieldGroup
     */
    private $parentFieldGroup;

    /**
     * If this field is part of a repeater or flexible content field, this is the prefix
     * of the meta key in the database meta tables, for a particular row for a particular field.
     *
     * @var string
     */
    private $metaValueNamespace = '';

    private $namespace = '';

    /**
     * Cached array of Custom Field Groups in a repeater.
     *
     * @var array
     */
    private $repeaterRows = [];

    protected $config = null;
    /**
     * Pass in the metaDataBinding of the Object that has this field.
     *
     * @param mixed  $binding              gets passed to registerRule
     * @param string $metaValueNamespace if part of a repeater pass in the prefix to retrive
     *                                   the meta data from the database for that row
     */
    public function __construct(MetaDataBinding $binding = null, $metaValueNamespace = '')
    {
        $this->setMetaValueNamespace($metaValueNamespace);
        if (is_a($binding, self::class)) {
            $this->setParentFieldGroup($binding);
            // Get metaDataBinding from ParentFieldGroup
            $this->setMetaDataBinding($this->getParentFieldGroup()->getMetaDataBinding());
            if (is_null($metaValueNamespace)) {
                $this->setMetaValueNamespace('');
            }
        } else if ($binding) {
            $this->setMetaDataBinding($binding);
        }

    }

    /**
     * Initalize a FieldsBuilder with the class's name as the group name
     * @return FieldsBuilder
     */
    private function initializeBuilder()
    {
        return new FieldsBuilder($this->getBindingName());
    }

    /**
     * Returns the AcfBuilder object. If config isn't set, set it to the
     * defaultConfig
     * @return FieldsBuilder
     */
    public function getConfig()
    {
        if (!$this->config) {
            // Retrive the default config. Create a new builder and pass to
            // configure method.
            $config = $this->configure($this->initializeBuilder());
            $config = $config->getRootContext();
            $this->setConfig($config);
        }

        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setGroupConfig($key, $value)
    {
        $this->getConfig()->setGroupConfig($key, $value);
        return $this;
    }

    public function hideOnScreen($value)
    {
        $hide = $this->getConfig()->getGroupConfig('hide_on_screen') ?: [];
        $hide[] = $value;

        $this->getConfig()->setGroupConfig('hide_on_screen', $hide);
        return $this;
    }

    public function hideContentEditor()
    {
        $this->hideOnScreen('the_content');
        return $this;
    }

    /**
     * @param  FieldsBuilder $builder to configure
     * @return FieldsBuilder
     */
    protected function configure(FieldsBuilder $builder)
    {
        return $builder;
    }

    /**
     * Call and pass in the object's metaDataBinding, usually a class or instance, also
     * allows you to pass in any custom ACF field group configuartion that will
     * get merged into the field groups default Configuration.
     *
     * @param mixed $metaDataBinding        Object that will have this field, or ACF rule Array
     * @return $this  to chain together multiple locations or a register method call
     */
    private function setLocationForMetaDataBinding($metaDataBinding)
    {
        if ($metaDataBinding instanceof Understory\View) {
            $this->setViewLocation($metaDataBinding);
        } elseif ($metaDataBinding instanceof Understory\CustomPostType) {
            $this->setCustomPostTypeLocation($metaDataBinding);
        } elseif ($metaDataBinding instanceof Understory\CustomTaxonomy) {
            $this->setCustomTaxonomyLocation($metaDataBinding);
        } elseif ($metaDataBinding instanceof CustomOptionsPage) {
            $this->setOptionsPageLocation($metaDataBinding);
        } elseif ($metaDataBinding instanceof Understory\User) {
            $this->setUserFormLocation($metaDataBinding);
        }
        return $this;
    }

    /**
     * Sets the location of the FieldGroup.
     * If location data already exists on the builder, the new condition will
     * be added as and `and` condition.
     * @param string $param
     * @param string $operator
     * @param string $value
     * @return \StoutLogic\AcfBuilder\LocationBuilder
     */
    public function setLocation($param, $operator, $value)
    {
        $builder = $this->getConfig();

        if (null === $builder->getLocation()) {
            return $builder->setLocation($param, $operator, $value);
        }

        return $builder->getLocation()->and($param, $operator, $value);
    }

    private function setViewLocation(Understory\View $metaDataBinding)
    {
        $fileName = $metaDataBinding->getFileName();
        $viewFile = 'app/Views'.$fileName.'.php';
        $this->namespaceFieldGroupKey('view_' . str_replace('/', '', $fileName));

        // Check to see if this is the default template
        if (str_replace('/', '', strtolower($fileName)) === 'page') {
            $viewFile = 'default';
        }

        $this->setLocation('page_template', '==', $viewFile);
    }

    private function setCustomPostTypeLocation(Understory\CustomPostType $metaDataBinding)
    {
        $postType = $metaDataBinding->getPostType();
        $this->namespaceFieldGroupKey('post_type_' . $postType);

        $this->setLocation('post_type', '==', $postType);
    }

    private function setCustomTaxonomyLocation(Understory\CustomTaxonomy $metaDataBinding)
    {
        $taxonomy = $metaDataBinding->getTaxonomy();
        $this->namespaceFieldGroupKey('taxonomy_' . $taxonomy);

        $this->setLocation('taxonomy', '==', $taxonomy);
    }

    private function setOptionsPageLocation(Understory\ACF\CustomOptionsPage $metaDataBinding)
    {
        $this->namespaceFieldGroupKey('options_' . $metaDataBinding->getId());
        $this->setLocation('options_page', '==', $metaDataBinding->getId());
    }

    private function setUserFormLocation(Understory\User $metaDataBinding)
    {
        $this->namespaceFieldGroupKey('user');
        $this->setLocation('user_form', '==', 'all');
    }

    private function namespaceFieldGroupKey($append)
    {
        $builder = $this->getConfig();
        $builder->setGroupConfig(
            'key',
            $builder->getGroupConfig('key') . '_' . $append
        );
    }

    /**
     * Sets the Field Group's Menu Order.
     * @param $position
     * @param MetaDataBinding $metaDataBinding
     */
    public function setSequentialPosition($position, MetaDataBinding $metaDataBinding)
    {
        $this->getConfig()->setGroupConfig('menu_order', $position);
    }

    /**
     * Register a fieldGroup with set locations.
     *
     * @param object $metaDataBinding     Shortcut which will call setLocation with
     *                          this metaDataBinding.
     * @return array $config    The final ACF config array
     */
    public function register($metaDataBinding = null)
    {
        if (!$metaDataBinding) {
            $metaDataBinding = $this->getMetaDataBinding();
        }
        $this->setLocationForMetaDataBinding($metaDataBinding);

        // Namespace the config keys
        $config = $this->getConfig()->build();

        // Optimization:
        // Don't register a field group that already exists
        // if (!acf_is_local_field_group($config['key']))
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group($config);
        }

        return $config;
    }

    /**
     * Create and retreive a value of our field as part of a repeater.
     * We will create an intermediary FieldGroup that is properly namespaced.
     *
     * @param string $metaFieldKey field name
     * @param int    $index
     *
     * @return FieldGroup
     */
    private function getRepeaterRow($metaFieldKey, $index)
    {
        if (!isset($this->repeaterRows[$index])) {
            // Determine Namespace
            $namespace = $metaFieldKey.'_'.$index;

            // Create a new instance of our current FieldGroup sub class

            $fieldGroupClass = $this->getRepeaterClass($metaFieldKey);
            $this->repeaterRows[$index] = new $fieldGroupClass($this,  $namespace);
        }

        return $this->repeaterRows[$index];
    }

    private function getRepeaterClass($metaFieldKey)
    {
        if ($this->fieldExists($metaFieldKey)) {
            $field = $this->getField($metaFieldKey);
            if ($field instanceof RepeaterBuilder && $field->getRepeaterFieldsClass()) {
                return $field->getRepeaterFieldsClass();
            }
        }

        return get_called_class();
    }

    /**
     * Calls getMetaValue on the metaDataBinding, that is properly namespaced so that it
     * works with repeaters and flexible post types.
     *
     * @param string         $metaFieldKey
     * @param int (optional) $index
     *
     * @return mixed Meta Value or FieldGroup
     */
    public function getMetaValue($metaFieldKey, $index = null)
    {
        if ($index === null && $cachedMetaValue = $this->getCachedMetaValue($metaFieldKey)) {
            return $cachedMetaValue;
        }

        if (isset($index)) {
            return $this->getRepeaterRow($metaFieldKey, $index);
        }

        $namespacedMetaFieldKey = $this->getNamespacedMetaFieldKey($metaFieldKey);

        return $this->getMetaDataBinding()->getMetaValue($namespacedMetaFieldKey);
    }

    /**
     * Timber caches all meta data on the object as a public property. The property name
     * is the same as the Namespaced Meta Field Key. This will save a database lookup.
     *
     * If the field is a Repeater, then return using getMetaValues, and pass in the Repeater Field's
     * class, that was used in the `addFields` method call during configuration. If one wasn't used, null
     * will be passed, resulting in the current FieldGroup's class being used to initialize the repeater
     * items.
     * @param string $metaFieldKey
     * @return mixed
     */
    public function getCachedMetaValue($metaFieldKey)
    {
        // If a repeater or flexible content, get instance
        if ($this->fieldExists($metaFieldKey)) {
            $field = $this->getField($metaFieldKey);
            if ($field instanceof RepeaterBuilder) {
                return $this->getMetaValues($field->getName(), $field->getRepeaterFieldsClass());
            }
            if ($field instanceof FlexibleContentBuilder) {
                return $this->getMetaValues($field->getName());
            }
        }


        // Otherwise use the value Timber probably has cached
        $fieldKey = $this->getNamespacedMetaFieldKey($metaFieldKey);

        if (isset($this->getMetaDataBinding()->$fieldKey)) {
            return $this->getMetaDataBinding()->$fieldKey;
        }

        return false;
    }

    public function setMetaValue($metaFieldKey, $value)
    {
        $namespacedMetaFieldKey = $this->getNamespacedMetaFieldKey($metaFieldKey);
        $this->getMetaDataBinding()->setMetaValue($namespacedMetaFieldKey, $value);
    }

    /**
     * The meta shortcut function is an alias of getMetaValue.
     *
     * It is important to note that the meta function is used by Timber and
     * Understory when attempting to do a method_missing __get lookup.
     * This allows one to simply call $this->name from php or a twig file instead
     * of defining a getName function to manually return the metaValue.
     *
     * A getter is still required if the value needs any post processing
     *
     * @param string $metaFieldKey
     * @param int  $index        optional
     *
     * @return mixed Meta Value or FieldGroup
     */
    public function meta($metaFieldKey, $index = null)
    {
        return $this->getMetaValue($metaFieldKey, $index);
    }

    /**
     * Memoized values returned from getMetaValues.
     *
     * @var array
     */
    private $metaValues = [];

    /**
     * Return an array of meta values, contained in a repeater field
     * or flexible content field.
     * Optionally instatiate each value as a FieldGroup subclass if a
     * repeater field
     *
     * @param string           $metaFieldKey
     * @param FieldGroup $className    (optional) class to instatiate value as
     *
     * @return array
     */
    public function getMetaValues($metaFieldKey, $className = null)
    {
        if (!array_key_exists($metaFieldKey, $this->metaValues)) {
            $this->metaValues[$metaFieldKey] = [];

            $value = $this->getMetaValue($metaFieldKey);

            // Check to see if is a repeater or flexible content field
            if (is_numeric($value)) {
                // Repeater
                $count = $value;
            } else {
                // Flexible Content
                $classNames = $value;
                if (!is_array($value)) {
                    $classNames = unserialize($value);
                }
                $count = count($classNames);
            }

            for ($i = 0; $i < $count; $i++) {
                if (isset($classNames) && is_array($classNames)) {
                    $className = $classNames[$i];
                }
                $className = urldecode($className);

                if ($this->fieldExists($metaFieldKey)) {
                    $field = $this->getField($metaFieldKey);
                    if ($field instanceof FlexibleContentBuilder && $field->layoutExists($className)) {
                        $className = $field->getLayoutClass($className);
                    }
                }

                if (class_exists($className)) {
                    $this->metaValues[$metaFieldKey][] = new $className($this, $metaFieldKey.'_'.$i);
                } else {
                    $this->metaValues[$metaFieldKey][] = $this->getMetaValue($metaFieldKey, $i);
                }
            }
        }

        return $this->metaValues[$metaFieldKey];
    }

    /**
     * Recursively determine our Parent Field Group's metaValueNamespace.
     *
     * @return string parent meta value namespace
     */
    protected function getParentMetaValueNamespace()
    {
        if ($this->getParentFieldGroup()) {

            $parentNamespace =
                $this->getParentFieldGroup()->getParentMetaValueNamespace().
                $this->getParentFieldGroup()->getMetaValueNamespace();

                if ($parentNamespace !== "") {
                    $namespace = $parentNamespace;
                    $namespace = rtrim($namespace, '_');
                    $namespace .= '_';
                    return $namespace;
                }
        }



        return '';
    }

    /**
     * Combine the metaFieldKey with our current namespace.
     *
     * @param [type] $metaFieldKey [description]
     *
     * @return [type] [description]
     */
    protected function getNamespacedMetaFieldKey($metaFieldKey)
    {
        $namespace = $this->getParentMetaValueNamespace().$this->getMetaValueNamespace();

        if ($namespace !== '') {
            // Ensure only one _ appears after the namespace
            $namespace = rtrim($namespace, '_');
            $namespace .= '_';
        }

        return $namespace.$metaFieldKey;
    }

    /**
     * Getters / Setters.
     */
    public function setMetaDataBinding(MetaDataBinding $metaDataBinding)
    {
        $this->metaDataBinding = $metaDataBinding;
    }

    public function getMetaDataBinding()
    {
        return $this->metaDataBinding;
    }

    public function getBindingName()
    {
        $reflectionClass = new \ReflectionClass(static::class);

        // Chop off the namespace
        $className = $reflectionClass->getShortName();

        // Convert to snake case
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $className)), '_');
    }

    private function setParentFieldGroup($parentFieldGroup)
    {
        $this->parentFieldGroup = $parentFieldGroup;
    }

    private function getParentFieldGroup()
    {
        return $this->parentFieldGroup;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    private function setMetaValueNamespace($metaValueNamespace)
    {
        $this->metaValueNamespace = $metaValueNamespace;
    }

    public function getMetaValueNamespace()
    {
        return $this->metaValueNamespace;
    }

    public function __call($method, $args)
    {
        return $this->__get($method);
    }

    public function __get($property)
    {
        if (method_exists($this, 'get'.$property)) {
            return call_user_func_array([$this, 'get'.$property], []);
        }
        return $this->getMetaValue($property);
    }

    public function getField($field)
    {
        return $this->getConfig()->getField($field);
    }

    public function fieldExists($field)
    {
        return $this->getConfig()->fieldExists($field);
    }
}
