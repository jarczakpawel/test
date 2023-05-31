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
 * @copyright  Copyright (c) 2018-2019 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\ConfiguableGridView\Model\Config;

class Group implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Customer groups options array
     *
     * @var null|array
     */
    protected $options;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $groupManagement;

    /**
     * Group constructor.
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $groupManagement
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\Collection $groupManagement
    ) {
        $this->groupManagement = $groupManagement;
    }

    /**
     * Retrieve customer groups as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->groupManagement->toOptionArray();
        }

        return $this->options;
    }
}
