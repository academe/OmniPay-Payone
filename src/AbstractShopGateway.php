<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\AbstractGateway;

abstract class AbstractShopGateway extends AbstractGateway
{
    /**
     * Mode enumeration.
     */
    const MODE_TEST = 'test';
    const MODE_LIVE = 'live';

    /**
     * API encoding.
     */
    const ENCODING_ISO8859 = 'ISO-8859-1';
    const ENCODING_UTF8 = 'UTF-8';

    /**
     * Hash type to use.
     */
    const HASH_MD5 = 'MD5';
    const HASH_SHA2_384 = 'SHA2_384';

    /**
     * Clearing type values.
     */
    // Debit payment
    const CLEARING_TYPE_ELV = 'elv';
    // Credit card
    const CLEARING_TYPE_CC  = 'cc';
    // Prepayment
    const CLEARING_TYPE_VOR = 'vor';
    // Invoice
    const CLEARING_TYPE_REC = 'rec';
    // Cash on delivery
    const CLEARING_TYPE_COD = 'cod';
    // Online bank transfer
    const CLEARING_TYPE_SB  = 'sb';
    // e-Wallet
    const CLEARING_TYPE_WLT = 'wlt';
    // Financing
    const CLEARING_TYPE_FNC = 'fnc';

    /**
     * The common financing types.
     */

    // BillSAFE Invoice
    const FINANCING_TYPE_BSV = 'BSV';
    // Klarna Invoice
    const FINANCING_TYPE_KLV = 'KLV';
    // Klarna installment
    const FINANCING_TYPE_KLS = 'KLS';

    /**
     * The wallet type is used with clearing type CLEARING_TYPE_WLT
     */
    // PPE = PayPal Express
    const WALLET_TYPE_PPE = 'PPE';

    /**
     * The default server endpoint.
     */
    protected $endpoint = 'https://api.pay1.de/post-gateway/';

    /**
     * The API version supported.
     */
    const API_VERSION = '3.9';

    /**
     * merchantId = Merchant account ID (mid: N..6)
     * portalId = Payment portal ID (portalid: N..7)
     * portalKey = Payment portal key (not encoded as MD5 value)
     * language is ISO 639
     */
    public function getDefaultParameters()
    {
        return array(
            // Required
            'merchantId' => 0,
            'portalId' => 0,
            'subAccountId' => 0,
            'portalKey' => '',
            // Optional
            'testMode' => false,
            'encoding' => array(
                static::ENCODING_UTF8,
                static::ENCODING_ISO8859
            ),
            'language' => 'en',
            'endpoint' => $this->endpoint,
            'hashMethod' => array(
                static::HASH_MD5,
                static::HASH_SHA2_384,
            ),
            'clearingType' => array(
                static::CLEARING_TYPE_CC,
                static::CLEARING_TYPE_ELV,
                static::CLEARING_TYPE_VOR,
                static::CLEARING_TYPE_REC,
                static::CLEARING_TYPE_COD,
                static::CLEARING_TYPE_SB,
                static::CLEARING_TYPE_WLT,
                static::CLEARING_TYPE_FNC,
            ),
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
     * The payment clearing type.
     * See self::CLEARING_TYPE_* for permitted values.
     */
    public function setClearingType($value)
    {
        return $this->setParameter('clearingtype', $value);
    }

    public function getClearingType()
    {
        return $this->getParameter('clearingtype');
    }

    public function setWalletType($value)
    {
        return $this->setParameter('wallettype', $value);
    }

    public function getWalletType()
    {
        return $this->getParameter('wallettype');
    }
}
