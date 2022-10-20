<?php

namespace Omnipay\Payone\Extend;

use Money\Currency;
use Money\Money;
use Omnipay\Tests\TestCase;

class ItemTest extends TestCase
{
    public function testPriceConversion()
    {
        // The format of the item price is not defined in Omnipay,
        // so test a number of automatic conversions, based on
        // assumptions of what is supplied.

        // Integer.
        $this->assertSame(123, Item::convertPriceInteger(123));
        $this->assertSame(123, Item::convertPriceInteger('123', 'EUR'));

        // Float.
        $this->assertSame(12300, Item::convertPriceInteger(123.0, 'EUR'));
        $this->assertSame(12300, Item::convertPriceInteger('123.0', 'EUR'));

        // Other currencies: 0, 2 and 3 decimal digits.
        $this->assertSame(123, Item::convertPriceInteger('123.0', 'JPY'));
        $this->assertSame(12300, Item::convertPriceInteger('123.0', 'EUR'));
        $this->assertSame(123000, Item::convertPriceInteger('123.0', 'IQD'));

        // Money.
        $this->assertSame(123, Item::convertPriceInteger(Money::EUR(123)));
        $this->assertSame(12345, Item::convertPriceInteger('123.45', new Currency('EUR')));
    }

    public function testParams()
    {
        // Parameters brought in by this extended item.

        $item = new Item([
            'price' => 123.0,
            'vat' => 20,
            'itemType' => Item::ITEM_TYPE_SHIPMENT,
            'id' => 'whoopee',
        ]);

        $this->assertSame(12300, $item->getPriceInteger('USD'));
        $this->assertSame(20, $item->getVat());
        $this->assertSame('shipment', $item->getItemType());
        $this->assertSame('whoopee', $item->getId());
    }
}
