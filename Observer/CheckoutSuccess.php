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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CheckoutSuccess
 *
 * @package Bss\ConfiguableGridView\Observer
 */
class CheckoutSuccess implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $typeList;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $pool;

    /**
     * @var \Bss\ConfiguableGridView\Helper\Data
     */
    protected $helper;

    /**
     * CheckoutSuccess constructor.
     * @param \Magento\Framework\App\Cache\TypeListInterface $typeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $pool
     * @param \Bss\ConfiguableGridView\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Cache\TypeListInterface $typeList,
        \Magento\Framework\App\Cache\Frontend\Pool $pool,
        \Bss\ConfiguableGridView\Helper\Data $helper
    ) {
        $this->typeList = $typeList;
        $this->pool = $pool;
        $this->helper = $helper;
    }

    /**
     * Clear cache after checkout success
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->helper->isEnabled()) {
            $types = ['block_html', 'full_page'];
            foreach ($types as $type) {
                $this->typeList->cleanType($type);
            }
            foreach ($this->pool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
        }
    }
}
