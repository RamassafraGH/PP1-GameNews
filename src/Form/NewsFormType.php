<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\News;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class NewsFormType extends AbstractType
{
    /**
     * NewsFormType
     *
     * Formulario para crear y editar noticias. Incluye campos para el título,
     * subtítulo, cuerpo, selección de categorías/etiquetas, imagen destacada
     * (campo no mapeado) y estado (borrador/publicado).
     *
     * En la demo: mostrar cómo las relaciones ManyToMany (categories/tags)
     * se representan y cómo `mapped => false` permite manejar archivos
     * manualmente en el controlador.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('subtitle', TextType::class, [
                'label' => 'Subtítulo',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Contenido',
                'attr' => ['class' => 'form-control', 'rows' => 10],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Categorías',
                'attr' => ['class' => 'form-check'],
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'Etiquetas',
                'attr' => ['class' => 'form-check'],
            ])
            ->add('featuredImageFile', FileType::class, [
                'label' => 'Imagen Destacada',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Por favor sube una imagen JPG o PNG válida',
                    ]),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Estado',
                'choices' => [
                    'Borrador' => 'draft',
                    'Publicar' => 'published',
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => News::class,
        ]);
    }
}