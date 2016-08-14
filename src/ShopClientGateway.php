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
     * The return type when making a POST.
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
     * The completion authorization transaction (capturting data retuned with the user).
     */
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopClientCompleteAuthorizeRequest', $parameters);
    }
}
