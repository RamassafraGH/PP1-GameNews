<?php

namespace App\Form;

use App\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'label' => 'Motivo de la denuncia',
                'choices' => [
                    'Contenido inapropiado' => 'inappropriate_content',
                    'Lenguaje o comportamiento ofensivo' => 'offensive_language',
                    'Acoso o grooming' => 'harassment',
                    'Spam o publicidad' => 'spam',
                    'Otros' => 'other',
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'form-check-input'],
                'constraints' => [
                    new NotBlank(['message' => 'Debes seleccionar un motivo']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descripción detallada',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Por favor, describe el problema con más detalle...',
                    'maxlength' => 500,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La descripción es obligatoria']),
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'La descripción no puede tener más de {{ limit }} caracteres',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}