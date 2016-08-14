<?php

namespace Omnipay\Payone\Message;

/**
 * Authorize, shop mode, classic payment page (user is sent to
 * the PAYONE site).
 */

use Omnipay\Payone\Extend\ItemInterface as ExtendItemInterface;
use Omnipay\Payone\Extend\Item as ExtendItem;
use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Common\Currency;
use Omnipay\Common\ItemBag;

class ShopFrontendAuthorizeRequest extends ShopAuthorizeRequest
{
    /**
     * Required access method to the ONEPAY credit card form.
     */
    const ACCESS_METHOD_CLASSIC = 'classic';
    const ACCESS_METHOD_IFRAME = 'iframe';

    const ENDPOINT_CLASSIC = 'https://secure.pay1.de/frontend/';
    const ENDPOINT_IFRAME = 'https://frontend.pay1.de/frontend/v2/';

    /**
     * Permitted values for targetWindow.
     * This is for breaking out of the iframe.
     */
    const TARGET_WINDOW_WINDOW  = 'window';
    const TARGET_WINDOW_OPENER  = 'opener';
    const TARGET_WINDOW_TOP     = 'top';
    const TARGET_WINDOW_PARENT  = 'parent';
    const TARGET_WINDOW_BLANK   = 'blank';
    const TARGET_WINDOW_SELF    = 'self';

    /**
     * Default values for the auto-created Item if none are supplied.
     * If you don't want to use these defaults, then make sure you always
     * pass an ItemBag into the transaction.
     */
    protected $defaultItemId = '000000';
    protected $defaultItemName = 'Payment';

    /**
     * The data is used to generate the POST form to send the user
     * off to the PAYONE credit card form.
     */
    public function getData()
    {
        // The base data.
        $data = [
            'portalid' => $this->getPortalId(),
            'aid' => $this->getSubAccountId(),
            'api_version' => AbstractShopGateway::API_VERSION,
            'mode' => $this->getTestMode()
                ? AbstractShopGateway::MODE_TEST
                : AbstractShopGateway::MODE_LIVE,
            'request' => $this->getRequestCode(),
            'clearingtype' => $this->getClearingType(),
            'reference' => $this->getTransactionId(),
            'amount' => $this->getAmountInteger(),
            'currency' => $this->getCurrency(),
            'encoding' => $this->getEncoding(),
        ];

        // Add basket contents next.
        // It seems that we MUST have at least one item in
        // the cart to be valid.

        $items = $this->getItems();

        if (empty($items) || $items->count() == 0) {
            // No items in the basket, so we will have to make
            // one up. The Frontend API MUST have at least one cart item.
            // The basket MUST add up to the total payment amount, so
            // be aware of that.

            $item = new ExtendItem([
                'id' => $this->defaultItemId,
                'price' => $this->getAmountInteger(),
                'quantity' => 1,
                'name' => $this->defaultItemName,
                'vat' => null,
            ]);

            $items = new ItemBag;
            $items->add($item);

            // Add this dummy cart to the gateway cart.
            $this->setItems($items);
        }

        // Add the cart items to the data.
        $data += $this->getDataItems();

        if ($this->getDisplayName()) {
            $data['display_name'] = $this->getDisplayName();
        }

        if ($this->getDisplayAddress()) {
            $data['display_address'] = $this->getDisplayAddress();
        }

        if ($this->getInvoiceId()) {
            $data['invoiceid'] = $this->getInvoiceId();
        }

        // The errorurl does NOT appear in the Frontend documentation, but does
        // work and is implemented in other platform gateways.

        $data += $this->getDataUrl();

        if ($this->getTargetWindow()) {
            $data['targetwindow'] = $this->getTargetWindow();
        }

        // Create the hash.
        // All data collected so far must be "protected" by the hash.
        $data['hash'] = $this->doHash($data, $this->getPortalKey());

        // Some fields are added after the hash.

        if ($card = $this->getCard()) {
            $data['firstname'] = $card->getFirstName();
            $data['lastname'] = $card->getLastName();
            $data['company'] = $card->getCompany();
            $data['street'] = $card->getBillingAddress1();
            $data['zip'] = $card->getBillingPostcode();
            $data['city'] = $card->getBillingCity();
            $data['country'] = $card->getBillingCountry();
            $data['email'] = $card->getEmail();
        }

        if ($this->getLanguage()) {
            $data['language'] = $this->getLanguage();
        }

        return $data;
    }

    /**
     * The response to sending the request is a text list of name=value pairs.
     * The output data is a mix of the sent data with the received data appended.
     */
    public function sendData($data)
    {
        return $this->createResponse($data);
    }

    /**
     * There are a number of options in accessing the credit card form, including
     * a GET redirect and a POST to an iframe.
     */
    protected function createResponse($data)
    {
        $this->response = new ShopFrontendAuthorizeResponse($this, $data);

        if ($this->getAccessMethod() == static::ACCESS_METHOD_CLASSIC) {
            $this->response->setEndpoint(static::ENDPOINT_CLASSIC);
        }

        if ($this->getAccessMethod() == static::ACCESS_METHOD_IFRAME) {
            $this->response->setEndpoint(static::ENDPOINT_IFRAME);
        }

        $this->response->setRedirectMethod($this->getRedirectMethod());

        return $this->response;
    }

    /**
     * Access method: classic or iframe
     */
    public function setAccessMethod($value)
    {
        $this->setParameter('accessMethod', $value);
    }

    public function getAccessMethod()
    {
        return $this->getParameter('accessMethod') ?: static::ACCESS_METHOD_CLASSIC;
    }

    /**
     * Indicates whether to display the firstname/lastname/company name fields in the
     * hosted form.
     * Values are "yes" and "no".
     */
    public function setDisplayName($value)
    {
        if ($value === true) {
            $value = 'yes';
        } elseif ($value === false) {
            $value = 'no';
        }

        $this->setParameter('displayName', $value);
    }

    public function getDisplayName()
    {
        return $this->getParameter('displayName');
    }

    /**
     * Indicates whether to display the address fields in the
     * hosted form.
     * Values are "yes" and "no".
     */
    public function setDisplayAddress($value)
    {
        if ($value === true) {
            $value = 'yes';
        } elseif ($value === false) {
            $value = 'no';
        }

        $this->setParameter('displayAddress', $value);
    }

    public function getDisplayAddress()
    {
        return $this->getParameter('displayAddress');
    }

    /**
     * The target window for breaking out of the iframe at the end.
     * See constants static::TARGET_WINDOW_* for permitted values.
     * Defaults to 'window'.
     */
    public function setTargetWindow($value)
    {
        $this->setParameter('targetWindow', $value);
    }

    public function getTargetWindow()
    {
        return $this->getParameter('targetWindow');
    }

    /**
     * Redirect method: GET or POST.
     */
    public function setRedirectMethod($value)
    {
        if ($value != 'GET' && $value != 'POST') {
            // TODO: exception
        }

        $this->setParameter('redirectMethod', $value);
    }

    public function getRedirectMethod()
    {
        return $this->getParameter('redirectMethod') ?: 'GET';
    }
}
