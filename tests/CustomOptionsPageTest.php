<?php

namespace Understory\ACF\Tests;

use PHPUnit\Framework\TestCase;
use Understory\ACF\CustomOptionsPage;
use Understory\ACF\OptionsPage;
use Understory\ACF\FieldGroup;
use phpmock\mockery\PHPMockery;
use Mockery;

class CustomOptionsPageTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testRegister()
    {
        $siteSettings = Mockery::namedMock('SiteSettings', CustomOptionsPage::class.'[configure]')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $siteSettings
            ->shouldReceive('configure')
            ->once()
            ->andReturnUsing(function(OptionsPage $optionsPage) use ($siteSettings) {
                // Make sure the title is deirved from the Class Name
                $this->assertSame('Site Settings', $optionsPage->getTitle());

                // Make sure the OptionsPage is registered
                $optionsPageMock = Mockery::mock($optionsPage);
                $optionsPageMock
                    ->shouldReceive('register')
                    ->once();

                $fieldGroup = Mockery::mock(FieldGroup::class);

                $fieldGroup
                    ->shouldReceive('register')
                    ->once();

                $fieldGroup
                    ->shouldReceive('setMetaDataBinding')
                    ->once();

                $siteSettings->set('field', $fieldGroup);

                return $optionsPageMock;
            });

        $siteSettings->register();
    }

}
