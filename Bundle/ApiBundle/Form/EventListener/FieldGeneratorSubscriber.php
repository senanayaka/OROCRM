<?php

namespace Talliance\Bundle\ApiBundle\Form\EventListener;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class FieldGeneratorSubscriber implements EventSubscriberInterface
{
    protected $request;
    protected $factory;
    protected $fields;

    /**
     * @param Request $request
     * @param FormFactoryInterface $factory
     * @param array $fields
     */
    public function __construct(Request $request, FormFactoryInterface $factory, array $fields)
    {
        $this->request = $request;
        $this->factory = $factory;
        $this->fields  = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        $entity = $event->getData();
        $form   = $event->getForm();

        $formName   = $form->getName();
        
         $requestFields = empty($formName)
                                        ? $this->request->request->all()
                                        : $this->request->request->get($formName);

        if (empty($requestFields) || !is_object($entity)) {
            return ;
        } else {
            if (method_exists($entity, 'getId') && $entity->getId()) {
                $this->removeAllFormChildren($form);
            }

            foreach ($requestFields as $field => $value) {
                if (isset($this->fields[$field]) && !$form->has($field)) {
                    $type    = $this->fields[$field]['type'];
                    $options = $this->fields[$field]['options'];

                    if (!isset($options['auto_initialize'])) {
                        $options['auto_initialize'] = false;
                    }

                    $form->add($this->factory->createNamed($field, $type, null, $options));
                }
            }
        }
    }

    /**
     * removeAllFormChildren
     *
     * @param Form $form
     */
    protected function removeAllFormChildren(Form $form)
    {
        $all = $form->all();
        foreach ($all as $key => $child) {
            $form->remove($key);
        }
    }
}
