<?php

namespace Omnipay\Payone\Extend;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\DecimalMoneyParser;
use Omnipay\Common\Item as CommonItem;

/**
 * Extends the Item class to support properties
 * required by PAYONE.
 */
class Item extends CommonItem implements ItemInterface
{
    /**
     * {@inheritDoc}
     */
    public function getVat()
    {
        return $this->getParameter('vat');
    }

    /**
     * {@inheritDoc}
     */
    public function setVat($value)
    {
        return $this->setParameter('vat', $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->getParameter('id');
    }

    /**
     * {@inheritDoc}
     */
    public function setItemType($value)
    {
        return $this->setParameter('itemType', $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getItemType()
    {
        return $this->getParameter('itemType');
    }

    /**
     * {@inheritDoc}
     */
    public function setId($value)
    {
        return $this->setParameter('id', $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceInteger($currency = null)
    {
        return static::convertPriceInteger($this->getPrice(), $currency);
    }

    /**
     * Return a price as minor unit integer, naking some assumptioms:
     * - If $price is an integer, assume it already is minor units.
     * - If a float, then assume it is major units.
     * - If a string with a decimal point in, then treat it like a float.
     *
     * @param int|float|string|Money The amount to convert to an integer minor units
     * @param string|Currency The currency to use if converting from a string or float
     * @return int The minor units
     */
    public static function convertPriceInteger($price, $currency = null)
    {
        // An integer provided as the price, so assume it is for minor units.

        if (is_int($price)) {
            return $price;
        }

        // An integer in a string, so assume it referesents minor units.

        if (is_string($price) && strpos($price, '.') === false) {
            $price = (int) $price;
        }

        // A float or a decimal in a string are assumed to be major units.
        // We need the currency to convert that.

        if (is_float($price)) {
            // Convert to a string for parsing.
            // Four decimal digits should be enough for all currencies.
            // Three decimal digits is the maximum in use today.

            $price = number_format($price, 4, '.', '');
        }

        if (is_string($price) && strpos($price, '.') !== false) {
            // Parse the string.
            // Currency is required for this to succeed.

            $currencies = new ISOCurrencies();
            $moneyParser = new DecimalMoneyParser($currencies);

            if (! ($currency instanceof Currency)) {
                $currency = new Currency((string) $currency);
            }

            $price = $moneyParser->parse($price, $currency);
        }

        // A money object supplied. We should use this all the time.

        if ($price instanceof Money) {
            return (int) $price->getAmount();
        }

        // Don't know what to do with it.

        return $price;
    }
}
