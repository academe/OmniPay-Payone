<?php

namespace Omnipay\Payone\Message;

/**
 * Check and tokenise credit card details, shop mode, client payment gateway.
 */

use Omnipay\Payone\ShopClientGateway;

class ShopClientCardCheckRequest extends ShopClientAuthorizeRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'creditcardcheck';

    public function getData()
    {
        // The base data.
        $data = $this->getBaseData();

        $data['aid'] = $this->getSubAccountId();

        $data['responsetype'] = ShopClientGateway::RETURN_TYPE_JSON;
        $data['storecarddata'] = 'yes';

        // Create the hash for the hashable fields.
        $data['hash'] = $this->hashArray($data);

        return $data;
    }

    /**
     *
     */
    protected function createResponse($data)
    {
        return $this->response = new ShopClientCardCheckResponse($this, $data);
    }
}
