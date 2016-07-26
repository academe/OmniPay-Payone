<?php

namespace Omnipay\Payone;

/**
 * ONEPAY Shop (single payments) Client (CC detailshashing form, local or iframe)
 * driver for Omnipay
 */

use Omnipay\Common\Exception\InvalidRequestException;

class ShopClientGateway extends AbstractShopGateway
{
    protected $endpoint = 'https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js';

    public function getName()
    {
        return 'PAYONE Shop Client';
    }
}
