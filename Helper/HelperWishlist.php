<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_ConfiguableGridView
 * @author     Extension Team
 * @copyright  Copyright (c) 2018-2020 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\ConfiguableGridView\Helper;

class HelperWishlist
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $dataObject;

    /**
     * @var \Magento\Wishlist\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * HelperWishlist constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Wishlist\Helper\Data $helperData
     * @param \Magento\Framework\DataObject $dataObject
     * @param \Magento\Wishlist\Model\ItemFactory $itemFactory
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Wishlist\Helper\Data $helperData,
        \Magento\Framework\DataObject $dataObject,
        \Magento\Wishlist\Model\ItemFactory $itemFactory
    ) {
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helperData = $helperData;
        $this->dataObject = $dataObject;
        $this->itemFactory = $itemFactory;
    }

    /**
     * Return Item Factory
     *
     * @return \Magento\Wishlist\Model\ItemFactory
     */
    public function returnItemFactory()
    {
        return $this->itemFactory;
    }

    /**
     * Return Store Manager Interface
     *
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    public function returnStoreManagerInterface()
    {
        return $this->storeManagerInterface;
    }

    /**
     * Return Helper Data
     *
     * @return \Magento\Wishlist\Helper\Data
     */
    public function returnHelperData()
    {
        return $this->helperData;
    }

    /**
     * Return Data Object
     *
     * @return \Magento\Framework\DataObject
     */
    public function returnDataObject()
    {
        return $this->dataObject;
    }
}
