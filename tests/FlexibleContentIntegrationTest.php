<?php

namespace Understory\ACF\Tests;

use PHPUnit\Framework\TestCase;
use Understory\ACF\CustomOptionsPage;
use Understory\ACF\OptionsPage;
use Understory\ACF\FieldGroup;

use phpmock\mockery\PHPMockery;
use Mockery;

class FlexibleContentIntegrationTest extends TestCase
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

    public function testUseFlexibleContentField()
    {
        $metaDataBinding = Mockery::mock(MyOptionsPage::class);

        $metaDataBinding
            ->shouldReceive('getMetaValue')
            ->with('sections')
            ->once()
            ->andReturn(serialize([Content1::class, Content2::class]));

        $metaDataBinding
            ->shouldReceive('getMetaValue')
            ->with('sections_1_field3')
            ->once()
            ->andReturn('test');

        $contentSections = new ContentSections($metaDataBinding);
        $contentSections->register();

        $this->assertSame('test', $contentSections->getSections()[1]->getField3());
    }
}

class Content1 extends FieldGroup
{
    public function configure($fieldsBuilder)
    {
        return $fieldsBuilder
            ->addText('field1')
            ->addText('field2');
    }
}

class Content2 extends FieldGroup
{
    public function configure($fieldsBuilder)
    {
        return $fieldsBuilder
            ->addText('field3')
            ->addText('field4');
    }

    public function getField3()
    {
        return $this->getMetaValue('field3');
    }
}

class ContentSections extends FieldGroup
{
    protected $sections;

    public function configure($fieldsBuilder)
    {
        return $fieldsBuilder
            ->addFlexibleContent('sections')
                ->addLayout(new Content1)
                ->addLayout(new Content2);
    }

    public function getSections()
    {
        return $this->getMetaValues('sections');
    }
}

class MyOptionsPage extends CustomOptionsPage
{
    protected $contentSetions;

    public function configure(OptionsPage $optionsPage)
    {
        $this->set('contentSetions', new ContentSections);

        return $optionsPage;
    }

    public function getContentSections()
    {
        return $this->contentSetions;
    }

}
