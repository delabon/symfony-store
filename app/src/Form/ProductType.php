<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Enum\ProductStatusEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ProductType extends AbstractType
{
    public function __construct(
        private readonly FormListenerFactory $formListenerFactory
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
            ])
            ->add('slug', textType::class, [
                'empty_data' => '',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'empty_data' => '',
            ])
            ->add('price', NumberType::class, [
                'empty_data' => '',
            ])
            ->add('salePrice', NumberType::class, [
                'empty_data' => '',
            ])
            ->add('quantity', NumberType::class, [
                'empty_data' => '',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('status', EnumType::class, [
                'class' => ProductStatusEnum::class,
            ])
            ->add('thumbnailFile', FileType::class, [
                'label' => 'Thumbnail',
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,
                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,
                // unmapped fields can't define their validation using attributes
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new Image([
                        'maxSize' => '1M',
                        'minHeight' => '500',
                        'minHeightMessage' => 'The minimum height is 500px.',
                        'minWidth' => '500',
                        'minWidthMessage' => 'The minimum width is 500px.',
                        'maxWidth' => '2500',
                        'maxWidthMessage' => 'The maximum width is 2500px.',
                        'maxHeight' => '2500',
                        'maxHeightMessage' => 'The maximum height is 2500px.',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid thumbnail.',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, $this->formListenerFactory->autoSlug('name'))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->formListenerFactory->timestamps())
            ->addEventListener(FormEvents::POST_SUBMIT, $this->formListenerFactory->setOwner())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
