<?php

namespace MauticPlugin\HelloWorldBundle\Tests\Unit\Sync\DataExchange;

use MauticPlugin\HelloWorldBundle\Sync\DataExchange\ValueNormalizer;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class ValueNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testBooleanConvertedForIntegration()
    {
        $normalizer      = new ValueNormalizer();
        $value           = new NormalizedValueDAO(NormalizedValueDAO::BOOLEAN_TYPE, 1);
        $normalizedValue = $normalizer->normalizeForIntegration($value);

        $this->assertSame(true, $normalizedValue);
    }

    public function testBooleanConvertedForMautic()
    {
        $normalizer      = new ValueNormalizer();
        $normalizedValue = $normalizer->normalizeForMautic(true, ValueNormalizer::BOOLEAN_TYPE);

        $this->assertSame(1, $normalizedValue->getNormalizedValue());
    }
}
