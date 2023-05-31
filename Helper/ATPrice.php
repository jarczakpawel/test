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

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Pricing\Adjustment;

/**
 * Class ATPrice
 *
 * @package Bss\ConfiguableGridView\Helper
 */
class ATPrice
{
    /**
     * Get tier price
     *
     * @param Product $product
     * @param array $data
     */
    public function getTierPrice($product, &$data)
    {
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        $i = 0;
        if (!empty($tierPricesList)) {
            foreach ($tierPricesList as $tierPrice) {
                $data['tier_price'][$i]["qty"] = $tierPrice['price_qty'];
                $data['tier_price'][$i]['price'] = $tierPrice['price']->getValue();
                $data['tier_price'][$i]['price_excl_tax'] = $tierPrice['price']->getValue(Adjustment::ADJUSTMENT_CODE);
                $i++;
            }
        }
    }

    /**
     * Advanced Tier Price
     *
     * @param array $data
     * @param string $productId
     * @return array|null
     */
    public function advancedTierPrice($data, $productId)
    {
        $productIds = [];
        $i = 0;
        $tierPrice = [];
        foreach ($data as $key => $datum) {
            if ($datum["product_id"] == $productId && isset($datum["tier_price"])) {
                foreach ($data as $key1 => $datum1) {
                    if ($key != $key1 && isset($datum1["tier_price"])) {
                        if ($datum["tier_price"] == $datum1["tier_price"]) {
                            $tierPrice = $datum["tier_price"];
                            $this->productIdsATPrice($productIds, $datum, $datum1, $i);
                            $i++;
                        }
                    }
                }
            }
        }
        return $this->dataATPrice($productIds, $tierPrice);
    }

    /**
     * Data advanced tier price
     *
     * @param array $productIds
     * @param array $tierPrice
     * @return array|null
     */
    public function dataATPrice($productIds, $tierPrice)
    {
        if (count($productIds) > 0) {
            return ["productIds" => $productIds,
                "tierPrice" => $tierPrice
            ];
        } else {
            return null;
        }
    }

    /**
     * Product Id of when product advanced tier price
     *
     * @param array $productIds
     * @param array $datum
     * @param array $datum1
     * @param int $i
     * @return mixed
     */
    public function productIdsATPrice(&$productIds, $datum, $datum1, &$i)
    {
        if ($i == 0) {
            $productIds[$i] = $datum["product_id"];
            $i++;
        }
        $productIds[$i] = $datum1["product_id"];
        return $productIds;
    }

    /**
     * Data child product
     *
     * @param Product $product
     * @return array
     */
    public function dataChildProduct($product)
    {
        $parentProduct = $product->getTypeInstance()->getUsedProducts($product);
        $i = 0;
        $data = [];
        foreach ($parentProduct as $childProduct) {
            $priceInfo = $childProduct->getPriceInfo();
            $finalPrice = $priceInfo->getPrice('final_price')->getAmount();
            $data[$i]['price'] = [
                'old_price' => $priceInfo->getPrice('regular_price')->getAmount()->getValue(),
                'basePrice' => $finalPrice->getBaseAmount(),
                'finalPrice' => $finalPrice->getValue(),
                'excl_tax' => $finalPrice->getValue(Adjustment::ADJUSTMENT_CODE),
            ];
            $this->getTierPrice($childProduct, $data[$i]);
            $data[$i]["product_id"] = $childProduct->getEntityId();
            $i++;
        }
        return $data;
    }

    /**
     * Set price when advanced tier price
     *
     * @param Item[] $quoteItem
     * @param int $typeTax
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setPriceATPrice($quoteItem, $typeTax)
    {
        $currentProductId = $quoteItem->getChildren()[0]->getProductId();
        $product = $quoteItem->getProduct();
        $dataChildProduct = $this->dataChildProduct($product);
        $advancedTierPrice = $this->advancedTierPrice($dataChildProduct, $currentProductId);
        $childCart = [];
        if (is_array($advancedTierPrice)) {
            $qty = 0;
            foreach ($quoteItem->getQuote()->getAllVisibleItems() as $quoteItem) {
                $quoteItemType = $quoteItem->getProduct()->getTypeId();
                if ($quoteItemType != 'configurable') {
                    continue;
                }
                $this->productATPrice($childCart, $quoteItem, $advancedTierPrice, $qty);
            }
            $aTP = 0;
            foreach ($childCart as $itemCart) {
                $finalPrice = $itemCart->getProduct()->getFinalPrice();
                foreach ($advancedTierPrice["tierPrice"] as $tierPrice) {
                    if ($qty >= $tierPrice["qty"]) {
                        $aTP = 1;
                        $finalPrice = $this->setPriceByTax($typeTax, $finalPrice, $tierPrice);
                        $itemCart->setCustomPrice($finalPrice);
                        $itemCart->setOriginalCustomPrice($finalPrice);
                        $itemCart->getProduct()->setIsSuperMode(true);
                    }
                }
            }
            if ($aTP == 0) {
                foreach ($childCart as $itemCart) {
                    $finalPrice = $quoteItem->getProduct()->getFinalPrice();
                    $itemCart->setCustomPrice($finalPrice);
                    $itemCart->setOriginalCustomPrice($finalPrice);
                    $itemCart->getProduct()->setIsSuperMode(true);
                }
            }
        }
    }

    /**
     * Product Advanced tier price
     *
     * @param Item[] $childCart
     * @param Item[] $quoteItem
     * @param array $advancedTierPrice
     * @param float $qty
     * @return mixed
     */
    public function productATPrice(&$childCart, $quoteItem, $advancedTierPrice, &$qty)
    {
        $productId = $quoteItem->getChildren()[0]->getProductId();
        foreach ($advancedTierPrice["productIds"] as $productIdATP) {
            if ($productId == $productIdATP) {
                $qty += $quoteItem->getQty();
                $childCart[] = $quoteItem;
            }
        }
        return $childCart;
    }

    /**
     * Set final price by tax
     *
     * @param int $typeTax
     * @param double $finalPrice
     * @param array $tierPrice
     * @return mixed
     */
    public function setPriceByTax($typeTax, $finalPrice, $tierPrice)
    {
        $priceTax = $tierPrice["price"];
        $priceExclTax = $tierPrice["price_excl_tax"];
        if ($typeTax == 1 && $priceTax < $finalPrice) {
            return $priceTax;
        } elseif ($typeTax == 0 && $priceExclTax < $finalPrice) {
            return $priceExclTax;
        }
        return $finalPrice;
    }
}
