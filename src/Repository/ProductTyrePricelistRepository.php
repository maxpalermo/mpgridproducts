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

namespace MpSoft\MpGridProducts\Repository;

use Doctrine\ORM\EntityRepository;
use MpSoft\MpGridProducts\Models\ModelProductTyrePricelist;

/**
 * Repository per la gestione delle entità ModelProductTyrePricelist
 */
class ProductTyrePricelistRepository extends EntityRepository
{
    /**
     * Trova tutti i listini prezzi attivi
     *
     * @return array
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.minOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trova i listini prezzi per un distributore specifico
     *
     * @param int $distributorId
     * @return array
     */
    public function findByDistributor(int $distributorId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.distributorId = :distributorId')
            ->setParameter('distributorId', $distributorId)
            ->orderBy('p.minOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trova il prezzo più basso per ogni min_order
     *
     * @return array
     */
    public function findLowestPriceByMinOrder(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT t1.*
            FROM ps_product_tyre_pricelist t1
            INNER JOIN (
                SELECT min_order, MIN(price_unit) as min_price
                FROM ps_product_tyre_pricelist
                WHERE active = 1
                GROUP BY min_order
            ) t2 ON t1.min_order = t2.min_order AND t1.price_unit = t2.min_price
            WHERE t1.active = 1
            ORDER BY t1.min_order ASC
        ';
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        
        return $resultSet->fetchAllAssociative();
    }

    /**
     * Salva un'entità ModelProductTyrePricelist
     *
     * @param ModelProductTyrePricelist $pricelist
     * @return void
     */
    public function save(ModelProductTyrePricelist $pricelist): void
    {
        $this->getEntityManager()->persist($pricelist);
        $this->getEntityManager()->flush();
    }

    /**
     * Elimina un'entità ModelProductTyrePricelist
     *
     * @param ModelProductTyrePricelist $pricelist
     * @return void
     */
    public function delete(ModelProductTyrePricelist $pricelist): void
    {
        $this->getEntityManager()->remove($pricelist);
        $this->getEntityManager()->flush();
    }
}
