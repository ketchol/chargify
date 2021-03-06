<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 10/25/2016
 * Time: 11:42 AM
 */

namespace IvanCLI\Chargify\Controllers;


use Illuminate\Support\Facades\Cache;
use IvanCLI\Chargify\Models\Coupon;
use IvanCLI\Chargify\Traits\Curl;

class CouponController
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
     * Create a new coupon
     *
     * @param $fields
     * @return Coupon|mixed
     */
    public function create($fields)
    {
        return $this->__create($fields);
    }

    /**
     * Update a coupon
     *
     * @param $coupon_id
     * @param $fields
     * @return Coupon|mixed
     */
    public function update($coupon_id, $fields)
    {
        return $this->__update($coupon_id, $fields);
    }

    /**
     * Archive an existing coupon
     *
     * @param $coupon_id
     * @return bool|mixed
     */
    public function archive($coupon_id)
    {
        return $this->__archive($coupon_id);
    }

    /**
     * Load a coupon
     *
     * @param $coupon_id
     * @return Coupon|mixed
     */
    public function get($coupon_id)
    {
        if (config('chargify.caching.enable') == true) {
            return Cache::remember("{$this->apiDomain}.coupons.{$coupon_id}", config('chargify.caching.ttl'), function () use ($coupon_id) {
                return $this->___get($coupon_id);
            });
        } else {
            return $this->___get($coupon_id);
        }
    }

    /**
     * Find a coupon by coupon code
     *
     * @param $coupon_code
     * @param null $product_family_id
     * @return Coupon|mixed
     */
    public function find($coupon_code, $product_family_id = null)
    {
        if (config('chargify.caching.enable') == true) {
            if (!is_null($product_family_id)) {
                $key = "{$this->apiDomain}.product_families.{$product_family_id}.coupons.coupon_code.{$coupon_code}";
            } else {
                $key = "{$this->apiDomain}.coupons.coupon_code.{$coupon_code}";
            }
            return Cache::remember($key, config('chargify.caching.ttl'), function () use ($coupon_code, $product_family_id) {
                return $this->__find($coupon_code, $product_family_id);
            });
        } else {
            return $this->__find($coupon_code, $product_family_id);
        }
    }

    /**
     * Load usage of a coupon
     *
     * @param $coupon_id
     * @return mixed
     */
    public function getUsage($coupon_id)
    {
        return $this->__getUsage($coupon_id);
    }

    /**
     * Check a coupon code's validity
     *
     * @param $coupon_code
     * @param null $product_family_id
     * @return Coupon|mixed
     */
    public function validate($coupon_code, $product_family_id = null)
    {
        return $this->__validate($coupon_code, $product_family_id);
    }

    /**
     * Load all coupon subcode of a coupon
     *
     * @param $coupon_id
     * @param null $page
     * @param null $per_page
     * @return mixed
     */
    public function allSubcodes($coupon_id, $page = null, $per_page = null)
    {
        return $this->__allSubcodes($coupon_id, $page, $per_page);
    }

    /**
     * Create a new coupon subcode under a coupon
     *
     * @param $coupon_id
     * @param $fields
     * @return mixed
     */
    public function createSubcodes($coupon_id, $fields)
    {
        return $this->__createSubcodes($coupon_id, $fields);
    }

    /**
     * Update a list of coupon subcodes under a coupon
     *
     * @param $coupon_id
     * @param $fields
     * @return Coupon|mixed
     */
    public function updateSubcodes($coupon_id, $fields)
    {
        return $this->__updateSubcodes($coupon_id, $fields);
    }

    /**
     * Delete a specific coupon subcode of a coupon
     *
     * @param $coupon_id
     * @param $coupon_subcode
     * @return bool|mixed
     */
    public function deleteSubcode($coupon_id, $coupon_subcode)
    {
        return $this->__deleteSubcode($coupon_id, $coupon_subcode);
    }

    /**
     * @param $fields
     * @return Coupon|mixed
     */
    private function __create($fields)
    {
        $url = $this->apiDomain . "coupons.json";
        $data = array(
            "coupon" => $fields
        );
        $data = json_decode(json_encode($data), false);
        $coupon = $this->_post($this->accessPoint, $url, $data);
        if (isset($coupon->coupon)) {
            $coupon = $this->__assign($coupon->coupon);
        }
        return $coupon;
    }

    /**
     * @param $coupon_id
     * @param $fields
     * @return Coupon|mixed
     */
    private function __update($coupon_id, $fields)
    {
        $url = $this->apiDomain . "coupons/{$coupon_id}.json";
        $data = array(
            "coupon" => $fields
        );
        $data = json_decode(json_encode($data), false);
        $coupon = $this->_put($this->accessPoint, $url, $data);
        if (isset($coupon->coupon)) {
            $coupon = $this->__assign($coupon->coupon);
            Cache::forget("{$this->apiDomain}.coupons.{$coupon_id}");
        }
        return $coupon;
    }

    /**
     * @param $coupon_id
     * @return bool|mixed
     */
    private function __archive($coupon_id)
    {
        $coupon = $this->___get($coupon_id);
        $url = $this->apiDomain . "coupons/{$coupon_id}.json";
        $result = $this->_delete($this->accessPoint, $url);
        if (is_null($result)) {
            $result = true;
            Cache::forget("{$this->apiDomain}.coupons.{$coupon_id}");
        }
        return $result;
    }

    /**
     * @param $coupon_id
     * @return Coupon|mixed
     */
    private function ___get($coupon_id)
    {
        $url = $this->apiDomain . "coupons/{$coupon_id}.json";
        $coupon = $this->_get($this->accessPoint, $url);
        if (isset($coupon->coupon)) {
            $coupon = $coupon->coupon;
            $coupon = $this->__assign($coupon);
        }
        return $coupon;
    }

    /**
     * @param $coupon_code
     * @param null $product_family_id
     * @return Coupon|mixed
     */
    private function __find($coupon_code, $product_family_id = null)
    {
        $url = $this->apiDomain . "coupons/find.json?code={$coupon_code}";
        if (!is_null($product_family_id)) {
            $url .= "&product_family_id={$product_family_id}";
        }
        $coupon = $this->_get($this->accessPoint, $url);
        if (isset($coupon->coupon)) {
            $coupon = $coupon->coupon;
            $coupon = $this->__assign($coupon);
        }
        return $coupon;
    }

    /**
     * @param $coupon_id
     * @return mixed
     */
    private function __getUsage($coupon_id)
    {
        $url = $this->apiDomain . "coupons/{$coupon_id}/usage.json";
        $usage = $this->_get($this->accessPoint, $url);
        return $usage;
    }

    /**
     * @param $coupon_code
     * @param null $product_family_id
     * @return Coupon|mixed
     */
    private function __validate($coupon_code, $product_family_id = null)
    {
        $url = $this->apiDomain . "coupons/validate.json?code={$coupon_code}";
        if (!is_null($product_family_id)) {
            $url = $url . "&product_family_id=" . $product_family_id;
        }
        $coupon = $this->_get($this->accessPoint, $url);
        if (isset($coupon->coupon)) {
            $coupon = $coupon->coupon;
            $coupon = $this->__assign($coupon);
        }
        return $coupon;
    }

    /**
     * @param $coupon_id
     * @param $page
     * @param $per_page
     * @return mixed
     */
    private function __allSubcodes($coupon_id, $page, $per_page)
    {
        $url = $this->apiDomain . "coupons/{$coupon_id}/codes.json";
        if (!is_null($page) && !is_null($per_page)) {
            $url .= "?page={$page}&per_page={$per_page}";
        }
        $couponSubcodes = $this->_get($this->accessPoint, $url);
        return $couponSubcodes;
    }

    /**
     * @param $coupon_id
     * @param $fields
     * @return mixed
     */
    private function __createSubcodes($coupon_id, $fields)
    {
        $url = $this->apiDomain . "coupons/$coupon_id/codes.json";
        $data = array(
            "codes" => $fields
        );
        $data = json_decode(json_encode($data), false);
        $coupon = $this->_post($this->accessPoint, $url, $data);
        return $coupon;
    }

    /**
     * @param $coupon_id
     * @param $fields
     * @return Coupon|mixed
     */
    private function __updateSubcodes($coupon_id, $fields)
    {
        $url = $this->apiDomain . "coupons/{$coupon_id}/codes.json";
        $data = array(
            "codes" => $fields
        );
        $data = json_decode(json_encode($data), false);
        $coupon = $this->_put($this->accessPoint, $url, $data);
        if (isset($coupon->coupon)) {
            $coupon = $this->__assign($coupon->coupon);
        }
        return $coupon;
    }

    /**
     * @param $coupon_id
     * @param $coupon_subcode
     * @return bool|mixed
     */
    private function __deleteSubcode($coupon_id, $coupon_subcode)
    {
        $url = $this->apiDomain . "coupons/{$coupon_id}/{$coupon_subcode}.json";
        $coupon = $this->_delete($this->accessPoint, $url);
        if (is_null($coupon)) {
            $coupon = true;
        }
        return $coupon;
    }


    /**
     * @param $input_coupon
     * @return Coupon
     */
    private function __assign($input_coupon)
    {
        $coupon = new Coupon($this->accessPoint);
        foreach ($input_coupon as $key => $value) {
            if (property_exists($coupon, $key)) {
                $coupon->$key = $value;
            }
        }
        return $coupon;
    }

}