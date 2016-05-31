<?php

namespace Talliance\Bundle\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\HttpFoundation\Request;

use Trackside\Bundle\ApiBundle\Form\EventListener\FieldGeneratorSubscriber;

class ApiAbstractType extends AbstractType
{
    protected $request;
    protected $factory;
    protected $fields;

    /**
     * @param Request $request
     * @param FormFactoryInterface $factory
     */
    public function __construct(Request $request, FormFactoryInterface $factory)
    {
        $this->request = $request;
        $this->factory = $factory;
        $this->fields  = $this->getFullFields();
    }

    /**
     * buildForm
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->getDefaultFields() as $child) {
            if (isset($this->fields[$child])) {
                $builder->add(
                    $child,
                    $this->fields[$child]['type'],
                    $this->fields[$child]['options']
                );
            }
        }

        $builder->addEventSubscriber(new FieldGeneratorSubscriber($this->request, $this->factory, $this->fields));
    }

    /**
     * getDefaultFields
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return array();
    }

    /**
     * getFullFields
     *
     * @return array
     */
    protected function getFullFields()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '';
    }
}
