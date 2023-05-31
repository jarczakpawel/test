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
 * @category  BSS
 * @package   Bss_ConfigurableProductWholesale
 * @author    Extension Team
 * @copyright Copyright (c) 2017-2020 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\ConfiguableGridView\Observer;

use Bss\ConfiguableGridView\Helper\ATPrice;
use Bss\ConfiguableGridView\Helper\Data as HelperData;
use Magento\Checkout\Helper\Cart as HelperCart;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SetTierPriceForItem
 *
 * @package Bss\ConfigurableProductWholesale\Observer
 */
class SetTierPriceForItem implements ObserverInterface
{
    protected $storeManager;
    /**
     * @var ATPrice
     */
    protected $aTPrice;
    /**
     * @var HelperData
     */
    protected $helperData;
    /**
     * @var HelperCart
     */
    protected $helperCart;

    /**
     * SetTierPriceForItem constructor.
     * @param HelperData $helperData
     * @param ATPrice $aTPrice
     * @param StoreManagerInterface $storeManager
     * @param HelperCart $helperCart
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        HelperData $helperData,
        ATPrice $aTPrice,
        HelperCart $helperCart
    ) {
        $this->storeManager = $storeManager;
        $this->helperData = $helperData;
        $this->aTPrice = $aTPrice;
        $this->helperCart = $helperCart;
    }

    /**
     * Set tier price for Item
     *
     * @param EventObserver $observer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(EventObserver $observer)
    {
        $configAdvanced = $this->helperData->advancedTierPrice($this->storeManager->getStore()->getId());
        if ($configAdvanced) {
            $quoteItem = $observer->getEvent()->getQuoteItem();
            $itemType = $quoteItem->getProduct()->getTypeId();
            if ($itemType == "configurable") {
                $typeTax = $this->helperData->getTypeTax();
                $this->aTPrice->setPriceATPrice($quoteItem, $typeTax);
            }
        }
    }
}
