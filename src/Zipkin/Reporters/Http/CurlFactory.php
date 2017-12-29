<?php

namespace Zipkin\Reporters\Http;

use RuntimeException;

class CurlFactory
{
    public static function create()
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $options)
    {
        /**
         * @param string $payload
         * @throws RuntimeException
         * @return void
         */
        return function ($payload) use ($options) {
            $handle = curl_init($options['endpoint_url']);

            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ]);

            if (curl_exec($handle) === true) {
                $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

                if ($statusCode !== '202') {
                    throw new RuntimeException(sprintf('Reporting of spans failed, code %s', $statusCode));
                }
            } else {
                throw new RuntimeException(
                    sprintf('Reporting of spans failed: %s, code %s', curl_error($handle), curl_errno($handle))
                );
            }

            curl_close($handle);
        };
    }
}
