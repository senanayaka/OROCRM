<?php

namespace Talliance\Bundle\ApiBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\ClassUtils;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @NamePrefix("talliance_api_")
 */
class ApiRestController extends RestController
{
    const ITEMS_PER_PAGE = 1000000;

    protected $excludeQueryStringParams;
    protected $enableParsingFilterPatterns;
    protected $parentRepositoryKey;

    public function __construct()
    {
        $this->excludeQueryStringParams = array('page', 'limit', 'sort_by', 'dir');
        $this->setParentRepositoryKey($this->getParentRepositoryKey());
    }

    /**
     * Override method to call #containerInitialized method when container set.
     *
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->preActionInitialized();
    }

    /**
     * Perform some operations after controller initialized and container set.
     */
    protected function preActionInitialized()
    {
        $this->get('session')->set('device', $this->getDevice());
    }

    /**
     * setParentRepositoryKey
     *
     * @param string $value
     */
    protected function setParentRepositoryKey($value)
    {
        $this->parentRepositoryKey = $value;
    }


    /**
     * prepareRequestParams
     *
     * @return array
     */
    protected function prepareRequestParams()
    {
        $page   = (int)$this->getRequest()->get('page', 1);
        $limit  = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);
        $sortBy = $this->getRequest()->get('sort_by');
        $dir    = $this->getRequest()->get('dir', 'ASC');
        $sortBy = $sortBy ? array($sortBy => $dir) : null;

        $filters = $this->getQueryStringFilters();

        return array(
            'filters'   => $filters,
            'page'      => $page,
            'limit'     => $limit,
            'sortBy'    => $sortBy,
        );
    }

    /**
     * getQueryStringFilters
     *
     * @return array
     */
    protected function getQueryStringFilters()
    {
        $params = $this->getRequest()->query->all();
        foreach ($this->excludeQueryStringParams as $param) {
            if (isset($params[$param])) {
                unset($params[$param]);
            }
        }

        $filters = array();
        foreach ($params as $param => $value) {
            $this->modifyQueryStringParameter($param, $value);
            $filters[] = array($param => array('=' => $value));
        }

        $this->parseFilterPatterns($filters);
        $this->modifyQueryStringFilters($filters);

        return $filters;
    }

    /**
     * getListJsonResponse
     *
     * @param array $items
     * @return Response
     */
    public function getListJsonResponse($items = array())
    {
        $result = array();
        foreach ($items as $item) {
            $result[] = $this->getPreparedItem($item);
        }
        unset($items);

        return new Response(json_encode($result), Codes::HTTP_OK);
    }

    /**
     * getDevice
     *
     * @return string
     */
    public function getDevice()
    {
        return $this->getRequest()->headers->get('trackside-device');
    }

    /**
     * parseJsonString
     *
     * @param string $json
     * @return array | NULL
     */
    public function parseJsonString($json)
    {
        $decoder = new \FOS\Rest\Decoder\JsonDecoder();

        return $decoder->decode($json, 'json');
    }

    /**
     * parseFilterPatterns
     *
     * @param array $filters
     */
    protected function parseFilterPatterns(&$filters)
    {
        if ($this->enableParsingFilterPatterns) {
            $conditions = array(
                '_from' => '>=',
                '_to'   => '<=',
                '_after'  => '>',
                '_before' => '<',
            );

            foreach ($filters as $idx => $filter) {
                $key = key($filter);
                $value = reset($filter[$key]);

                foreach ($conditions as $pattern => $cond) {
                    if (( $pos = strrpos($key, $pattern)) !== false) {
                        $rpos = strlen($key) - strlen($pattern);

                        if ($pos === $rpos) {
                            $field = substr_replace($key, '', $pos, strlen($pattern));
                            $filters[$idx] = array($field => array($cond => $value));
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $param
     * @param $value
     */
    protected function modifyQueryStringParameter($param, &$value)
    {
        $definedFilterPatterns   = $this->getDefinedFilterPatterns();
        $definedFilterPatterns[] = '';

        foreach ($definedFilterPatterns as $pattern) {
            $dateField = str_replace($pattern, '', $param);

            if (in_array($dateField, $this->getDefinedDateFilters())) {
                $value = $this->get('talliance_api.helper.date')->formatDateTimeUTC($value);
            }
        }
    }

    /**
     * modifyQueryStringFilters
     *
     * filters: array(
     *      array('columnName' => array('condition' => 'value')),
     *      array('columnName' => array('orCondition' => 'value')),
     *      ...
     *      array('columnName' => array('or=' => 'value')),
     *      ...
     * )
     *
     * @param array $filters
     */
    protected function modifyQueryStringFilters(&$filters)
    {
        //Override your code here
    }

    /**
     * Create new
     *
     * @return Response
     */
    public function handleCreateRequestByCode($id)
    {
        $this->handleCreateRequest();
    }

    /**
     * Create new
     *
     * @return Response
     */
    public function handleCreateRequest()
    {
        $entity = $this->getManager()->createEntity();
        $isProcessed = $this->processForm($entity);

        $result = array();
        if ($isProcessed) {
            $this->markProcessSuccess();
            $entityClass = ClassUtils::getRealClass($entity);
            $classMetadata = $this->getManager()->getObjectManager()->getClassMetadata($entityClass);
            $responseCode = Codes::HTTP_CREATED;
            $result['message'] = $this->getResponseMessage('created');
            $result['resource'] = [
                'id' => $entity->getId(),
                'url' => $this->getCreateResponseUrl($classMetadata->getIdentifierValues($entity))
            ];
        } else {
            $this->markProcessFailure();
            return $this->handleView($this->view($this->getForm(), Codes::HTTP_BAD_REQUEST));
        }

        return new Response(json_encode($result), $responseCode);
    }

    /**
     * Edit entity
     *
     * @param  mixed    $id
     * @return Response
     */
    public function handleUpdateRequest($id)
    {
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            $this->markProcessFailure();
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        $result = array();
        if ($this->processForm($entity)) {
            $this->markProcessSuccess();
            $responseCode = Codes::HTTP_OK;
            $result['message'] = $this->getResponseMessage('updated');
        } else {
            $this->markProcessFailure();
            return $this->handleView($this->view($this->getForm(), Codes::HTTP_BAD_REQUEST));
        }

        return new Response(json_encode($result), $responseCode);
    }

    /**
     * Delete entity
     *
     * @param  mixed    $id
     * @return Response
     */
    public function handleDeleteRequest($id)
    {
        try {
            $this->getDeleteHandler()->handleDelete($id, $this->getManager());
            $this->markProcessSuccess();
        } catch (EntityNotFoundException $notFoundEx) {
            $this->markProcessFailure();
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        } catch (ForbiddenException $forbiddenEx) {
            $this->markProcessFailure();
            return $this->handleView(
                $this->view(['reason' => $forbiddenEx->getReason()], Codes::HTTP_FORBIDDEN)
            );
        }

        $result = array('message' => $this->getResponseMessage('deleted'));
        return new Response(json_encode($result), Codes::HTTP_OK);
    }

    /**
     * getCreateResponseUrl
     *
     * @param $id
     * @return string
     */
    protected function getCreateResponseUrl($id)
    {
        //Override your code here
    }

    /**
     * getCreateResponseMessage
     *
     * @return string
     */
    protected function getResponseMessage($type)
    {
        $mapping = $this->getMappingMessages();

        return isset($mapping[$type]) ? $mapping[$type] : '';
    }

    /**
     * getMappingMessages
     *
     * @return array
     */
    protected function getMappingMessages()
    {
        return array(
            'getBy'   => '',
            'getOne'  => '',
            'created' => '',
            'updated' => '',
            'deleted' => '',
        );
    }

    /**
     * @return array
     */
    protected function getDefinedDateFilters()
    {
        return array('created', 'modified', 'timestamp');
    }

    /**
     * @return array
     */
    protected function getDefinedFilterPatterns()
    {
        return array('_after', '_before', '_from', '_to');
    }

    /**
     * GET entities list
     *
     * @param array $filter
     * @param  int      $page
     * @param  int      $limit
     * @return Response
     */
    public function handleGetListByRequest($filter = array(), $page = 1, $limit = self::ITEMS_PER_PAGE, $orderBy = null)
    {
        $manager = $this->getManager();
        $items = $manager->getListBy($filter, $limit, $page, $orderBy);

        return $this->getListJsonResponse($items);
    }

    /**
     * GET single item
     *
     * @param  mixed    $code
     * @return Response
     */
    public function handleGetOneByRequest($code)
    {
        $item = $this->getManager()->getRepository()->findOneBy(array('code' => $code));

        if ($item) {
            $item = $this->getPreparedItem($item);
        }
        $responseData = $item ? json_encode($item) : '';

        return new Response($responseData, $item ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * Edit entity
     *
     * @param  mixed    $code
     * @return Response
     */
    public function handleUpdateOneRequest($code)
    {
        $entity = $this->getManager()->getRepository()->findOneBy(array('code' => $code));

        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        if ($this->processForm($entity)) {
            $view = $this->view(null, Codes::HTTP_NO_CONTENT);
        } else {
            $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * isIncorrectParent
     *
     * @param $parentId
     * @return Response | Entity
     */
    public function isCorrectParent($parentId)
    {
        $entity = $this->getManager()->getObjectManager()->getRepository($this->parentRepositoryKey)->find($parentId);

        $error = array('message' => 'Not a valid parameter in URL.');
        if (!$entity) {
            return $this->handleView($this->view($error, Codes::HTTP_NOT_FOUND));
        }

        return $entity;
    }

    /**
     * isIncorrectChild
     *
     * @param int $parentId
     * @param int $childId
     * @return Response | Entity
     */
    public function isCorrectChild($parentId, $childId)
    {
        $parent = $this->isCorrectParent($parentId);
        if (!method_exists($parent, 'getId')) {
            return $parent;
        }

        $error = array('message' => 'Not a valid parameter in URL.');
        $child = $this->getManager()->getRepository()->find($childId);
        if (!$child) {
            return $this->handleView($this->view($error, Codes::HTTP_NOT_FOUND));
        }

        if (!$this->hasEntityChild($parent, $child)) {
            return $this->handleView($this->view($error, Codes::HTTP_BAD_REQUEST));
        }

        return $child;
    }

    /**
     * hasEntityChild
     *
     * @param Doctrine Entity $parent
     * @param Doctrine Entity $child
     * @return boolean TRUE if the collection contains the element, FALSE otherwise.
     */
    protected function hasEntityChild($parent, $child)
    {
        $reflect = new \ReflectionClass($child);
        $plurals = array('es', 's', '');
        foreach ($plurals as $ext) {
            $getChildsMethod = 'get' . $reflect->getShortName() . $ext;
            if (method_exists($parent, $getChildsMethod)) {
                return $parent->$getChildsMethod()->contains($child);
            }
        }

        return false;
    }

    protected function markProcessSuccess()
    {
    }

    protected function markProcessFailure()
    {
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }

    /**
     * @return FormInterface
     * @throws \RuntimeException
     */
    public function getForm()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }

    /**
     * @return ApiFormHandler
     * @throws \RuntimeException
     */
    public function getFormHandler()
    {
        throw new \RuntimeException('This method is not implemented yet.');
    }

    /**
     * getParentRepositoryKey
     *
     * @return string
     */
    public function getParentRepositoryKey()
    {
        return '';//Write your Parent Repository Key
    }

    /**
     * getApiVersion
     *
     * @return string
     */
    public function getApiVersion()
    {
        return 'v1';
    }
}
