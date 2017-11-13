<?php

namespace Zipkin\Propagation;

use Zipkin\TraceContext;

final class ExtraField implements Propagation
{
    /**
     * @var Propagation
     */
    private $delegate;

    /**
     * @var string[]
     */
    private $keyToName;

    /**
     * @var string[]
     */
    private $nameToKey;

    public function __construct(Propagation $delegate, array $keyToName)
    {
        $this->delegate = $delegate;
        $this->keyToName = $keyToName;
        $this->nameToKey = array_flip($keyToName);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        return array_values($this->nameToKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getInjector(Setter $setter)
    {
        $nameToKey = $this->nameToKey;
        $delegateInjector = $this->delegate->getInjector($setter);

        /**
         * @param TraceContext $traceContext
         * @param $carrier
         * @return void
         */
        return function (TraceContext $traceContext, $carrier) use ($setter, $delegateInjector, $nameToKey) {
            foreach ($traceContext->getExtra() as $name => $value) {
                if (!array_key_exists($name, $nameToKey)) {
                    continue;
                }

                $setter->put($carrier, $nameToKey[$name], $value);
            }

            $delegateInjector($traceContext, $carrier);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExtractor(Getter $getter)
    {
        $keyToName = $this->keyToName;
        $delegateExtractor = $this->delegate->getExtractor($getter);

        /**
         * @param $carrier
         * @return TraceContext
         */
        return function ($carrier) use ($getter, $delegateExtractor, $keyToName) {
            $traceContext = $delegateExtractor($carrier);

            $extra = [];

            foreach ($keyToName as $key => $name) {
                $value = $getter->get($carrier, $key);

                if ($value === null) {
                    continue;
                }

                $extra[$name] = $value;
            }

            return $traceContext->withExtra($extra);
        };
    }
}
