<?php

namespace Zipkin\Propagation;

/**
 * Injects and extracts {@link TraceContext trace identifiers} as text into carriers that travel
 * in-band across process boundaries. Identifiers are often encoded as messaging or RPC request
 * headers.
 *
 * <h3>Propagation example: Http</h3>
 *
 * When using http, the carrier of propagated data on both the client (injector) and server
 * (extractor) side is usually an http request. Propagation is usually implemented via library-
 * specific request interceptors, where the client-side injects span identifiers and the server-side
 * extracts them.
 */
interface Propagation
{
    /**
     * The propagation fields defined
     *
     * @return array|string[]
     */
    // The use cases of this are:
    // * allow pre-allocation of fields, especially in systems like gRPC Metadata
    // * allow a single-pass over an iterator (ex OpenTracing has no getter in TextMap)
    public function getKeys();

    /**
     * Used to send the trace context downstream. For example, as http headers.
     * Returns a injector as a callable having the signature function(TraceContext $context, $carrier): void
     *
     * @param Setter $setter invoked for each propagation key to add.
     * @return callable
     */
    public function getInjector(Setter $setter);

    /**
     * Returns the extractor as a callable having the signature function($carrier): TraceContext|SamplingFlags
     *
     * @param Getter $getter invoked for each propagation key to get.
     * @return callable
     */
    public function getExtractor(Getter $getter);
}
