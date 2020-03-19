<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Messaging;

use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Tests\UnitTestCase;

/**
 * @internal
 */
class WebPushConfigTest extends UnitTestCase
{
    /**
     * @dataProvider validDataProvider
     */
    public function testCreateFromArray(array $data): void
    {
        $config = WebPushConfig::fromArray($data);

        $this->assertEquals($data, $config->jsonSerialize());
    }

    public function validDataProvider(): array
    {
        return [
            'full_config' => [[
                // https://firebase.google.com/docs/cloud-messaging/admin/send-messages#webpush_specific_fields
                'notification' => [
                    'title' => '$GOOG up 1.43% on the day',
                    'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
                    'icon' => 'https://my-server/icon.png',
                ],
            ]],
        ];
    }
}
