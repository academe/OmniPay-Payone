<?php

namespace Omnipay\Payone\Message;

/**
 * Acknowledge the incoming Transaction Status message from ONEPAY.
 */
 
use Omnipay\Common\Message\AbstractResponse as OmnipayAbstractResponse;
use Omnipay\Common\Exception\InvalidResponseException;

class ShopTransactionStatusServerResponse extends OmnipayAbstractResponse
{
    protected $responseMessage = 'TSOK';

    /**
     * This method checks on the success of the hash verification on the
     * status message from ONEPAY. It does not reflect on whether the
     * transaction was authorised or not.
     */
    public function isSuccessful()
    {
        return $this->request->isValid();
    }

    /**
     * Acknowledge the receipt of the Transaction Status details.
     * PAYONE expects just a simple string and nothing else.
     */
    public function acknowledge($exit = true)
    {
        // Only send the OK message if the hash has been successfuly verified.
        if (isSuccessful()) {
            echo $this->responseMessage . "\n";
        }

        if ($exit) {
            exit;
        }
    }

    /**
     * Alias of acknowledge as a more consistent OmniPay lexicon.
     */
    public function send($exit = true)
    {
        return $this->acknowledge($exit);
    }
}
