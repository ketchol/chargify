<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 10/27/2016
 * Time: 10:06 AM
 */

namespace IvanCLI\Chargify\Controllers;


use IvanCLI\Chargify\Models\Webhook;
use IvanCLI\Chargify\Traits\Curl;

/**
 * Class WebhookController
 * @package IvanCLI\Chargify\Controllers
 */
class WebhookController
{
    use Curl;

    protected $accessPoint;

    protected $apiDomain;

    public function __construct($accessPoint)
    {
        $this->accessPoint = $accessPoint;

        $this->apiDomain = config("chargify.{$this->accessPoint}.api_domain");
    }

    /**
     * Load all webhooks
     *
     * @param null $queryString
     * @return array|mixed
     */
    public function all($queryString = null)
    {
        return $this->__all($queryString);
    }

    /**
     * Resend hooker
     *
     * @param $id
     * @return bool|mixed
     */
    public function resend($id)
    {
        return $this->__resend($id);
    }

    /**
     * @param $queryString
     * @return array|mixed
     */
    private function __all($queryString)
    {
        $url = $this->apiDomain . "webhooks.json";
        if (!is_null($queryString)) {
            $url .= "?" . $queryString;
        }
        $webhooks = $this->_get($this->accessPoint, $url);
        if (is_array($webhooks)) {
            $webhooks = array_pluck($webhooks, 'webhook');
            $output = array();
            foreach ($webhooks as $webhook) {
                $output[] = $this->__assign($webhook);
            }
            return $output;
        } else {
            return $webhooks;
        }
    }

    /**
     * @param $id
     * @return bool|mixed
     */
    private function __resend($id)
    {
        $url = $this->apiDomain . "webhooks/replay.json";
        if (!is_array($id)) {
            $id = array($id);
        }
        $data = array(
            "ids" => $id
        );
        $data = json_decode(json_encode($data), false);
        $result = $this->_post($this->accessPoint, $url, $data);
        if (isset($result->status) && $result->status == "ok") {
            $result = true;
        }
        return $result;
    }

    /**
     * @param $input_transaction
     * @return Webhook
     */
    private function __assign($input_transaction)
    {
        $transaction = new Webhook($this->accessPoint);
        foreach ($input_transaction as $key => $value) {
            if (property_exists($transaction, $key)) {
                $transaction->$key = $value;
            }
        }
        return $transaction;
    }
}