<?php

namespace Paygreen\Sdk\Payment\V2\Request\PaymentOrder;

use Exception;
use Paygreen\Sdk\Core\Encoder\JsonEncoder;
use Paygreen\Sdk\Core\Exception\ConstraintViolationException;
use Paygreen\Sdk\Core\Normalizer\CleanEmptyValueNormalizer;
use Paygreen\Sdk\Core\Serializer\Serializer;
use Paygreen\Sdk\Core\Validator\Validator;
use Paygreen\Sdk\Payment\V2\Model\PaymentOrder;
use Psr\Http\Message\RequestInterface;

class CashRequest extends \Paygreen\Sdk\Core\Request\Request
{
    /**
     * @param PaymentOrder $paymentOrder
     *
     * @throws ConstraintViolationException
     * @throws Exception
     *
     * @return RequestInterface
     */
    public function getCreateRequest($paymentOrder)
    {
        $violations = Validator::validateModel($paymentOrder);

        if ($violations->count() > 0) {
            throw new ConstraintViolationException($violations, 'Request parameters validation has failed.');
        }

        $publicKey = $this->environment->getPublicKey();

        $body = [
            'orderId' => $paymentOrder->getOrder()->getReference(),
            'amount' => $paymentOrder->getOrder()->getAmount(),
            'currency' => $paymentOrder->getOrder()->getCurrency(),
            'paymentType' => $paymentOrder->getPaymentType(),
            'type' => $paymentOrder->getType(),
            'notified_url' => $paymentOrder->getNotifiedUrl(),
            'returned_url' => $paymentOrder->getReturnedUrl(),
            'withPaymentLink' => $paymentOrder->getWithPaymentLink(),
            'buyer' => [
                'id' => $paymentOrder->getOrder()->getCustomer()->getId(),
                'lastName' => $paymentOrder->getOrder()->getCustomer()->getLastName(),
                'firstName' => $paymentOrder->getOrder()->getCustomer()->getFirstName(),
                'email' => $paymentOrder->getOrder()->getCustomer()->getEmail(),
                'country' => $paymentOrder->getOrder()->getCustomer()->getCountryCode(),
                'companyName' => $paymentOrder->getOrder()->getCustomer()->getCompanyName(),
            ],
            'shippingAddress' => [
                'address' => $paymentOrder->getOrder()->getShippingAddress()->getStreet(),
                'zipCode' => $paymentOrder->getOrder()->getShippingAddress()->getPostcode(),
                'city' => $paymentOrder->getOrder()->getShippingAddress()->getCity(),
                'country' => $paymentOrder->getOrder()->getShippingAddress()->getCountryCode(),
            ],
            'billingAddress' => [
                'address' => $paymentOrder->getOrder()->getBillingAddress()->getStreet(),
                'zipCode' => $paymentOrder->getOrder()->getBillingAddress()->getPostcode(),
                'city' => $paymentOrder->getOrder()->getBillingAddress()->getCity(),
                'country' => $paymentOrder->getOrder()->getBillingAddress()->getCountryCode(),
            ],
            'metadata' => $paymentOrder->getMetadata(),
            'ttl' => $paymentOrder->getTtl(),
        ];

        if (!empty($paymentOrder->getCardToken())) {
            $body['card'] = [
                'token' => $paymentOrder->getCardToken(),
            ];
        }

        $body['eligibleAmount'][$paymentOrder->getPaymentType()] = $body['amount'];

        return $this->requestFactory->create(
            "/api/{$publicKey}/payins/transaction/cash",
            (new Serializer([new CleanEmptyValueNormalizer()], [new JsonEncoder()]))->serialize($body, 'json')
        )->withAuthorization()->isJson()->getRequest();
    }
}