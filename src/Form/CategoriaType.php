<?php

namespace App\Form;

use App\Entity\Categoria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoriaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nome', TextType::class, ['label' => 'Nome'])
            ->add('cor', ColorType::class, [
                'label' => 'Cor',
                'help' => 'Aparece na etiqueta da categoria no site e no admin.',
                'html5' => false,
            ])
            ->add('ativa', CheckboxType::class, [
                'label' => 'Ativa',
                'required' => false,
                'help' => 'Categoria inativa não aparece para novos eventos, e os eventos dela saem do site.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Categoria::class]);
    }
}
