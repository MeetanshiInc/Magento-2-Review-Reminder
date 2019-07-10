<?php


namespace Meetanshi\ReviewReminderBasic\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\View\Asset\Repository;

class Data extends AbstractHelper
{
    const ENABLE = 'admin_reviewreminderbasic/config/enable';
    const DAYS = 'admin_reviewreminderbasic/config/days';
    const SENDER = 'admin_reviewreminderbasic/config/sender';
    const EMAIL_TEMPLATE = 'admin_reviewreminder_config_general_email_template';
    private $timezone;
    private $storeManager;
    private $inlineTranslation;
    private $transportBuilder;
    private $assetRepo;


    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        Repository $assetRepo,
        TransportBuilder $transportBuilder
    )
    {
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->assetRepo = $assetRepo;
        parent::__construct($context);
    }

    public function getConfig($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        try {
            if ( $this->scopeConfig->getValue(self::ENABLE, $scope) ):
                return true;
            else:
                return false;
            endif;

        } catch (\Exception $e) {
            return false;
        }

    }


    public function getDays($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(self::DAYS, $scope);
    }

    public function getCurrentTime()
    {
        try {
            return $this->timezone->date()->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    public function sendReviewReminderMail($config)
    {
        try {
            $config['template'] = self::EMAIL_TEMPLATE;
            $config['store'] = $this->getStoreName();
            $config['image'] = $this->assetRepo->getUrl("Meetanshi_ReviewReminderBasic::images/reminder.jpg");
            $this->inlineTranslation->suspend();
            $this->generateTemplate($config);
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return $this;
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    public function getStoreName($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'general/store_information/name',
            $scope
        );
    }

    public function generateTemplate($config)
    {
        try {
            $this->transportBuilder->setTemplateIdentifier($config['template'])
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId(),
                    ]
                )
                ->setTemplateVars($config)
                ->setFrom($this->scopeConfig->getValue(self::SENDER, ScopeConfigInterface::SCOPE_TYPE_DEFAULT))
                ->addTo($config['mail']);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return $this;
    }


}
