<?php

namespace Omnipay\Payone\Message;

/**
 * Authorize, shop mode, client payment gateway (AJAX card tokens or redirect).
 */

use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Common\Currency;

class ShopClientAuthorizeRequest extends ShopServerAuthorizeRequest
{
    /**
     * The Response Type is always needed.
     */
    public function setResponseType($value)
    {
        return $this->setParameter('responseType', $value);
    }

    public function getResponseType()
    {
        return $this->getParameter('responseType');
    }

    protected function getBaseData()
    {
        return array(
            'mid' => $this->getMerchantId(),
            'portalid' => $this->getPortalId(),
            'api_version' => AbstractShopGateway::API_VERSION,
            'mode' => $this->getTestMode()
                ? AbstractShopGateway::MODE_TEST
                : AbstractShopGateway::MODE_LIVE,
            'request' => $this->getRequestCode(),
            'responsetype' => $this->getResponseType(),
            'encoding' => $this->getEncoding(),
        );
    }

    /**
     * The data is used to generate the POST form to send the user
     * off to the PAYONE credit card form.
     */
    public function getData()
    {
        // The base data.
        $data = $this->getBaseData();

        // The errorurl does NOT appear in the Frontend documentation, but does
        // work and is implemented in other platform gateways.

        $data = array_merge($data, $this->getDataUrl());

        $data['aid'] = $this->getSubAccountId();
        $data['clearingtype'] = $this->getClearingType();
        $data['reference'] = $this->getTransactionId();
        $data['amount'] = $this->getAmountInteger();
        $data['currency'] = $this->getCurrency();

        if ($this->getEcommerceMode()) {
            $data['ecommercemode'] = $this->getEcommerceMode();
        }

        if ($this->getParam() !== null) {
            $data['param'] = $this->getParam();
        }

        if ($this->getDescription()) {
            $data['narrative_text'] = $this->getDescription();
        }

        if ($this->getInvoiceId()) {
            $data['invoiceid'] = $this->getInvoiceId();
        }

        // Add in any cart items.
        $data = array_merge($data, $this->getDataItems());

        // Note that "lastname" and "country" are mandatory, so card details with
        // a lastname and country must be supplied at an absolute minimum.

        if ($card = $this->getCard()) {
            $data['firstname'] = $card->getFirstName();
            $data['lastname'] = $card->getLastName(); // Mandatory
            $data['company'] = $card->getCompany();
            $data['street'] = $card->getBillingAddress1();
            $data['zip'] = $card->getBillingPostcode();
            $data['city'] = $card->getBillingCity();
            $data['country'] = $card->getBillingCountry(); // Mandatory
            $data['email'] = $card->getEmail();

            // Stuff the card data away for later use, but don't merge its
            // data into the main hidden-fields data.
            $card_data = $this->getDataCard();
            $data['card'] = $card_data;
        }

        if ($this->getFinancingtype()) {
            $data['financingtype'] = $this->getFinancingtype();
        }

        // Create the hash for the hashable fields.
        $data['hash'] = $this->hashArray($data);

        return $data;
    }

    /**
     * Sending the data is a simple pass-through. However:
     * Whether using the AJAX or REDIRECT response type, the client needs the
     * POST data for adding to the form. Some of that data will be supplied in
     * hidden fields and be hash-protected. Some of that data will be user-
     * enterable, and so not included in the hash. Some non-hashed fields are
     * still mandatory, depending on the request type.
     */
    public function sendData($data)
    {
        // Filter out all fields but the hashable hidden fields.
        // But do add in the hash that has already been calculated.
        // CHECKME: some fields, such as 'financingtype', may still need to be
        // included, but don't need to be a part of the hash. Should we just let
        // them all through?

        $data = array_merge(
            $this->filterHashFields($data),
            array('hash' => $data['hash'])
        );

        return $this->createResponse($data);
    }


    /**
     *
     */
    protected function createResponse($data)
    {
        return $this->response = new ShopClientAuthorizeResponse($this, $data);
    }
}
