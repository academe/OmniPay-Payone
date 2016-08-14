<?php

namespace Omnipay\Payone\Message;

/**
 * PAYONE Abstract Request.
 */

use Omnipay\Common\Message\AbstractResponse as OmnipayAbstractResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Omnipay;
use Guzzle\Http\Url;

abstract class AbstractResponse extends OmnipayAbstractResponse
{
    /**
     * Status codes shared by all responses.
     */
    const STATUS_APPROVED   = 'APPROVED';
    const STATUS_REDIRECT   = 'REDIRECT';
    const STATUS_PENDING    = 'PENDING';
    const STATUS_ERROR      = 'ERROR';

    /**
     * Get a data item, or default if not present.
     */
    protected function getDataItem($name, $default = null)
    {
        if (!isset($this->data)) {
            $this->data = $this->getData();
        }

        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    /**
     * Response Message - the system message for logging only.
     *
     * @return null|string A response message from the payment gateway
     */
    public function getMessage()
    {
        return isset($this->data['errormessage']) ? $this->data['errormessage'] : null;
    }

    /**
     * Response Message - suitable for putting in front of an end user.
     *
     * @return null|string A response message from the payment gateway
     */
    public function getCustomerMessage()
    {
        return isset($this->data['customermessage']) ? $this->data['customermessage'] : null;
    }

    /**
     * Response code
     *
     * @return null|string A response code from the payment gateway
     */
    public function getCode()
    {
        return isset($this->data['errorcode']) ? $this->data['errorcode'] : null;
    }

    /**
     * Raw status from the gateway.
     * For authorize: APPROVED / REDIRECT / ERROR / PENDING
     *
     * @return null|string A response code from the payment gateway
     */
    public function getStatus()
    {
        return isset($this->data['status']) ? $this->data['status'] : null;
    }

    /**
     * Indicates whether any kind of system error was returned.
     * No other data (apart from messages) can be trusted if the
     * transaction is in error.
     */
    public function hasError()
    {
        $status = $this->getStatus();

        return $status == static::STATUS_ERROR || empty($status);
    }
}
