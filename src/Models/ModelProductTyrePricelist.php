<?php
/**
 * 2025 MP Soft
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    MP Soft
 * @copyright 2025 MP Soft
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace MpSoft\MpGridProducts\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="ps_product_tyre_pricelist")
 * @ORM\Entity(repositoryClass="MpSoft\MpGridProducts\Repository\ProductTyrePricelistRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ModelProductTyrePricelist
{

    /**
     * @var SymfonyContainer
     */
    private $container;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_t24", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_distributor", type="integer")
     */
    public $distributorId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    public $name;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=64)
     * @Assert\NotBlank()
     * @Assert\Length(max=64)
     */
    public $country;

    /**
     * @var string
     *
     * @ORM\Column(name="country_code", type="string", length=2)
     * @Assert\NotBlank()
     * @Assert\Length(exactly=2)
     */
    public $countryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=64)
     * @Assert\NotBlank()
     * @Assert\Length(max=64)
     */
    public $type;

    /**
     * @var int
     *
     * @ORM\Column(name="min_order_1", type="decimal", precision=20, scale=6, default=0.000000)
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual(0)
     */
    public $minOrder1;

    /**
     * @var int
     *
     * @ORM\Column(name="min_order_2", type="decimal", precision=20, scale=6, default=0.000000)
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual(0)
     */
    public $minOrder2;

    /**
     * @var int
     *
     * @ORM\Column(name="min_order_4", type="decimal", precision=20, scale=6, default=0.000000)
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual(0)
     */
    public $minOrder4;


    /**
     * @var DateTime
     *
     * @ORM\Column(name="delivery_time", type="date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    public $deliveryTime;

    /**
     * @var int
     *
     * @ORM\Column(name="stock", type="integer")
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual(0)
     */
    public $stock;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    public $active = true;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_add", type="datetime")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    public $dateAdd;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="date_upd", type="datetime", nullable=true)
     * @Assert\Type("\DateTime")
     */
    public $dateUpd;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateAdd = new DateTime();
        $this->container = SymfonyContainer::getInstance();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAt()
    {
        $this->dateUpd = new DateTime();
    }

    /**
     * Convert entity to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'distributorId' => $this->distributorId,
            'name' => $this->name,
            'country' => $this->country,
            'countryCode' => $this->countryCode,
            'type' => $this->type,
            'minOrder1' => $this->minOrder1,
            'minOrder2' => $this->minOrder2,
            'minOrder4' => $this->minOrder4,
            'deliveryTime' => $this->deliveryTime->format('Y-m-d'),
            'stock' => $this->stock,
            'active' => $this->active,
            'dateAdd' => $this->dateAdd->format('Y-m-d H:i:s'),
            'dateUpd' => $this->dateUpd ? $this->dateUpd->format('Y-m-d H:i:s') : null,
        ];
    }

    public function insert()
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $em->persist($this);
        $em->flush();
    }
}
