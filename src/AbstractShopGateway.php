<?php

namespace Omnipay\Payone;

/**
 * PAYONE Shop (single payments) driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\AbstractGateway;

use Omnipay\Payone\Traits\HasGatewayParams;

abstract class AbstractShopGateway extends AbstractGateway
{
    use HasGatewayParams;

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
     *
     * 3.9 API from 2015-01-05
     * 3.10 API from 2016-06-01
     * 3.11 API from 2018-02-01
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

    public function setWalletType($value)
    {
        return $this->setParameter('wallettype', $value);
    }

    public function getWalletType()
    {
        return $this->getParameter('wallettype');
    }
}
