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
     * @param Setter $setter invoked for each propagation key to add.
     * @return Callable
     */
    public function getInjector(Setter $setter);

    /**
     * @param Getter $getter invoked for each propagation key to get.
     * @return Callable
     */
    public function getExtractor(Getter $getter);
}
