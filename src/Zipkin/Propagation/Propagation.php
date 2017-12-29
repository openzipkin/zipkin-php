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
     * The propagation fields defined. If your carrier is reused, you should delete the fields here
     * before calling {@link Setter#put(object, string, string)}.
     *
     * <p>For example, if the carrier is a single-use or immutable request object, you don't need to
     * clear fields as they could not have been set before. If it is a mutable, retryable object,
     * successive calls should clear these fields first.
     *
     * @return array|string[]
     */
    // The use cases of this are:
    // * allow pre-allocation of fields, especially in systems like gRPC Metadata
    // * allow a single-pass over an iterator (ex OpenTracing has no getter in TextMap)
    public function getKeys();

    /**
     * Returns a injector as a callable having the signature function(TraceContext $context, &$carrier): void
     *
     * The injector replaces a propagated field with the given value so <b>it is very important the carrier is
     * being passed by reference.</b>
     *
     * @param Setter $setter invoked for each propagation key to add.
     * @return callable
     */
    public function getInjector(Setter $setter);

    /**
     * Returns the extractor as a callable having the signature function($carrier): TraceContext|SamplingFlags
     * - return SamplingFlags being empty if the context does not hold traceId, not debug nor sampling decision
     * - return SamplingFlags if the context does not contain a spanId.
     * - return TraceContext if the context contains a traceId and a spanId.
     *
     * @param Getter $getter invoked for each propagation key to get.
     * @return callable
     */
    public function getExtractor(Getter $getter);
}
