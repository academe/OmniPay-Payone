<?php

namespace Omnipay\Payone\Message;

/**
 * Acknowledge the incoming Transaction Status message from ONEPAY.
 */
 
use Omnipay\Common\Message\AbstractResponse as OmnipayAbstractResponse;
use Omnipay\Common\Exception\InvalidResponseException;

class ShopCompleteTxnStatusServerResponse extends OmnipayAbstractResponse
{
    protected $responseMessage = 'TSOK';

    /**
     * This method is in the interface for the response message, but is really
     * not much use when this is a ServerResponse.
     */
    public function isSuccessful()
    {
        return true;
    }

    /**
     * Acknowledge the receipt of the Transaction Status details.
     * PAYONE expects just a simple string and nothing else.
     */
    public function acknowledge($exit = true)
    {
        echo $this->responseMessage;

        if ($exit) {
            exit;
        }
    }
}
