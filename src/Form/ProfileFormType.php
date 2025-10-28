<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ProfileFormType extends AbstractType
{
    /**
     * ProfileFormType
     *
     * Define los campos que el usuario puede editar en su perfil: username y
     * subida de imagen de perfil (campo no mapeado `profileImageFile`).
     *
     * En la demo: explicar el uso de `mapped => false` y cómo se procesa la
     * subida en el controlador (`move()` y uso de `SluggerInterface`).
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nombre de Usuario',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('profileImageFile', FileType::class, [
                'label' => 'Foto de Perfil (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Por favor sube una imagen JPG o PNG válida',
                        'maxSizeMessage' => 'La imagen no puede superar los 2MB',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}