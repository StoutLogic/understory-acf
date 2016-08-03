<?php

namespace Understory\ACF\Tests;

use PHPUnit\Framework\TestCase;
use Understory\ACF\CustomOptionsPage;
use Understory\ACF\OptionsPage;
use Understory\ACF\FieldGroup;

use phpmock\mockery\PHPMockery;
use Mockery;

class OptionsPageIntegrationTest extends TestCase
{
    public function setup()
    {
        // Mock the WordPress global function
        $this->wordPressAddActionMock = PHPMockery::mock('Understory\ACF', 'add_action')
            ->with('acf/init', Mockery::any());

        // Mock the WordPress global function
        $this->acfAddOptionsPageMock = PHPMockery::mock('Understory\ACF', 'acf_add_options_page')
            ->with(Mockery::any());

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testCreateOptionsPage()
    {
        $siteSettings = new SiteSettings;

        $this->assertSame('Site Settings', $siteSettings->getTitle());
        $this->assertInstanceOf(SocialSettings::class, $siteSettings->socialSettings);

        $this->wordPressAddActionMock->times(1);
        $this->acfAddOptionsPageMock->times(1);

        $siteSettings->register();

        $facebookUrl = 'http://facebook.com';

        PHPMockery::mock('Understory\ACF', 'get_option')
            ->with('site-settings_facebook_url')
            ->andReturn($facebookUrl)
            ->times(2);

        $this->assertSame($facebookUrl, $siteSettings->getSocialSettings()->getFacebookUrl());
        $this->assertSame($facebookUrl, $siteSettings->socialSettings->facebookUrl);
    }
}

class SocialSettings extends FieldGroup
{
    protected $twitter_handle;
    protected $facebook_url;
    protected $linkedin_url;

    public function configure($fieldsBuilder)
    {
        $fieldsBuilder
            ->addText('twitter_handle', ['prepend' => '@'])
            ->addUrl('facebook_url')
            ->addUrl('linkedin_url');

        return $fieldsBuilder;
    }

    public function getTwitterHandle()
    {
        return $this->getMetaValue('twitter_handle');
    }

    public function getTwitterUrl()
    {
        return 'https://twitter.com' . $this->getTwitterHandle();
    }

    public function getFacebookUrl()
    {
        return $this->getMetaValue('facebook_url');
    }

    public function getLinkedinUrl()
    {
        return $this->getMetaValue('linkedin_url');
    }
}

class SiteSettings extends CustomOptionsPage
{
    protected $socialSettings;

    public function configure(OptionsPage $optionsPage)
    {
        $this->set('socialSettings', new SocialSettings);

        return $optionsPage;
    }

    public function getSocialSettings()
    {
        return $this->socialSettings;
    }

}
