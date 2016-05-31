<?php

namespace Talliance\Bundle\ContactlistBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Talliance\Bundle\ContactlistBundle\Entity\magento_contactus_list;
use Symfony\Component\HttpFoundation\Response;


use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Talliance\Bundle\ApiBundle\Controller\Api\Rest\ApiRestController;

/**
 * @RouteResource("clubs")
 * @NamePrefix("trackside_api_")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/contact_list", name="contact_us_list")
     * @Template
     */

    public function indexAction()
    {

        


        return array();
    }


}
