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
namespace Bss\ConfiguableGridView\Controller\Index;
use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Index
 *
 * @package Bss\ConfiguableGridView\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $session
     */
    public function __construct(Context $context,
    \Magento\Checkout\Model\Session $session
)
    {
        $this->session = $session;
        parent::__construct($context);
    }

    /**
     * Post cart item
     *
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $quote = $this->session->getQuote()->getAllItems();
        $quoteArray = [];
        foreach ($quote as $item) {
            foreach ($item->getQtyOptions() as $key => $value) {
                $quoteArray[(string)$key] = [
                    'qty' => $item->getQty(),
                    'item_id' => $item->getItemId()
            ];
            }
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($quoteArray);

        return $resultJson;
    }
}
