<?php

namespace ZipkinTests\Unit;

use Zipkin\Span;
use PHPUnit\Framework\TestCase;
use Zipkin\SpanCustomizerShield;

final class SpanCustomizerShieldTest extends TestCase
{
    const TEST_NAME = 'name';
    const TEST_TAG_KEY = 'key';
    const TEST_TAG_VALUE = 'value';
    const TEST_ANNOTATION_VALUE = 'annotation';

    public function testAttributesAreSetAndMethodAreCalled()
    {
        $span = new class($this) implements Span {
            use SpanCustomizerShieldSpan;

            public function isNoop(): bool
            {
                return false;
            }

            public function setName(string $name): void
            {
                $this->test->assertEquals(SpanCustomizerShieldTest::TEST_NAME, $name);
            }

            public function tag(string $key, string $value): void
            {
                $this->test->assertEquals(SpanCustomizerShieldTest::TEST_TAG_KEY, $key);
                $this->test->assertEquals(SpanCustomizerShieldTest::TEST_TAG_VALUE, $value);
            }

            public function annotate(string $value, ?int $timestamp = null): void
            {
                $this->test->assertEquals(SpanCustomizerShieldTest::TEST_ANNOTATION_VALUE, $value);
            }
        };

        $spanCustomizer = new SpanCustomizerShield($span);
        $spanCustomizer->setName(self::TEST_NAME);
        $spanCustomizer->tag(self::TEST_TAG_KEY, self::TEST_TAG_VALUE);
        $spanCustomizer->annotate(self::TEST_ANNOTATION_VALUE);
    }
}
