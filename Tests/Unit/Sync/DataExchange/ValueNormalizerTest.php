<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\DataExchange;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\HelloWorldBundle\Sync\DataExchange\ValueNormalizer;

class ValueNormalizerTest extends \PHPUnit\Framework\TestCase
{
    public function testBooleanConvertedForIntegration(): void
    {
        $normalizer      = new ValueNormalizer();
        $value           = new NormalizedValueDAO(NormalizedValueDAO::BOOLEAN_TYPE, 1);
        $normalizedValue = $normalizer->normalizeForIntegration($value);

        $this->assertSame(true, $normalizedValue);
    }

    public function testBooleanConvertedForMautic(): void
    {
        $normalizer      = new ValueNormalizer();
        $normalizedValue = $normalizer->normalizeForMautic(true, ValueNormalizer::BOOLEAN_TYPE);

        $this->assertSame(1, $normalizedValue->getNormalizedValue());
    }
}
