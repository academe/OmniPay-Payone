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
     * Whether to exit immediately on responding.
     */
    protected $exit_on_response = true;

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
        if ($this->isSuccessful()) {
            echo $this->responseMessage . "\n";
        }

        if ($exit) {
            exit;
        }
    }

    /**
     * Added for consistency with Sage Pay Server.
     * The nextUrl and detail message are not used.
     */
    public function accept($nextUrl = null, $detail = null)
    {
        $this->acknowledge($this->exit_on_response);
    }

    /**
     * Added for consistency with Sage Pay Server.
     * The nextUrl and detail message are not used.
     */
    public function reject($nextUrl = null, $detail = null)
    {
        // Don't output anything - just exit.
        // The gateway will treat that as a non-acceptance, but will try
        // to send the notification multiple times.

        if ($this->exit_on_response) {
            exit;
        }
    }

    /**
     * Set or reset flag to exit immediately on responding.
     * Switch auto-exit off if you have further processing to do.
     *
     * @param boolean true to exit; false to not exit.
     */
    public function setExitOnResponse($value)
    {
        $this->exit_on_response = (bool)$value;
    }

    /**
     * Alias of acknowledge as a more consistent OmniPay lexicon.
     */
    public function send($exit = true)
    {
        return $this->acknowledge($exit);
    }
}
