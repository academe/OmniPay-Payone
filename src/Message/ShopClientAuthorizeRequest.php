<?php

namespace Omnipay\Payone\Message;

/**
 * Authorize, shop mode, client payment gateway (AJAX card tokens or redirect).
 */

use Omnipay\Payone\Extend\ItemInterface as ExtendItemInterface;
use Omnipay\Payone\Extend\Item as ExtendItem;
use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Payone\ShopClientGateway;
use Omnipay\Common\Currency;
use Omnipay\Common\ItemBag;

class ShopClientAuthorizeRequest extends ShopAuthorizeRequest
{
    /**
     * The mode determines whether a server-to-server call is made, or
     * whether the AJAX will be handled on the client.
     */
    protected $mode_server = false;

    /**
     * The Response Type is always needed.
     */
    public function setModeServer($value)
    {
        return $this->mode_server = (bool)$value;;
    }

    public function getModeServer()
    {
        return $this->mode_server;
    }

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

    /**
     * The data is used to generate the POST form to send the user
     * off to the PAYONE credit card form.
     */
    public function getData()
    {
        // The base data.
        $data = [
            'mid' => $this->getMerchantId(),
            'portalid' => $this->getPortalId(),
            'api_version' => AbstractShopGateway::API_VERSION,
            'mode' => $this->getTestMode()
                ? AbstractShopGateway::MODE_TEST
                : AbstractShopGateway::MODE_LIVE,
            'request' => $this->getRequestCode(),
            'responsetype' => $this->getResponseType(),
            'encoding' => $this->getEncoding(),
        ];

        // The errorurl does NOT appear in the Frontend documentation, but does
        // work and is implemented in other platform gateways.

        $data += $this->getDataUrl();

        if ($this->getSubAccountId()) {
            $data['aid'] = $this->getSubAccountId();
        }

        if ($this->getClearingType()) {
            $data['clearingtype'] = $this->getClearingType();
        }

        if ($this->getTransactionId()) {
            $data['reference'] = $this->getTransactionId();
        }

        if ($this->getAmountInteger()) {
            $data['amount'] = $this->getAmountInteger();
        }

        if ($this->getCurrency()) {
            $data['currency'] = $this->getCurrency();
        }

        if ($this->getEcommerceMode()) {
            $data['ecommercemode'] = $this->getEcommerceMode();
        }

        // Add in any cart items.
        $data += $this->getDataItems();

        // Create the hash.
        // All data collected so far must be "protected" by the hash.
        $data['hash'] = $this->doHash($data, $this->getPortalKey());

        // Some fields are added after the hash.

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

        return $data;
    }

    /**
     * Sending the data is a simple pass-through.
     * When using the JSON response type, we can either pass the raw data to the
     * response object to then be used to generate the payment form which submits
     * direct to remote gateway, or we can make a direct JSON call and get the
     * result immediately. Use the latter only for testing or for passing a
     * pseudocardpan in place of full card details to stay out of PCI requirements
     * as much as possible.
     */
    public function sendData($data)
    {
        if ($this->getModeServer() && $this->getResponseType() === ShopClientGateway::RETURN_TYPE_JSON) {
            // Move the card details into the data to be sent.
            $data = $data + $data['card'];
            unset($data['card']);

            $httpRequest = $this->httpClient->post($this->getEndpoint(), null, $data);
            // CURL_SSLVERSION_TLSv1_2 for libcurl < 7.35
            $httpRequest->getCurlOptions()->set(CURLOPT_SSLVERSION, 6);
            $httpResponse = $httpRequest->send();

            $body = (string)$httpResponse->getBody();

            $data = json_decode($body, true);
        }

        return $this->createResponse($data);
    }


    /**
     * Whether using the AJAX or REDIRECT response type, the client needs the
     * POST data for adding to the form. Some of that data will be supplied in
     * hidden fields and be hash-protected. Some of that data will be user-
     * enterable, and so not included in the hash. Some non-hashed fields are
     * still mandatory, depending on the request type.
     * Some responses may require a REDIRECT if 3D Secure is enabled, so that
     * needs to be dealt with in the complete* messages. A redirect may be needed
     * for AJAX, but will be a part of the original POST for the REDIRECT method.
     */
    protected function createResponse($data)
    {
        if ($this->getModeServer() && $this->getResponseType() === ShopClientGateway::RETURN_TYPE_JSON) {
            // A server-to-server call has been made.
            return $this->response = new ShopClientCompleteAuthorizeResponse($this, $data);
        } else {
            return $this->response = new ShopClientAuthorizeResponse($this, $data);
        }
    }
}
