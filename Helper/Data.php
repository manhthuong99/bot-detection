<?php

namespace Eric\BotDetection\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\CacheInterface;

class Data extends AbstractHelper
{
    const XML_PATH_DETECTION_ACTION_ENABLED = 'bot_detection/action/enabled';
    const XML_PATH_DETECTION_ACTION_MAXIMUN_REQUEST = 'bot_detection/action/maximum_requests';
    const XML_PATH_DETECTION_ACTION_TIME_LIMIT = 'bot_detection/action/time_limit';
    const XML_PATH_DETECTION_ACTION_BLACK_LIST = 'bot_detection/action/black_list';
    const XML_PATH_DETECTION_BILLING_ENABLED = 'bot_detection/billing/enabled';
    const XML_PATH_DETECTION_BILLING_MESSAGE = 'bot_detection/billing/message';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\App\Request\Http $request
     * @param CacheInterface $cache
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\App\Request\Http $request,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->cache = $cache;
        $this->remoteAddress = $remoteAddress;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function isActionEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DETECTION_ACTION_ENABLED);
    }

    /**
     * @return string
     */
    public function getMaximumRequests()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DETECTION_ACTION_MAXIMUN_REQUEST);
    }

    /**
     * @return array
     */
    public function getBlackList()
    {
        $blackList = $this->scopeConfig->getValue(self::XML_PATH_DETECTION_ACTION_BLACK_LIST);
        if (!$blackList) {
            return [];
        }
        return explode("\n", $blackList);
    }

    /**
     * @return string
     */
    public function getTimeLimit()
    {
        $timeLimit = (int) $this->scopeConfig->getValue(self::XML_PATH_DETECTION_ACTION_TIME_LIMIT);
        return $timeLimit ?? 10;
    }

    /**
     * @return string
     */
    public function isBillingEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DETECTION_BILLING_ENABLED);
    }

    /**
     * @return string
     */
    public function getBillingMessage()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DETECTION_BILLING_MESSAGE);
    }

    /**
     * @param bool $isV4
     * @return string
     */
    public function getRemoteIp($isV4 = true)
    {
        if ($isV4) {
            return $this->remoteAddress->getRemoteAddress() ?: $_SERVER['REMOTE_ADDR'] ?? '';
        } else {
            return $this->request->getServer('HTTP_X_FORWARDED_FOR') ?: $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        }
    }

    /**
     * @param string $ipv4
     * @param string $ipv6
     * @return string
     */
    public function isAllowActionAccess($ipv4 = null, $ipv6 = null)
    {
        if (!$this->isActionEnabled()) {
            return true;
        }
        if ($ipv4 === null) {
            $ipv4 = $this->getRemoteIp();
        }

        if ($ipv6 === null) {
            $ipv6 = $this->getRemoteIp(false);
        }

        if (
            $this->isIPBlacklisted($ipv4) ||
            $this->isIPBlacklisted($ipv6) ||
            $this->exceedsMaximumRequests($ipv4) ||
            $this->exceedsMaximumRequests($ipv6)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param string $ip
     * @return string
     */
    private function isIPBlacklisted($ip)
    {
        $blackList = $this->getBlackList();
        foreach ($blackList as $ipBlock) {
            if (trim($ip) == trim($ipBlock)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $ip
     * @return string
     */
    private function exceedsMaximumRequests($ip)
    {
        if ($ip) {
            $timeLimit = $this->getTimeLimit();
            $count = $this->cache->load($ip) ?? 0;
            $numberRequest = (int) $count + 1;

            if ($numberRequest <= $this->getMaximumRequests()) {
                $this->cache->save($numberRequest, $ip, [], $timeLimit);
            } else {
                return true;
            }
        }
        return false;
    }

}