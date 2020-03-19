<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\RemoteConfig;

use Kreait\Firebase\RemoteConfig\DefaultValue;
use Kreait\Firebase\RemoteConfig\Parameter;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class ParameterTest extends UnitTestCase
{
    public function testCreateWithImplicitDefaultValue(): void
    {
        $parameter = Parameter::named('empty');

        $this->assertEquals(DefaultValue::none(), $parameter->defaultValue());
    }

    public function testCreateWithDefaultValue(): void
    {
        $parameter = Parameter::named('with_default_foo', 'foo');

        $this->assertEquals(DefaultValue::with('foo'), $parameter->defaultValue());
    }
}
