<?php

namespace Zipkin\Reporters\Http;

use BadFunctionCallException;
use RuntimeException;

final class CurlFactory implements ClientFactory
{
    /**
     * @return CurlFactory
     * @throws \BadFunctionCallException if the curl extension is not installed.
     */
    public static function create()
    {
        if (!function_exists('curl_init')) {
            throw new BadFunctionCallException('cURL is not enabled');
        }

        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $options = [])
    {
        /**
         * @param string $payload
         * @throws RuntimeException
         * @return void
         */
        return function ($payload) use ($options) {
            $handle = curl_init($options['endpoint_url']);
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($handle, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ]);

            if (curl_exec($handle) === true) {
                $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                curl_close($handle);

                if ($statusCode !== 202) {
                    throw new RuntimeException(
                        sprintf('Reporting of spans failed, status code %d', $statusCode)
                    );
                }
            } else {
                throw new RuntimeException(sprintf(
                    'Reporting of spans failed: %s, error code %s',
                    curl_error($handle),
                    curl_errno($handle)
                ));
            }
        };
    }
}
