<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\AbstractGateway;

class ShopGateway extends AbstractGateway
{
    /**
     * Mode enumeration.
     */
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    /**
     * API encoding.
     */
    const ENCODING_ISO8859 = 'ISO 8859-1';
    const ENCODING_UTF8 = 'UTF-8';

    /**
     * Hash type to use.
     */
    const HASH_MD5 = 'MD5';
    const HASH_SHA2_384 = 'SHA2_384';

    protected $endpoint = 'https://api.pay1.de/post-gateway/';

    /**
     * The API version supported.
     */
    const API_VERSION = '3.9';

    public function getName()
    {
        return 'PAYONE Shop API';
    }

    /**
     * merchantId = Merchant account ID (mid: N..6)
     * portalId = Payment portal ID (portalid: N..7)
     * portalKey = Payment portal key (not encoded as MD5 value)
     * language is ISO 639
     */
    public function getDefaultParameters()
    {
        return array(
            'merchantId' => 0,
            'portalId' => 0,
            'subAccountId' => 0,
            'portalKey' => '',
            'testMode' => false,
            'encoding' => array(static::ENCODING_UTF8, static::ENCODING_ISO8859),
            'language' => 'en',
            'endpoint' => $this->endpoint,
            'hashMethod' => static::HASH_MD5,
        );
    }

    /**
     * *** Global Settings ***
     */

    /**
     * The Merchant ID is always needed.
     */
    public function setMerchantId($merchantId)
    {
        if (!is_numeric($merchantId)) {
            throw new InvalidRequestException('Merchant Account ID must be numeric.');
        }

        return $this->setParameter('merchantId', $merchantId);
    }

    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    /**
     * The Portal ID is always needed.
     */
    public function setPortalId($portalId)
    {
        if (!is_numeric($portalId)) {
            throw new InvalidRequestException('Payment Portal ID must be numeric.');
        }

        return $this->setParameter('portalId', $portalId);
    }

    public function getPortalId()
    {
        return $this->getParameter('portalId');
    }

    /**
     * The Portal Key is always needed.
     */
    public function setPortalKey($portalKey)
    {
        return $this->setParameter('portalKey', $portalKey);
    }

    public function getPortalKey()
    {
        return $this->getParameter('portalKey');
    }

    /**
     * The Sub Account ID is needed for each transaction.
     */
    public function setSubAccountId($subAccountId)
    {
        if (!is_numeric($subAccountId)) {
            throw new InvalidRequestException('Sub Account ID must be numeric.');
        }

        return $this->setParameter('subAccountId', $subAccountId);
    }

    public function getSubAccountId()
    {
        return $this->getParameter('subAccountId');
    }

    /**
     * The Endpoint will only need to be changed if instructructed.
     */
    public function setEndpoint($endpoint)
    {
        return $this->setParameter('endpoint', $endpoint);
    }

    public function getEndpoint()
    {
        return $this->getParameter('endpoint');
    }

    /**
     * The hash method to use in a number of places.
     * The PAYONE account must be configured with the hash method to be used.
     */
    public function setHashMethod($hashMethod)
    {
        return $this->setParameter('hashMethod', $hashMethod);
    }

    public function getHashMethod()
    {
        return $this->getParameter('hashMethod');
    }

    /**
     * The default encoding is ISO-5559-1 in the API.
     * We don't want to encourage that, so will set UTF-8 as the default in this gateway.
     */
    public function setEncoding($encoding)
    {
        if ($encoding != static::ENCODING_UTF8 && $encoding != static::ENCODING_ISO8859) {
            throw new InvalidRequestException(sprintf(
                'Encoding invalid. Must be "%s" or "%s".',
                static::ENCODING_UTF8,
                static::ENCODING_ISO8859
            ));
        }

        return $this->setParameter('encoding', $encoding);
    }

    public function getEncoding()
    {
        return $this->getParameter('encoding');
    }

    /**
     * The language sets the language used in the customermessage results..
     */
    public function setLanguage($language)
    {
        return $this->setParameter('language', $language);
    }

    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * *** Messages ***
     */

    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopAuthorizeRequest', $parameters);
    }

    /**
     * For handling a purchase (athorisation with capture).
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopPurchaseRequest', $parameters);
    }

    /**
     * For handling a capture action.
     */
    public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopCaptureRequest', $parameters);
    }

    /**
     * For capturing incoming (ServerRequest) Transaction Status messages from PAYONE.
     * Alias for acceptNotification()
     */
    public function completeStatus(array $parameters = array())
    {
        return $this->acceptNotification($parameters);
    }

    /**
     * Accept an incoming notification (a ServerRequest).
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopTransactionStatusServerRequest', $parameters);
    }

    /**
     * For handling a void action.
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopVoidRequest', $parameters);
    }

// Below: TODO

    /**
     * For handling a refund action.
     */
    public function DISABLED_refund(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopRefundRequest', $parameters);
    }

    /**
     * To fetch a single transaction.
     */
    public function DISABLED_fetchTransaction(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Payone\Message\ShopFetchTransactionRequest', $parameters);
    }
}
