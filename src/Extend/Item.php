<?php

namespace Omnipay\Payone\Extend;

/**
 * Extends the Item class to support properties
 * required by PAYONE.
 */

use Omnipay\Common\Item as CommonItem;

class Item extends CommonItem
{
   /**
     * {@inheritDoc}
     */
    public function getVat()
    {
        return $this->getParameter('vat');
    }

    /**
     * Set the item name
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
     * Set the item name
     */
    public function setId($value)
    {
        return $this->setParameter('id', $value);
    }
}
