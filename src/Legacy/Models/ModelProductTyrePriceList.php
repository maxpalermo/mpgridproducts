<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

class ModelProductTyrePriceList extends ObjectModel
{
    public $id;
    public $id_distributor;
    public $id_pricelist;
    public $name;
    public $country;
    public $country_code;
    public $type;
    public $min_order_1;
    public $min_order_2;
    public $min_order_4;
    public $delivery_time;
    public $stock;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'product_tyre_pricelist',
        'primary' => 'id_t24',
        'fields' => [
            'id_distributor' => ['type' => self::TYPE_INT],
            'id_pricelist' => ['type' => self::TYPE_INT],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 255],
            'country' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 64],
            'country_code' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 2],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 64],
            'min_order_1' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false, 'default' => 0.000000],
            'min_order_2' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false, 'default' => 0.000000],
            'min_order_4' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false, 'default' => 0.000000],
            'delivery_time' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'stock' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE],
            'date_upd' => ['type' => self::TYPE_DATE],
        ],
    ];
}