<?php

namespace Omnipay\Payone\Message;

/**
* PAYONE Shop Authorize Request
*/

class ShopCaptureRequest extends AbstractRequest
{
    /**
     * The "request" parameter.
     */
    protected $request_code = 'capture';

    /**
     * Collect the data together to send to the Gateway.
     */
    public function getData()
    {
        $data = $this->getBaseData();

        $data['txid'] = $this->getTransactionReference();

        $sequence_number = $this->getSequenceNumber();
        if (isset($sequence_number)) {
            $data['sequencenumber'] = $sequence_number;
        }

        // Amount is in minor units.
        $data['amount'] = $this->getAmountInteger();

        // Currency is (i.e. has to be) ISO 4217
        $data['currency'] = $this->getCurrency();

        if ($this->getDescription()) {
            $data['narrative_text'] = substr($this->getDescription(), 0, 80);
        }

        return $data;
    }

    protected function createResponse($data)
    {
        return $this->response = new ShopCaptureResponse($this, $data);
    }

    /**
     * The sequence number is used to capture the total in smaller amounts.
     */
    public function setSequenceNumber($sequenceNumber)
    {
        if (!is_numeric($sequenceNumber)) {
            throw new InvalidRequestException('Sequence Number must be numeric.');
        }

        return $this->setParameter('sequenceNumber', $sequenceNumber);
    }

    public function getSequenceNumber()
    {
        return $this->getParameter('sequenceNumber');
    }

}
