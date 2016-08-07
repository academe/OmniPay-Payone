<?php

namespace Omnipay\Payone;

use Omnipay\Tests\GatewayTestCase;

class ShopServerGatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new ShopServerGateway($this->getHttpClient(), $this->getHttpRequest());

        $this->options = array(
            'merchantId' => 12345678,
            'subAccountId' => 12345,
            'amount' => '10.00',
            'card' => $this->getValidCard(),
        );

        $this->purchaseOptions = array(
            'amount' => '10.00',
            'transactionId' => '123',
            'card' => $this->getValidCard(),
        );

        $this->captureOptions = array(
            'amount' => '10.00',
            'transactionId' => '123',
            'transactionReference' => '????',
        );
    }

    /**
     * Generate a random value for a gateway parameter.
     * The value generated will depend on the data type of
     * the default parameter value.
     *
     * @parame mixed $default
     * @return mixed
     */
    protected function makeUnique($default)
    {
        switch (gettype($default)) {
            case 'integer':
                $value = rand(100000, 999999);
                break;

            case 'array':
                $value = $default[array_rand($default)];
                break;

            case 'string':
            default:
                $value = uniqid();
                break;
        }

        return $value;
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testDefaultParametersHaveMatchingMethods()
    {
        $settings = $this->gateway->getDefaultParameters();

        foreach ($settings as $key => $default) {
            $getter = 'get'.ucfirst($key);
            $setter = 'set'.ucfirst($key);
            $value = $this->makeUnique($default);
            $this->assertTrue(method_exists($this->gateway, $getter), "Gateway must implement $getter()");
            $this->assertTrue(method_exists($this->gateway, $setter), "Gateway must implement $setter()");

            // setter must return instance
            $this->assertSame($this->gateway, $this->gateway->$setter($value));
            $this->assertSame($value, $this->gateway->$getter());
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testAuthorizeParameters()
    {
        if ($this->gateway->supportsAuthorize()) {
            foreach ($this->gateway->getDefaultParameters() as $key => $default) {
                // set property on gateway
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $value = $this->makeUnique($default);
                $this->gateway->$setter($value);

                // request should have matching property, with correct value
                $request = $this->gateway->authorize();
                $this->assertSame($value, $request->$getter());
            }
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testCaptureParameters()
    {
        if ($this->gateway->supportsCapture()) {
            foreach ($this->gateway->getDefaultParameters() as $key => $default) {
                // set property on gateway
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $value = $this->makeUnique($default);
                $this->gateway->$setter($value);

                // request should have matching property, with correct value
                $request = $this->gateway->capture();
                $this->assertSame($value, $request->$getter());
            }
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testPurchaseParameters()
    {
        foreach ($this->gateway->getDefaultParameters() as $key => $default) {
            // set property on gateway
            $getter = 'get'.ucfirst($key);
            $setter = 'set'.ucfirst($key);
            $value = $this->makeUnique($default);
            $this->gateway->$setter($value);

            // request should have matching property, with correct value
            $request = $this->gateway->purchase();
            $this->assertSame($value, $request->$getter());
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testVoidParameters()
    {
        if ($this->gateway->supportsVoid()) {
            foreach ($this->gateway->getDefaultParameters() as $key => $default) {
                // set property on gateway
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $value = $this->makeUnique($default);
                $this->gateway->$setter($value);

                // request should have matching property, with correct value
                $request = $this->gateway->void();
                $this->assertSame($value, $request->$getter());
            }
        }
    }


    public function testAuthorizeSuccess()
    {
        // TransactionReference = 196569999
        $this->setMockHttpResponse('ShopAuthorizeSuccess.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect()); // TODO: Need to ensure 3DSecure is turned off
        $this->assertSame('196569999', $response->getTransactionReference());
    }

    public function testPurchaseSuccess()
    {
        // TransactionReference = 196569999
        $this->setMockHttpResponse('ShopPurchaseSuccess.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect()); // TODO: Need to ensure 3DSecure is turned off
        $this->assertSame('196569999', $response->getTransactionReference());
    }

    public function testAuthorizeFailure()
    {
        $this->setMockHttpResponse('ShopAuthorizeFailure.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('33', $response->getCode());
        $this->assertSame('Expiry date invalid, incorrect or in the past', $response->getMessage());
        $this->assertSame('Invalid card expiry date. Please verify your card data.', $response->getCustomerMessage());
        $this->assertNull($response->getTransactionReference());
    }

    public function testPurchaseFailure()
    {
        $this->setMockHttpResponse('ShopPurchaseFailure.txt');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('33', $response->getCode());
        $this->assertSame('Expiry date invalid, incorrect or in the past', $response->getMessage());
        $this->assertSame('Invalid card expiry date. Please verify your card data.', $response->getCustomerMessage());
        $this->assertNull($response->getTransactionReference());
    }
}
