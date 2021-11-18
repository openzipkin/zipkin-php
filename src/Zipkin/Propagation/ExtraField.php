<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

use Zipkin\Propagation\TraceContext;

final class ExtraField implements Propagation
{
    private Propagation $delegate;

    /**
     * @var array<string,string>
     */
    private array $keyToName;

    /**
     * @var array<string,string>
     */
    private array $nameToKey;

    /**
     * @param Propagation $delegate
     * @param array<string,string> $keyToNameMap
     */
    public function __construct(Propagation $delegate, array $keyToNameMap)
    {
        $this->delegate = $delegate;
        $this->keyToName = $keyToNameMap;
        $this->nameToKey = array_flip($keyToNameMap);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys(): array
    {
        return array_values($this->nameToKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getInjector(Setter $setter): callable
    {
        $delegateInjector = $this->delegate->getInjector($setter);

        /**
         * @param TraceContext $traceContext
         * @param $carrier
         * @return void
         */
        return function (TraceContext $traceContext, &$carrier) use ($setter, $delegateInjector): void {
            foreach ($traceContext->getExtra() as $name => $value) {
                if (!array_key_exists($name, $this->nameToKey)) {
                    continue;
                }

                $setter->put($carrier, $this->nameToKey[$name], $value);
            }

            $delegateInjector($traceContext, $carrier);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExtractor(Getter $getter): callable
    {
        $delegateExtractor = $this->delegate->getExtractor($getter);

        /**
         * @param $carrier
         * @return SamplingFlags
         */
        return function ($carrier) use ($getter, $delegateExtractor): SamplingFlags {
            $traceContext = $delegateExtractor($carrier);
            if (!($traceContext instanceof TraceContext)) {
                return $traceContext;
            }

            $extra = [];
            foreach ($this->keyToName as $key => $name) {
                $value = $getter->get($carrier, $key);

                if ($value === null) {
                    continue;
                }

                $extra[$name] = $value;
            }

            return $traceContext->withExtra($extra);
        };
    }

    public function supportsJoin(): bool
    {
        return $this->delegate->supportsJoin();
    }
}
