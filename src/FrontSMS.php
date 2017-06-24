<?php
/*!
 * frontsms
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Anders Evenrud <andersevenrud@gmail.com>
 */

namespace FrontSMS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

use FrontSMS\FrontSMSException;

/**
 * Front SMS
 * @author Anders Evenrud <andersevenrud@gmail.com>
 */
class FrontSMS
{

    protected $configuration = [
        'debug' => false,
        'base_uri' => 'https://www.pling.as/psk/push.php',
        'serviceid' => null,
        'fromid' => null
    ];

    /**
     * Create a new instance
     *
     * @throws InvalidArgumentException
     * @param Array $configuration The configuration
     */
    public function __construct(Array $configuration)
    {
        $this->configuration = array_merge($this->configuration,
            $configuration);

        // Make sure no empty configurations
        foreach ( $this->configuration as $k => $v ) {
            if ( empty($v) && ($v !== false) ) {
                throw new InvalidArgumentException('Missing configuration: ' . $k);
            }
        }
    }

    /**
     * Builds and checks final arguments array
     *
     * @since 0.5
     * @throws InvalidArgumentException
     * @param Array $args Arguments
     * @return Array
     */
    protected function checkArguments(Array $args)
    {
        $args = array_merge([
            'serviceid' => $this->configuration['serviceid'],
            'fromid' => $this->configuration['fromid']
        ], $args);

        if (  empty($args['serviceid']) ) {
            throw new InvalidArgumentException('Cannot send without credentials');
        }

        if ( empty($args['txt']) ) {
            throw new InvalidArgumentException('Cannot send empty message');
        }

        if ( empty($args['phoneno']) ) {
            throw new InvalidArgumentException('Invalid reciever number');
        }

        return $args;
    }

    /**
     * Perform a request
     *
     * @since 0.5
     * @throws FrontSMSException
     * @throws InvalidArgumentException
     * @param Array $args Endpoint arguments
     * @return Array
     */
    protected function request(Array $args)
    {
        $args = $this->checkArguments($args);


        $client = new Client([
            'base_uri' => $this->configuration['base_uri']
        ]);

        try {
            $response = $client->request('GET', '', [
                'debug' => $this->configuration['debug'],
                'query' => $args ?: []
            ]);
        } catch ( ClientException $e ) {
            throw new FrontSMSException($e->getMessage());
        } catch ( ConnectException $e ) {
            throw new FrontSMSException($e->getMessage());
        }

        $body = (string) $response->getBody();

        $data = static::parseResponse($body);

        if ( !isset($data['ErrorCode']) ) {
            throw new FrontSMSException('Invalid response from gateway: ' . $body);
        }

        if ( $data['ErrorCode'] != 0 ) {
            throw new FrontSMSException(sprintf('Gateway error: %s (%s)', $data['ID'], $data['ErrorCode']));
        }

        return $data;
    }

    /**
     * Parses a response from SMS Gateway
     *
     * @param String $response
     * @return Array
     */
    static protected function parseResponse($response)
    {
        $list = explode(', ', $response);

        $data = [];
        foreach ( $list as $iter ) {
            list($key, $value) = explode('=', $iter);
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Escape a phone number
     *
     * @param String $number
     * @return String
     */
    static protected function escapeNumber($number)
    {
        return is_null($number) ? $number : preg_replace('/[^A-Za-z0-9 ]/', '', $number);
    }

    /**
     * Sends a message
     *
     * @throws FrontSMSException
     * @throws InvalidArgumentException
     * @param String $to To phone number
     * @param String $message Message
     * @return Array
     */
    public function send($to, $message)
    {
        $to = static::escapeNumber($to);

        return $this->request([
            'phoneno' => $to,
            'txt' => (string)$message
        ]);
    }

}
