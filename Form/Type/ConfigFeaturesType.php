<?php

declare(strict_types=1);

namespace MauticPlugin\HelloWorldBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigFeaturesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'default_world',
            ChoiceType::class,
            [
                'label'             => 'helloworld.default_world',
                'label_attr'        => ['class' => 'control-label'],
                'required'          => true,
                'attr'              => [
                    'class' => 'form-control',
                ],
                'choices'           => [
                    'helloworld.world.mars'    => 'mars',
                    'helloworld.world.earth'   => 'earth',
                    'helloworld.world.saturn'  => 'saturn',
                    'helloworld.world.jupiter' => 'jupiter',
                ],
                'choices_as_values' => true,
            ]
        );
    }
}
