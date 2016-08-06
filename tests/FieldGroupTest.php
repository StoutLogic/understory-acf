<?php

namespace Understory\ACF\Tests;

use Understory\ACF\FieldsBuilder;
use Understory\ACF\FieldGroup;
use Understory\ACF\FieldGroupInterface;
use Understory\CustomPostType;
use PHPUnit\Framework\TestCase;
use phpmock\mockery\PHPMockery;
use Mockery;

class FieldGroupTest extends TestCase
{
    public function setup()
    {
        // Mock the WordPress global function
        $this->wordPressAddActionMock = PHPMockery::mock('Understory\ACF',
            "add_action")
            ->with('acf/init', Mockery::any());
    }

    public function tearDown()
    {
        // see Mockery's documentation for why we do this
        Mockery::close();
    }

    public function testIsAbstract()
    {
        $abstractClass = new \ReflectionClass(FieldGroup::class);

        $this->assertTrue($abstractClass->isAbstract());
    }


    public function testMetaDataBinding()
    {
        $metaDataBindingValue = $this->createMock(\Understory\MetaDataBinding::class);
        $newMetaDataBinding = $this->createMock(\Understory\MetaDataBinding::class);

        $fieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class,
                [$metaDataBindingValue]);

        $this->assertSame($metaDataBindingValue,
            $fieldGroup->getMetaDataBinding());

        $fieldGroup->setMetaDataBinding($newMetaDataBinding);

        $this->assertNotSame($metaDataBindingValue,
            $fieldGroup->getMetaDataBinding());
        $this->assertSame($newMetaDataBinding,
            $fieldGroup->getMetaDataBinding());
    }

    public function testParentFieldGroupMetaDataBinding()
    {
        $metaDataBindingValue = $this->createMock(\Understory\MetaDataBinding::class);

        $parentFieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class,
                [$metaDataBindingValue]);
        $childFieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$parentFieldGroup]);

        $this->assertSame($metaDataBindingValue,
            $parentFieldGroup->getMetaDataBinding());
        $this->assertSame($metaDataBindingValue,
            $childFieldGroup->getMetaDataBinding());
    }

    public function testGetMetaValue()
    {
        $metaDataBinding = $this
            ->getMockBuilder(CustomPostType::class)
            ->getMock();

        $metaValue = 'meta content';

        $metaDataBinding
            ->expects($this->once())
            ->method('getMetaValue')
            ->with('content')
            ->willReturn($metaValue);

        $fieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);

        $this->assertSame($metaValue, $fieldGroup->getMetaValue('content'));
    }

    public function testSetMetaValue()
    {
        $metaDataBinding = $this
            ->getMockBuilder(CustomPostType::class)
            ->getMock();

        $metaValue = 'meta content';

        $metaDataBinding
            ->expects($this->once())
            ->method('setMetaValue')
            ->with('key', 'content');

        $fieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);

        $fieldGroup->setMetaValue('key', 'content');
    }

    public function testGetIndexedMetaValue()
    {
        $metaDataBinding = $this
            ->getMockBuilder(CustomPostType::class)
            ->getMock();

        $metaValue = 'meta content';

        $metaDataBinding
            ->expects($this->once())
            ->method('getMetaValue')
            ->with('content_0_text')
            ->willReturn($metaValue);

        $fieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);

        $this->assertSame(
            $metaValue,
            $fieldGroup
                ->getMetaValue('content', 0)
                ->getMetaValue('text')
        );
    }

    public function testGetNamespacedMetaValue()
    {
        $metaDataBinding = $this
            ->getMockBuilder(CustomPostType::class)
            ->getMock();

        $metaValue = 'meta content';

        $metaDataBinding
            ->expects($this->once())
            ->method('getMetaValue')
            ->with('slide_1_column_1_content')
            ->willReturn($metaValue);

        $parentFieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);

        $childFieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class,
                [$parentFieldGroup, 'slide_1']);

        $grandChildFieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class,
                [$childFieldGroup, 'column_1']);

        $this->assertSame(
            $metaValue,
            $grandChildFieldGroup->getMetaValue('content')
        );
    }

    public function testGetRepeaterMetaValues()
    {
        $metaDataBinding = $this
            ->getMockBuilder(CustomPostType::class)
            ->getMock();


        $metaValues = ['meta content1', 'meta content2', 'meta content3'];

        $metaDataBinding
            ->method('getMetaValue')
            ->will($this->returnValueMap(
                [
                    ['slides', count($metaValues)],
                    ['slides_1_content', $metaValues[1]]
                ])
            );

        $fieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);
        $childFieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);

        $this->assertSame(
            $metaValues[1],
            $fieldGroup
                ->getMetaValues('slides')[1]
                ->getMetaValue('content')
        );
    }

    public function testGetFlexibleContentMetaValues()
    {
        $metaDataBinding = $this
            ->getMockBuilder(CustomPostType::class)
            ->getMock();

        $unseralized = ['FieldGroup1', 'FieldGroup2', 'FieldGroup3'];

        $layout = $this->getMockBuilder(CustomPostType::class)
            ->setMockClassName('FieldGroup2')
            ->getMock();


        $metaValues = serialize($unseralized);

        $metaDataBinding
            ->method('getMetaValue')
            ->will($this->returnValueMap(
                [
                    ['sections', $metaValues],
                    ['sections_1_content', 'test'],
                ])
            );

        $fieldGroup = $this
            ->getMockForAbstractClass(FieldGroup::class, [$metaDataBinding]);

        $fieldGroup
            ->getMetaValues('sections')[1]
            ->expects($this->once())
            ->method('getMetaValue')
            ->with('content')
            ->willReturn('test');

        $this->assertSame(
            'test',
            $fieldGroup
                ->getMetaValues('sections')[1]
                ->getMetaValue('content')
        );
    }

    public function testConfigure()
    {
        $fieldGroup = Mockery::namedMock('Banner',
            FieldGroup::class . '[configure]')
            ->shouldAllowMockingProtectedMethods();

        $fieldGroup
            ->shouldReceive('configure')
            ->andReturnUsing(function ($builder) {
                $this->assertInstanceOf(FieldsBuilder::class, $builder);
                $this->assertStringEndsWith(
                    "banner",
                    $builder->getName()
                );

                $builder
                    ->addText('title')
                    ->addWysiwyg('content');

                return $builder;
            });

        $config = $fieldGroup->getConfig();
        $expectedConfig = [
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                ],
                [
                    'type' => 'wysiwyg',
                    'name' => 'content',
                ],
            ],
        ];

        $this->assertArraySubset($expectedConfig, $config->build());
    }

    public function testRegister()
    {
        $metaDataBinding = Mockery::mock(CustomPostType::class)
            ->shouldReceive('getPostType')
            ->andReturn('test');

        $fieldGroup = Mockery::mock(FieldGroup::class . '[configure]')
            ->shouldAllowMockingProtectedMethods();

        $fieldGroup
            ->shouldReceive('configure')
            ->andReturnUsing(function ($builder) {
                $builder->addText('title');
                $builder->addWysiwyg('content');

                return $builder;
            });

        $expectedConfig = [
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'test',
                    ]
                ]
            ],
        ];
        $config = $fieldGroup->register($metaDataBinding->getMock());
        $this->assertArraySubset($expectedConfig, $config);
    }

    public function testSetSequentialPosition()
    {
        $fieldGroup = Mockery::mock(FieldGroup::class . '[configure]')
            ->shouldAllowMockingProtectedMethods();

        $fieldGroup
            ->shouldReceive('configure')
            ->andReturnUsing(function ($builder) {
                $builder->addText('title');
                $builder->addWysiwyg('content');

                return $builder;
            });

        $metaDataBinding = Mockery::mock(CustomPostType::class);

        $fieldGroup->setSequentialPosition(5, $metaDataBinding);

        $expectedConfig = [
            'menu_order' => 5,
        ];

        $this->assertArraySubset($expectedConfig, $fieldGroup->register());
    }
}
