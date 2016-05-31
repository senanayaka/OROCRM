<?php

namespace Talliance\Bundle\ApiBundle\Entity\Manager;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class RestEntityManager extends ApiEntityManager
{
    /**
     * Returns Paginator to paginate throw items.
     *
     * In case when limit and offset set to null QueryBuilder instance will be returned.
     *
     * @param array $filters
     * @param int $limit
     * @param int $page
     * @param null $orderBy
     * @return mixed
     */
    public function getListBy($filters = array(), $limit = 10, $page = 1, $orderBy = null)
    {
        $page = $page > 0 ? $page : 1;
        $orderBy = $orderBy ? $orderBy : $this->getDefaultOrderBy();
        $offset = $this->calculateOffset($page, $limit);
        $result = $this->getRepository()->getListBy($filters, $limit, $page, $offset, $orderBy);

        return $result;
    }

    /**
     * Calculate offset by page
     *
     * @param  int|null $page
     * @param  int $limit
     * @return int
     */
    protected function calculateOffset($page, $limit)
    {
        if (!$page !== null) {
            $page = $page > 0 ? ($page - 1)*$limit : 0;
        }

        return $page;
    }
}
