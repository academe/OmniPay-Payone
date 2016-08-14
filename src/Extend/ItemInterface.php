<?php

namespace Omnipay\Payone\Extend;

/**
 * Extends the Item class to support properties
 * required by PAYONE.
 */

use Omnipay\Common\ItemInterface as CommonItemInterface;

interface ItemInterface extends CommonItemInterface
{
    /**
     * Allowed Item Types.
     */

    // For BSV/KLV/KLS financing type and PPE wallet type
    const ITEM_TYPE_GOODS = 'goods';
    const ITEM_TYPE_SHIPMENT = 'shipment';
    const ITEM_TYPE_HANDLING = 'handling';

    // For BSV/KLV/KLS financing type
    const ITEM_TYPE_VOUCHER = 'voucher';

    /**
     * Set the item VAT.
     * See notes on PAYONE site for usage (values <100 and >100 have different meanings).
     * value < 100 = percent; value > 99 = basis points
     */
    public function setVat($value);

    /**
     * Get the item VAT.
     */
    public function getVat();

    /**
     * Set the item stock ID
     * Permitted characters: 0-9 a-z A-Z ()[]{} +-_#/:
     */
    public function setId($value);

   /**
    * The stock item ID.
    */
    public function getId();

   /**
    * The item type, for PPE.
    */
    public function setItemType($value);

   /**
    * The item type, for PPE.
    */
    public function getItemType();

    /**
     * Get the price in minor units, making some assumptions.
     */
    public function getPriceInteger();
}
