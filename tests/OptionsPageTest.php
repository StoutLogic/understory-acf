<?php

namespace Understory\ACF\Tests;
use Understory\ACF\OptionsPage;
use phpmock\mockery\PHPMockery;
use Mockery;

class OptionsPageTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testGetTitle()
    {
        $optionsPage = new OptionsPage('Test Options');
        $this->assertSame('Test Options', $optionsPage->getTitle());
    }

    public function testGetId()
    {
        $optionsPage = new OptionsPage('Test Options');
        $this->assertSame('test-options', $optionsPage->getId());
    }

    public function testSetId()
    {
        $optionsPage = new OptionsPage('Test Options');
        $optionsPage->setId('options');
        $this->assertArraySubset([
            'page_title' => 'Test Options',
            'post_id' => 'options',
            'menu_slug' => 'options',
        ], $optionsPage->getConfig());
    }

    public function testGetConfig()
    {
        $optionsPage = new OptionsPage('Test Options');
        $this->assertArraySubset([
            'page_title' => 'Test Options',
            'post_id' => 'test-options',
        ], $optionsPage->getConfig());
    }

    public function testSetConfig()
    {
        $optionsPage = new OptionsPage('Test Options');
        $optionsPage->setConfig(['page_title' => 'Options']);
        $this->assertArraySubset([
            'page_title' => 'Options',
            'post_id' => 'test-options',
        ], $optionsPage->getConfig());
    }

    public function testSetConfigFromConstructor()
    {
        $optionsPage = new OptionsPage('Test Options',
            ['parent_slug' => 'posts']
        );
        $this->assertArraySubset([
            'page_title' => 'Test Options',
            'post_id' => 'test-options',
            'parent_slug' => 'posts',
        ], $optionsPage->getConfig());
    }

    public function testGetMetaValue()
    {
        $optionsPage = new OptionsPage('Test Options');

        // Mock the WordPress global function
        $getOption = PHPMockery::mock('Understory\ACF', 'get_option')
            ->andReturnUsing(function($optionName) {
                // Assert the option name was constructed properly
                $this->assertSame('test-options_test', $optionName);
            })
            ->once();

        $optionsPage->getMetaValue('test');
    }

    public function testSetMetaValue()
    {
        $optionsPage = new OptionsPage('Test Options');

        // Mock the WordPress global function
        $getOption = PHPMockery::mock('Understory\ACF', 'update_option')
            ->andReturnUsing(function($optionName, $value) {
                // Assert the option name was constructed properly
                $this->assertSame('test-options_test', $optionName);
                $this->assertSame(1, $value);
            })
            ->once();

        $optionsPage->setMetaValue('test', 1);
    }

    public function testRegister()
    {
        $optionsPage = new OptionsPage('Test Options');

        // Mock the ACF global function
        $addOptionspage = PHPMockery::mock('Understory\ACF', 'acf_add_options_page')
            ->andReturnUsing(function($config) {
                $this->assertArraySubset([
                    'page_title' => 'Test Options',
                    'post_id' => 'test-options',
                ], $config);
            })
            ->once();

        // Return true that acf_add_options_page funciton exists
        $functionExists = PHPMockery::mock('Understory\ACF', 'function_exists')
            ->with('acf_add_options_page')
            ->andReturn(true);

        $optionsPage->register();
    }
}
