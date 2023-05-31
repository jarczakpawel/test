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

namespace Bss\ConfiguableGridView\Plugin\Model;

use Bss\ConfiguableGridView\Helper\HelperClass;
use Bss\ConfiguableGridView\Helper\HelperWishlist;
use Magento\Framework\Message\ManagerInterface;
use Magento\Wishlist\Model\Item;

/**
 * Class Wishlist
 *
 * @package Bss\ConfiguableGridView\Plugin\Model
 */
class Wishlist
{
    /**
     * @var HelperClass
     */
    protected $helperClass;

    /**
     * @var HelperWishlist
     */
    protected $helperWishlist;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Wishlist constructor.
     *
     * @param HelperClass $helperClass
     * @param HelperWishlist $helperWishlist
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        HelperClass $helperClass,
        HelperWishlist $helperWishlist,
        ManagerInterface $messageManager
    ) {
        $this->helperClass = $helperClass;
        $this->helperWishlist = $helperWishlist;
        $this->messageManager = $messageManager;
    }

    /**
     * Update item configurable grid view
     *
     * @param \Magento\Wishlist\Model\Wishlist $subject
     * @param callable $proceed
     * @param int|Item $itemId
     * @param \Magento\Framework\DataObject $buyRequest
     * @param null|array|\Magento\Framework\DataObject $params
     * @return \Magento\Wishlist\Model\Wishlist
     */
    public function aroundUpdateItem(
        $subject,
        callable $proceed,
        $itemId,
        $buyRequest,
        $params = null
    ) {
        if (isset($buyRequest["disable_grid_table_view"]) && $buyRequest["disable_grid_table_view"] == 1) {
            return $proceed($itemId, $buyRequest, $params);
        }

        if (isset($buyRequest['configurable_grid_table'])
            && $buyRequest['configurable_grid_table'] == 'Yes'
            && array_sum($buyRequest['config_table_qty']) > 0
        ) {
            try {
                $this->updateItemWishlist($subject, $itemId, $buyRequest);
                return $subject;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t update your Wish List right now.'));
                $this->helperClass->returnLoggerInterface()->critical($e);
            }
        }
        return $proceed($itemId, $buyRequest, $params);
    }

    /**
     * Update item wishlist
     *
     * @param \Magento\Wishlist\Model\Wishlist $subject
     * @param int|Item $itemId
     * @param \Magento\Framework\DataObject $buyRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateItemWishlist($subject, $itemId, $buyRequest)
    {
        if ($itemId instanceof Item) {
            $item = $itemId;
            $itemId = $item->getId();
        } else {
            $item = $subject->getItem((int)$itemId);
        }
        if (!$item) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t specify a wish list item.'));
        }
        $product = $item->getProduct();
        $item->delete();
        foreach ($buyRequest['config_table_qty'] as $id => $qty) {
            if (isset($qty) && $qty != '' && $qty > 0) {
                $data = [];
                $filter = $this->helperClass->returnLocalizedToNormalized()->setOptions(
                    ['locale' => $this->helperClass->returnResolverInterface()->getLocale()]
                );
                $data['super_attribute'] = $buyRequest['bss_super_attribute'][$id];
                $data['product'] = $buyRequest['product'];
                $data['qty'] = $filter->filter($qty);
                if (!empty($buyRequest['options'])) {
                    $data['options'] = $buyRequest['options'];
                }
                $buyRequestNew = $this->helperWishlist->returnDataObject()->addData($data);
                $subject->addNewItem($product, $buyRequestNew);
            }
        }
        $this->helperWishlist->returnHelperData()->calculate();
        $message = __('%1 has been updated in your Wish List.', $product->getName());
        $this->messageManager->addSuccessMessage($message);
    }
}
