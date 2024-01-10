<?php

namespace MagentoExample\ProductHome\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductList implements ArrayInterface
{
    protected $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name');
        $options = [];

        foreach ($collection as $product) {
            $options[] = ['value' => $product->getId(), 'label' => $product->getName()];
        }

        return $options;
    }
}
