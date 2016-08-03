<?php

class TeamMember extends \Understory\CustomPostTypeDecorator
{
    /**
     * Banner for Team Member
     * @var Fields\Banner
     */
    private $banner;

    /**
     * Taxonomy
     * @var Taxonomies\TeamMemberRoles
     */
    private $roles;

    private function configure(\Understory\CustomPostType $customPostType)
    {
        $customPostType->setConfig([
            'supports' => 'title, thumbnail',
        ]);

        // Custom Post Types get registered after Taxonomies
        $this->roles = new Taxonomies\TeamMemberRoles($this);
        $this->banner = new Fields\Banner($this);

        // Use setters which are implmeneted via __set to store the new
        // instances as well as register the field (only once) if needed.
        $this->setRoles(new Taxonomies\TeamMemberRoles);
        $this->setBanner(new Fields\Banner);

        return $customPostType;
    }


    // These functions are dynamically created using __set and __get
    function setBanner($banner)
    {
        $banner->setScope($this);
        $this->addToRegistry($banner);
    }

    function setRoles($roles)
    {
        $roles->setScope($this);
        $this->addToRegistry($roles);
    }

    function getRoles()
    {
        $this->roles;
    }

    function register()
    {
        $this->getCustomPostType()->register();

        foreach ($this->getRegistry() as $registryitem) {
            $registryItem->register($this);
        }
    }
}

class TeamOptions extends \Understory\ACF\OptionsPageDecorator
{
    private $banner;

    private function configure(\Understory\ACF\OptionsPage $optionsPage)
    {
        $this->banner = new Fields\Banner($this);

        return $optionsPage;
    }
}

class EventsTemplate extends \Understory\View
{
    private $banner;

    private function configure()
    {
        $this->setContext('page', $this);
        $this->setTemplate('section-page.twig');

        $this->banner = new Fields\Banner($this);
    }
}

class TeamMemberRoles extends \Understory\Taxonomy
{
    private $color;

    private function configure(\Understory\CustomTaxonomy $customTaxonomy)
    {
        $customTaxonomy->setConfig([
            'menu_name' => 'Roles',
            'hierarchical' => true,
            'capabilities' => [
                'manage_terms' => 'edit_posts',
                'edit_terms'   => 'edit_posts',
                'delete_terms' => 'edit_posts',
                'assign_terms' => 'edit_posts',
            ],
        ]);

        $this->color = new Fields\Color($this);

        return $customTaxonomy;
    }
}

$style = "";
foreach ($css as $key => $value) {
    $style .= $key.':'.$value.';';
}
return $style;

    return join('', array_map(function($value, $key) {
        return $key.':'.$value.';';
    }));
