<?php
namespace MagentoExample\ProductHome\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class Data
{
    protected $productRepository;
    protected $getSalableQuantityDataBySku;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku
    ) {
        $this->productRepository = $productRepository;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
    }

    public function getStockData($sku)
    {
        $stockData = $this->getSalableQuantityDataBySku->execute($sku);
        return $stockData;
    }
}
