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
     * Set the item VAT.
     * See notes on PAYONE site for usage (values <100 and >100 have different meanings).
     * value < 100 = percent; value > 99 = basis points
     */
    public function setVat($value)
    {
        return $this->setParameter('vat', $value);
    }

   /**
    * The stock item ID.
    */
    public function getId()
    {
        return $this->getParameter('id');
    }

    /**
     * Set the item stock ID
     * Permitted characters: 0-9 a-z A-Z ()[]{} +-_#/:
     */
    public function setId($value)
    {
        return $this->setParameter('id', $value);
    }
}
