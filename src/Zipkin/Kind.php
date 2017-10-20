<?php

namespace Zipkin\Kind;

const CLIENT = 'CLIENT';

const SERVER = 'SERVER';

/**
* When present, {@link Tracer#start()} is the moment a producer sent a message to a destination.
* A duration between {@link Tracer#start()} and {@link Tracer#finish()} may imply batching delay. {@link
* #remoteEndpoint(Endpoint)} indicates the destination, such as a broker.
*
* <p>Unlike {@link #CLIENT}, messaging spans never share a span ID. For example, the {@link
* #CONSUMER} of the same message has {@link TraceContext#parentId()} set to this span's {@link
* TraceContext#spanId()}.
*/
const PRODUCER = 'PRODUCER';

/**
* When present, {@link Tracer#start()} is the moment a consumer received a message from an
* origin. A duration between {@link Tracer#start()} and {@link Tracer#finish()} may imply a processing backlog.
* while {@link #remoteEndpoint(Endpoint)} indicates the origin, such as a broker.
*
* <p>Unlike {@link #SERVER}, messaging spans never share a span ID. For example, the {@link
* #PRODUCER} of this message is the {@link TraceContext#parentId()} of this span.
*/
const CONSUMER = 'CONSUMER';
