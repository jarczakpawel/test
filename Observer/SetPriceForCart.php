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
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SetPriceForCart
 *
 * @package Bss\ConfigurableProductWholesale\Observer
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SetPriceForCart implements ObserverInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;
    /**
     * @var ATPrice
     */
    protected $storeManager;
    /**
     * @var ATPrice
     */
    protected $aTPrice;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * SetPriceForCart constructor.
     *
     * @param HelperData $helperData
     * @param StoreManagerInterface $storeManager
     * @param ATPrice $aTPrice
     * @param Session $checkoutSession
     */
    public function __construct(
        HelperData $helperData,
        StoreManagerInterface $storeManager,
        ATPrice $aTPrice,
        Session $checkoutSession
    ) {
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
        $this->aTPrice = $aTPrice;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Advanced tier price
     *
     * @param EventObserver $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EventObserver $observer)
    {
        $configAdvanced = $this->helperData->advancedTierPrice($this->storeManager->getStore()->getId());
        if ($configAdvanced) {
            $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
            foreach ($quoteItems as $quoteItem) {
                $quoteItemType = $quoteItem->getProduct()->getTypeId();
                if ($quoteItemType != 'configurable') {
                    continue;
                }
                $typeTax = $this->helperData->getTypeTax();
                $this->aTPrice->setPriceATPrice($quoteItem, $typeTax);
            }
        }
    }
}
