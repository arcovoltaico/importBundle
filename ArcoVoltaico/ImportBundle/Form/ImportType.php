<?php

namespace ArcoVoltaico\ImportBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ImportType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $loading = "sending";
$date = new \DateTime();
$now = $date->format('YmdHis');
        $builder->add('name', 'text', array(
                    'label' => false,
                    'data' => 'sync'.$now,
                    'attr' => array('class' => 'readonly')
                ))
                ->add('file', 'file', array(
                    'label' => false,
                    'attr' => array('class' => 'center'),
                    'constraints' => array(
                        new Assert\File(array(
                            'maxSize' => '20M', //1024k / 4.2M / 15b
                            'mimeTypes' => array(
                                'application/pdf',
                                'application/x-pdf',
                                'application/xml'
                            ),
                            'mimeTypesMessage' => 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.',
                            'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}',
                                )
                        ))
                        )
                )
             


        ;



        $builder->add('save', 'submit', array('label' => 'save', 'attr' => array('data-loading-text' => $loading, 'class' => "btn btn-primary")));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ArcoVoltaico\ImportBundle\Entity\Import'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'ArcoVoltaico_importbundle_import';
    }

}
