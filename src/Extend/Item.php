<?php

namespace Omnipay\Payone\Extend;

/**
 * Extends the Item class to support properties
 * required by PAYONE.
 */

use Omnipay\Common\Item as CommonItem;

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
    public function getPriceInteger($currency_digits = 2)
    {
        return static::convertPriceInteger($this->getPrice(), $currency_digits);
    }

    /**
     * Return a price as minor unit integer, naking some assumptioms:
     * - If $price is an integer, assume it already is minor units.
     * - If a float, then assume it is major units.
     * - If a string with a decimal point in, then assume it is major units.
     */
    public static function convertPriceInteger($price, $currency_digits = 2)
    {
        if (is_string($price) && strpos($price, '.') !== false) {
            $price = (float)$price;
        }

        if (is_string($price) && strpos($price, '.') === false) {
            $price = (integer)$price;
        }

        if (is_integer($price)) {
            return $price;
        }

        if (is_float($price)) {
            return $price * pow(10, $currency_digits);
        }

        // Don't know what to do with it.
        return $price;
    }
}
