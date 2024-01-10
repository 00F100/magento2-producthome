<?php
namespace MagentoExample\ProductHome\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MagentoExample\ProductHome\Helper\Data as ProductHomeHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\Helper\Data as PricingData;

class FeaturedProduct extends Template
{
    protected $helper;
    protected $productRepository;
    protected $stockState;
    protected $pricingData;

    public function __construct(
        Context $context,
        ProductHomeHelper $helper,
        ProductRepositoryInterface $productRepository,
        StockStateInterface $stockState,
        PricingData $pricingData,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->stockState = $stockState;
        $this->pricingData = $pricingData;
        parent::__construct($context, $data);
    }

    public function isBlockEnabled()
    {
        return $this->helper->getGeneralConfig('enable');
    }

    public function getFeaturedProductIds()
    {
        $productIds = $this->helper->getGeneralConfig('product_id');
        return explode(',', $productIds);
    }


    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    public function getImageUrl($product)
    {
        $store = $this->_storeManager->getStore();
        $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        return $imageUrl;
    }

    public function getStockQuantity($product)
    {
        $typeId = $product->getTypeId();

        switch ($typeId) {
            case 'simple':
            case 'virtual':
            case 'downloadable':
                return $this->getSimpleProductStockQuantity($product);

            case 'configurable':
                return $this->getConfigurableProductStockQuantity($product);

            case 'grouped':
                return $this->getGroupedProductStockQuantity($product);

            case 'bundle':
                return $this->getBundleProductStockQuantity($product);

            default:
                return __('Estoque não disponível');
        }
    }

    private function getSimpleProductStockQuantity($product)
    {
        $stockQty = $this->stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
        return $stockQty > 0 ? __('Estoque: ') . $stockQty : __('Fora de estoque');
    }

    private function getConfigurableProductStockQuantity($product)
    {
        $totalStock = 0;

        $productTypeInstance = $product->getTypeInstance();
        $childProducts = $productTypeInstance->getUsedProducts($product);

        foreach ($childProducts as $child) {
            $stockQty = $this->stockState->getStockQty($child->getId(), $child->getStore()->getWebsiteId());
            $totalStock += $stockQty;
        }

        return $totalStock > 0 ? __('Estoque: ') . $totalStock : __('Fora de estoque');
    }


    private function getGroupedProductStockQuantity($product)
    {
        $totalStock = 0;
        $productTypeInstance = $product->getTypeInstance();
        $childProducts = $productTypeInstance->getAssociatedProducts($product);
        foreach ($childProducts as $child) {
            if ($child->isSaleable()) {
                $stockQty = $this->stockState->getStockQty($child->getId(), $child->getStore()->getWebsiteId());
                $totalStock += $stockQty;
            }
        }

        return $totalStock > 0 ? __('Estoque: ') . $totalStock : __('Fora de estoque');
    }


    private function getBundleProductStockQuantity($product)
    {
        $bundleTypeInstance = $product->getTypeInstance();
        $optionCollection = $bundleTypeInstance->getOptionsCollection($product);

        $minStock = PHP_INT_MAX;

        foreach ($optionCollection as $option) {
            $selectionCollection = $bundleTypeInstance->getSelectionsCollection([$option->getId()], $product);
            
            foreach ($selectionCollection as $selection) {
                if ($selection->isSaleable()) {
                    $stockQty = $this->stockState->getStockQty($selection->getId(), $selection->getStore()->getWebsiteId());
                    if ($stockQty < $minStock) {
                        $minStock = $stockQty;
                    }
                }
            }
        }

        return $minStock != PHP_INT_MAX ? __('Estoque: ') . $minStock : __('Fora de estoque');
    }



    public function getFeaturedProducts()
    {
        $featuredProducts = [];
        $productIds = $this->getFeaturedProductIds();
        foreach ($productIds as $productId){
            $featuredProducts[] = $this->loadProductById($productId);
        }
        return $featuredProducts;
    }

    private function getByCurrency($price) {
        return $this->pricingData->currency($price, true, false);;
    }

    public function getPrice(\Magento\Catalog\Model\Product $product)
    {
        switch ($product->getTypeId()) {
            case 'simple':
                return $this->getByCurrency($product->getPrice());
        
            case 'configurable':
                return $this->getPriceRange($product);
        
            case 'grouped':
                return $this->getGroupedProductPrice($product);
        
            case 'virtual':
                return $this->getByCurrency($product->getPrice());
        
            case 'bundle':
                return $this->getBundlePriceRange($product);
        
            case 'downloadable':
                return $this->getByCurrency($product->getPrice());
        
            case 'giftcard':
                return $this->getGiftCardPrice($product);
        
            default:
                return __('Preço não disponível');
        }
        
    }

    private function getPriceRange($product)
    {
        $productTypeInstance = $product->getTypeInstance();
        $usedProducts = $productTypeInstance->getUsedProducts($product);

        $minPrice = PHP_FLOAT_MAX;
        $maxPrice = PHP_FLOAT_MIN;

        foreach ($usedProducts as $child) {
            if ($child->getPrice() < $minPrice) {
                $minPrice = $child->getPrice();
            }
            if ($child->getPrice() > $maxPrice) {
                $maxPrice = $child->getPrice();
            }
        }

        if ($minPrice === PHP_FLOAT_MAX && $maxPrice === PHP_FLOAT_MIN) {
            $minPrice = $maxPrice = $product->getPrice();
        }

        if ($minPrice == $maxPrice) {
            return $this->getByCurrency($minPrice);
        }

        return $this->getByCurrency($minPrice) . ' a ' . $this->getByCurrency($maxPrice);
    }

    private function getGroupedProductPrice($product)
    {
        $groupedProductTypeInstance = $product->getTypeInstance();
        $childProducts = $groupedProductTypeInstance->getAssociatedProducts($product);

        $totalPrice = 0;

        foreach ($childProducts as $child) {
            if ($child->isSaleable()) {
                $totalPrice += $child->getFinalPrice();
                // $totalPrice += $child->getPrice();
            }
        }

        return $this->getByCurrency($totalPrice);
    }

    private function getBundlePriceRange($product)
    {
        $bundleProductPriceModel = $product->getPriceModel();
        list($minPrice, $maxPrice) = $bundleProductPriceModel->getTotalPrices($product, null, true, false);

        if ($minPrice == $maxPrice) {
            return $this->getByCurrency($minPrice);
        }
        return $this->getByCurrency($minPrice) . ' a ' . $this->getByCurrency($maxPrice);
    }

    private function getGiftCardPrice($product)
    {
        if ($product->getTypeId() !== 'giftcard') {
            return __('Não é um cartão-presente');
        }
        $fixedAmounts = $product->getGiftcardAmounts();
        if (is_array($fixedAmounts) && !empty($fixedAmounts)) {
            $amounts = array_map(function ($amount) {
                return $amount['website_value'];
            }, $fixedAmounts);
            sort($amounts);
            $minPrice = reset($amounts);
            $maxPrice = end($amounts);
    
            return $this->getByCurrency($minPrice) . ' a ' . $this->getByCurrency($maxPrice);
        }
        $minValue = $product->getOpenAmountMin();
        $maxValue = $product->getOpenAmountMax();
        if ($minValue && $maxValue) {
            return $this->getByCurrency($minValue) . ' a ' . $this->getByCurrency($maxValue);
        }
        return __('Preço não disponível');
    }
}
