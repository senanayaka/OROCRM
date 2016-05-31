<?php

namespace Talliance\Bundle\ApiBundle\Entity\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class RestEntityRepository extends EntityRepository
{
    private $singletonQb;
    private $qbAlias;

    public function setQbAlias($value)
    {
        $this->qbAlias = $value;
    }

    public function getQbAlias()
    {
        return $this->qbAlias ? $this->qbAlias : 'e';
    }

    /**
     * Get Singleton Query Builder
     */
    public function getSingletonQb()
    {
        if (null === $this->singletonQb) {
            $this->singletonQb = $this->createQueryBuilder($this->getQbAlias());
        }

        return $this->singletonQb;
    }

    public function resetSingletonQb()
    {
        $this->singletonQb = $this->createQueryBuilder($this->getQbAlias());
    }

    /**
     *
     * @param string $type and|or|null
     */
    public function getWhereMethod($type = 'and')
    {
        return $this->getSingletonQb()->getParameters()->count() ? "{$type}Where" : 'where';
    }

    /**
     * getIdentifierKey
     *
     * @return int timestamp
     */
    public function getIdentifierKey()
    {
        return md5(uniqid(rand(), true));
    }

    public function getSingletonQbResult($hydrateType = null)
    {
        return $this->getSingletonQb()->getQuery()->getResult($hydrateType);
    }

    public function getSingletonQbCount()
    {
        $qb     = clone $this->getSingletonQb();
        $alias  = $qb->getRootAlias();

        return $qb->select("count($alias)")->getQuery()->getSingleScalarResult();
    }

    public function getListBy($filters = array(), $limit = 10, $page = 1, $offset = 0, $orderBy = null)
    {
        $qb = $this->createQueryBuilder($this->getQbAlias());
        $this->setWhereConditions($qb, $filters);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $this->setOrderBy($qb, $orderBy);

        return $qb->getQuery()->getResult();
    }

    /**
     * setOrderBy
     *
     * @param QueryBuilder $qb
     * @param array $orderBy
     */
    protected function setOrderBy(&$qb, $orderBy)
    {
        if (is_array($orderBy)) {
            $dir = reset($orderBy);
            if (is_string($dir)) {
                $alias = $qb->getRootAlias();
                $qb->orderBy("$alias." . key($orderBy), $dir);
            }
        }
    }

    /**
     * setWhereConditions
     *
     * filters: array(
     *      array('columnName' => array('condition' => 'value')),
     *      array('columnName' => array('orCondition' => 'value')),
     *      ...
     *      array('columnName' => array('or=' => 'value')),
     *      ...
     * )
     *
     * @param $qb
     * @param $filters
     */
    protected function setWhereConditions(&$qb, $filters)
    {
        if (is_array($filters)) {
            $columns = $this->getClassMetadata()->columnNames;
            $associationMappings = $this->getClassMetadata()->associationMappings;

            foreach ($filters as $filter) {
                $fieldName = key($filter);
                $addWhere = false;
                if (isset($columns[$fieldName])) {
                    $addWhere = true;
                } elseif (isset($associationMappings[$fieldName])) {
                    $associationType = $associationMappings[$fieldName]['type'];
                    if ($associationType == ClassMetadataInfo::MANY_TO_ONE) {
                        $addWhere = true;
                    }
                }

                if ($addWhere) {
                    $alias      = $qb->getRootAlias();
                    $cond       =  $filter[$fieldName];
                    $bindId     = $fieldName . $this->getIdentifierKey();
                    $condition  = strpos(strtoupper(key($cond)), 'OR') === false ? key($cond) : str_replace('or', '', key($cond));
                    $whereType  = strpos(strtoupper($condition), 'OR') === false ? 'and' : 'or';
                    $bind       = strpos(strtoupper($condition), 'IN') === false ? ":$bindId" : "(:$bindId)";
                    $value      = reset($cond);
                    $func       = $qb->getParameters()->count() ? "{$whereType}Where" : 'where';

                    $qb->$func("$alias.$fieldName $condition $bind")
                        ->setParameter($bindId, $value);
                }
            }
        }
    }

    /**
     * addFilters
     *
     * @param array $filters
     * @return TicketRepository | NULL
     */
    public function addFilters($filters = array())
    {
        $qb = $this->getSingletonQb();
        $this->setWhereConditions($qb, $filters);

        return $this;
    }

    public function addSingletonQbPager($page, $limit)
    {
        $page   = $page ? $page : 0;
        if ($limit) {
            $offset = $page > 0 ? ($page - 1) * $limit : 0;
            $qb = $this->getSingletonQb();
            $qb->setFirstResult($offset);
            $qb->setMaxResults($limit);
        }
    }
}