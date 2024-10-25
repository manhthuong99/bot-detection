<?php
namespace Eric\BotDetection\Plugin\Model;

use Eric\BotDetection\Helper\Data as HelperData;
use Magento\Framework\Exception\CouldNotSaveException;

class GuestPaymentInformationManagementRestrictions
{
    private $validateFields = [
        'firstname',
        'lastname',
        'company',
        'telephone',
        'postcode',
        'city',
    ];

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var \Amasty\Geoip\Model\Geolocation
     */
    protected $geoipModel;


    /**
     * GuestPaymentInformationManagementRestrictions constructor.
     * @param HelperData $helperData
     * @param \Amasty\Geoip\Model\Geolocation $geoipModel
     */
    public function __construct(
        HelperData $helperData,
        \Amasty\Geoip\Model\Geolocation $geoipModel
    ) {
        $this->helperData = $helperData;
        $this->geoipModel = $geoipModel;
    }

    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {

        if ($paymentMethod->getMethod() == 'stripe_payments') {
            if (!$this->helperData->isAllowActionAccess()) {
                $timeLimit = $this->helperData->getTimeLimit();
                throw new CouldNotSaveException(
                    __("You are interacting too quickly. Please try again after {$timeLimit} seconds.")
                );
            }

            if ($this->helperData->isBillingEnabled()) {
                $ipv4 = $this->helperData->getRemoteIp();
                $locate = $this->geoipModel->locate($ipv4);
                $country = $locate->getCountry();
                $billingCountry = $billingAddress->getCountryId();
                if ($country != $billingCountry && $ipv4 != '127.0.0.1') {
                    throw new CouldNotSaveException(__($this->helperData->getBillingMessage()));
                }
            }
        }

        $regex = '/^[^{}()[\]<>!]*$/';
        foreach ($this->validateFields as $field) {
            if (!preg_match($regex, $billingAddress->getData($field)) || strpos($billingAddress->getData($field), 'getTemplateFilter') !== false) {
                throw new CouldNotSaveException(
                    __("Please enter value without special character.")
                );
            }
        }

        return $proceed($cartId, $email, $paymentMethod, $billingAddress);
    }
}
