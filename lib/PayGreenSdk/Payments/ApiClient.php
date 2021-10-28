<?php

namespace PayGreenSdk\Payments;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use PayGreenSdk\Core\Components\Environment;
use PayGreenSdk\Core\HttpClient;
use PayGreenSdk\Payments\Exceptions\PaymentCreationException;
use PayGreenSdk\Payments\Interfaces\OrderInterface;

class ApiClient extends HttpClient
{
    const API_BASE_URL_SANDBOX = 'https://sandbox.paygreen.fr';
    const API_BASE_URL_PROD = 'https://paygreen.fr';

    public function __construct()
    {
        $environment = new Environment(
            getenv('PAYGREEN_PUBLIC_KEY'),
            getenv('PAYGREEN_PRIVATE_KEY'),
            getenv('PAYGREEN_API_SERVER')
        );

        parent::__construct($environment);

        $this->initClient();
    }

    /**
     * @param OrderInterface $order
     * @param int $amount
     * @param string $notifiedUrl
     * @param string $currency
     * @param string $paymentType
     * @param string $returnedUrl
     * @param array $metadata
     * @param array $eligibleAmount
     * @param string $ttl
     * @return Response
     * @throws PaymentCreationException
     */
    public function createCash(
        OrderInterface $order,
        $amount,
        $notifiedUrl,
        $paymentType = 'CB',
        $currency = 'EUR',
        $returnedUrl = '',
        $metadata = array(),
        $eligibleAmount = array(),
        $ttl = ''
    ) {
        try {
            $url = $this->parseUrlParameters('/api/{ui}/payins/transaction/cash', array(
                'ui' => $this->environment->getPublicKey()
            ));

            $response = $this->client->post($url, array(
                'json' => array(
                    'orderId' => 'PG-' . $order->getReference(),
                    'amount' => $amount,
                    'currency' => $currency,
                    'paymentType' => $paymentType,
                    'notifiedUrl' => $notifiedUrl,
                    'returnedUrl' => $returnedUrl,
                    'buyer' => (object) array(
                        'id' => $order->getCustomer()->getId(),
                        'lastName' => $order->getCustomer()->getLastName(),
                        'firstName' => $order->getCustomer()->getFirstName(),
                        'country' => $order->getCustomer()->getCountryCode()
                    ),
                    'shippingAddress' => (object) array(
                        'lastName' => $order->getShippingAddress()->getLastName(),
                        'firstName' => $order->getShippingAddress()->getFirstName(),
                        'address' => $order->getShippingAddress()->getStreet(),
                        'zipCode' => $order->getShippingAddress()->getZipCode(),
                        'city' => $order->getShippingAddress()->getCity(),
                        'country' => $order->getShippingAddress()->getCountryCode()
                    ),
                    'billingAddress' => (object) array(
                        'lastName' => $order->getBillingAddress()->getLastName(),
                        'firstName' => $order->getBillingAddress()->getFirstName(),
                        'address' => $order->getBillingAddress()->getStreet(),
                        'zipCode' => $order->getBillingAddress()->getZipCode(),
                        'city' => $order->getBillingAddress()->getCity(),
                        'country' => $order->getBillingAddress()->getCountryCode()
                    ),
                    'metadata' => $metadata,
                    'eligibleAmount' => $eligibleAmount,
                    'ttl' => $ttl
                ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->environment->getPrivateKey()
                )
            ));

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $exception) {
            throw new PaymentCreationException(
                "An error occurred while creating a payment task for order '{$order->getReference()}'."
            );
        }
    }

    /**
     * @return string
     */
    private function getBaseUri()
    {
        if ($this->environment->getEnvironment() === 'SANDBOX') {
            $baseUri = self::API_BASE_URL_SANDBOX;
        } else {
            $baseUri = self::API_BASE_URL_PROD;
        }

        return $baseUri;
    }

    /**
     * @return void
     */
    private function initClient()
    {
        $this->client = new Client(array(
            'base_uri' => $this->getBaseUri(),
            'defaults' => array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => $this->buildUserAgentHeader()
                )
            )
        ));
    }
}