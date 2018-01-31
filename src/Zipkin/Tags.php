<?php

namespace Zipkin\Tags;

const HTTP_HOST = 'http.host';

/**
 * Records the HTTP method (GET, POST, etc.) of a request
 */
const HTTP_METHOD = 'http.method';

/**
 * Records the path of a URL. It does not include query parameters
 */
const HTTP_PATH = 'http.path';

/**
 * Records the full URL.
 */
const HTTP_URL = 'http.url';

/**
 * Records the status code of a response.
 */
const HTTP_STATUS_CODE = 'http.status_code';

const HTTP_REQUEST_SIZE = 'http.request.size';

const HTTP_RESPONSE_SIZE = 'http.response.size';

const SQL_QUERY = 'sql.query';

const LOCAL_COMPONENT = 'lc';

const ERROR = 'error';
