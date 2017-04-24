<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) Client (CC detailshashing form, local or iframe)
 * driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;

class ShopClientGateway extends AbstractShopGateway
{
    /**
     * The response type when making a POST.
     */
    const RETURN_TYPE_JSON = 'JSON';
    const RETURN_TYPE_REDIRECT = 'REDIRECT';

    protected $javascript_url = 'https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js';
    protected $endpoint = 'https://secure.pay1.de/client-api/';


    public function getName()
    {
        return 'PAYONE Shop Client';
    }

    /*
     *
     */
    public function getDefaultParameters()
    {
        $params = parent::getDefaultParameters();

        $params['responseType'] = array(
            static::RETURN_TYPE_JSON,
            static::RETURN_TYPE_REDIRECT,
        );

        return $params;
    }

    /**
     * The Response Type is always needed.
     * This determines whether the response will be a message
     * or a redirect (with a complete*() method needed later).
     */
    public function setResponseType($value)
    {
        return $this->setParameter('responseType', $value);
    }

    public function getResponseType()
    {
        return $this->getParameter('responseType');
    }

    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopClientAuthorizeRequest', $parameters);
    }

    /**
     * The purchase transaction.
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopClientPurchaseRequest', $parameters);
    }

    /**
     * The complete authorization transaction (capturing data retuned with the user).
     */
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopClientCompleteAuthorizeRequest', $parameters);
    }

    /**
     * The complete purchase transaction (capturing data retuned with the user).
     */
    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopClientCompleteAuthorizeRequest', $parameters);
    }

    /**
     * Helper for generating the hidden fields in a credit card tokenisation AJAX form.
     */
    public function creditCardCheck(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopClientCardCheckRequest', $parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     * This API supports the notification responses as well as the complete* responses.
     * However, only the notification responses are signed and so can be trusted.
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopTransactionStatusServerRequest', $parameters);
    }
}
