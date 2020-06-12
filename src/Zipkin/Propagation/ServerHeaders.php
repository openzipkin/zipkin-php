<?php

declare(strict_types=1);

namespace Zipkin\Propagation;

// This class implements the Zipkin getter interface to extract the zipkin
// headers out of the $_SERVER variable.
// Due to how the api is written, $_SERVER gets passed in as $carrier rather
// than get accessed here directly.
//
// Example:
//   $extractor = $this->tracing->getPropagation()->getExtractor(new ServerHeaders);
//   $extractedContext = $extractor($_SERVER);
final class ServerHeaders implements Getter
{
    /**
     * {@inheritdoc}
     *
     * @param mixed $carrier
     * @param string $key
     * @return string|null
     */
    public function get($carrier, string $key): ?string
    {
        // Headers in $_SERVER are always uppercased, with any - replaced with an _
        $key = strtoupper($key);
        $key = str_replace('-', '_', $key);

        return $carrier['HTTP_' . $key] ?? null;
    }
}
