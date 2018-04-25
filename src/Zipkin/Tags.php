<?php

namespace Zipkin\Tags;

/**
 * The domain portion of the URL or host header. Ex. "mybucket.s3.amazonaws.com"
 *
 * <p>Used to filter by host as opposed to ip address.</p>
 */
const HTTP_HOST = 'http.host';

/**
 * The HTTP method, or verb, such as "GET" or "POST".
 *
 * <p>Used to filter against an http route.</p>
 */
const HTTP_METHOD = 'http.method';

/**
 * The absolute http path, without any query parameters. Ex. "/objects/abcd-ff"
 *
 * <p>Used as a filter or to clarify the request path for a given route. For example, the path for
 * a route "/objects/:objectId" could be "/objects/abdc-ff". This does not limit cardinality like
 * {@link #HTTP_ROUTE} can, so is not a good input to a span name.</p>
 *
 * <p>The Zipkin query api only supports equals filters. Dropping query parameters makes the
 * number of distinct URIs less. For example, one can query for the same resource, regardless of
 * signing parameters encoded in the query line. Dropping query parameters also limits the
 * security impact of this tag.</p>
 *
 * <p>Historical note: This was commonly expressed as "http.uri" in zipkin, even though it was most</p>
 */
const HTTP_PATH = 'http.path';

/**
 * The route which a request matched or "" (empty string) if routing is supported, but there was
 * no match. Ex "/objects/{objectId}"
 *
 * <p>Often used as a span name when known, with empty routes coercing to "not_found" or
 * "redirected" based on {@link #HTTP_STATUS_CODE}.</p>
 *
 * <p>Unlike {@link #HTTP_PATH}, this value is fixed cardinality, so is a safe input to a span
 * name function or a metrics dimension. Different formats are possible. For example, the
 * following are all valid route templates: "/objects" "/objects/:objectId" "/objects/*"</p>
 */
const HTTP_ROUTE = 'http.route';

/**
 * The entire URL, including the scheme, host and query parameters if available. Ex.
 * "https://mybucket.s3.amazonaws.com/objects/abcd-ff?X-Amz-Algorithm=AWS4-HMAC-SHA256..."
 *
 * <p>Combined with {@linkplain #HTTP_METHOD}, you can understand the fully-qualified request</p>
 * line.
 *
 * <p>This is optional as it may include private data or be of considerable length.</p>
 */
const HTTP_URL = 'http.url';

/**
 * The HTTP status code, when not in 2xx range. Ex. "503"
 *
 * <p>Used to filter for error status.</p>
 */
const HTTP_STATUS_CODE = 'http.status_code';

/**
 * The size of the non-empty HTTP request body, in bytes. Ex. "16384"
 *
 * <p>Large uploads can exceed limits or contribute directly to latency.</p>
 */
const HTTP_REQUEST_SIZE = 'http.request.size';

/**
 * The size of the non-empty HTTP response body, in bytes. Ex. "16384"
 *
 * <p>Large downloads can exceed limits or contribute directly to latency.</p>
 */
const HTTP_RESPONSE_SIZE = 'http.response.size';

/**
 * The query executed for SQL call.  Ex. "select * from customers where id = ?"
 *
 * <p>Used to understand the complexity of a request</p>
 */
const SQL_QUERY = 'sql.query';

/**
 * The value of "lc" is the component or namespace of a local span.
 */
const LOCAL_COMPONENT = 'lc';

const ERROR = 'error';
