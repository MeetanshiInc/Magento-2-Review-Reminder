<?php

namespace Meetanshi\ReviewReminderBasic\Cron;

use Meetanshi\ReviewReminderBasic\Helper\Data;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SortOrderBuilder;

class Reminder
{
    private $helper;
    private $searchCriteriaBuilder;
    private $orderRepository;
    private $sortBuilder;

    public function __construct(
        Data $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortBuilder,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
        $this->sortBuilder = $sortBuilder;
    }

    public function execute()
    {
        if ( $this->helper->getConfig() ):
            try {
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('status', 'pending', 'eq')
                    ->addSortOrder($this->sortBuilder->setField('entity_id')
                        ->setDescendingDirection()->create())
                    ->setPageSize(100)->setCurrentPage(1)->create();

                $day = '-' . $this->helper->getDays() . ' day';
                $to = $this->helper->getCurrentTime();
                $from = strtotime($day, strtotime($to));
                $from = date('Y-m-d h:i:s', $from);

                $ordersList = $this->orderRepository->getList($searchCriteria);
                $ordersList->addFieldToFilter('created_at', array('from' => $from, 'to' => $to));

                foreach ($ordersList as $order):
                    $config = array();
                    $config['incrementId'] = $order->getIncrementId();
                    $config['mail'] = $order->getCustomerEmail();
                    $config['customer'] = $order->getBillingAddress()->getFirstName();
                    $config['date'] = date("M d, Y h:i:s A", strtotime($order->getcreatedAt()));
                    $product = array();
                    foreach ($order->getAllVisibleItems() as $item):
                        $product[] = $item->getProductId();
                    endforeach;
                    $config['reminder']['product_id'] = implode(',', $product);
                    $config['reminder']['increment_id'] = $order->getIncrementId();
                    $this->helper->sendReviewReminderMail($config);
                endforeach;

            } catch (\Exception $e) {
                return $e->getMessage();
            }
        endif;
        return $this;
    }
}