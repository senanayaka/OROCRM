<?php

namespace Talliance\Bundle\ApiBundle\Handler;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

use Symfony\Component\HttpFoundation\Request;

/**
 * A class encapsulates a business logic responsible to delete entity
 */
class RestDeleteHandler extends DeleteHandler
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle delete entity object.
     *
     * @param mixed            $id
     * @param ApiEntityManager $manager
     * @throws EntityNotFoundException if an entity with the given id does not exist
     * @throws ForbiddenException if a delete operation is forbidden
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        $entity = $manager->find($id);
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $em = $manager->getObjectManager();
        $this->checkPermissions($entity, $em);
        $this->deleteEntity($entity, $em);
        $this->onSuccess($em);
    }

    public function onSuccess($em)
    {
        $em->flush();
    }
}
