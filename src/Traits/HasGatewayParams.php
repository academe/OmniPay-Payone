<?php

namespace Omnipay\Payone\Traits;

/**
 * Gateway parameter setters and getters, shared by the gateway
 * and the message classes.
 */

use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Common\Exception\InvalidRequestException;

trait HasGatewayParams
{
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
     * The default encoding is ISO-5559-1 in the API.
     * We don't want to encourage that, so will set UTF-8 as the default in this gateway.
     */
    public function setEncoding($encoding)
    {
        if ($encoding != AbstractShopGateway::ENCODING_UTF8 && $encoding != AbstractShopGateway::ENCODING_ISO8859) {
            throw new InvalidRequestException(sprintf(
                'Encoding invalid. Must be "%s" or "%s".',
                AbstractShopGateway::ENCODING_UTF8,
                AbstractShopGateway::ENCODING_ISO8859
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
     * The payment clearing type.
     * See AbstractShopGateway::CLEARING_TYPE_* for permitted values.
     */
    public function setClearingType($value)
    {
        return $this->setParameter('clearingtype', $value);
    }

    public function getClearingType()
    {
        return $this->getParameter('clearingtype');
    }
}
