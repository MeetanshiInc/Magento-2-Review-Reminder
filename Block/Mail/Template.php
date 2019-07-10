<?php


namespace Meetanshi\ReviewReminderBasic\Block\Mail;

use Magento\Framework\View\Element\Template as CoreTempate;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Meetanshi\ReviewReminderBasic\Helper\Data;
use Magento\Catalog\Helper\Image;

class Template extends CoreTempate
{
    private $productCollection;
    private $helper;
    private $imageHelperFactory;

    public function __construct
    (
        CoreTempate\Context $context,
        array $data = [],
        Collection $productCollection,
        Data $helper,
        Image $imageHelperFactory
    )
    {
        $this->productCollection = $productCollection;
        $this->helper = $helper;
        $this->imageHelperFactory = $imageHelperFactory;
        CoreTempate::__construct($context, $data);
    }

    public function getReminderData($reminder)
    {
        $productCollection = $this->productCollection->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', array('in' => $reminder['product_id']));

        return $productCollection;
    }

    public function getUtm()
    {
        return $this->helper->getUtmConfig();
    }

    public function getProductImage($product)
    {
        try {

            return $this->imageHelperFactory->init($product, 'small_image', ['type' => 'small_image'])->keepAspectRatio(true)->resize('65', '65')->getUrl();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }
}