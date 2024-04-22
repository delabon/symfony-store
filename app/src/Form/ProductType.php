<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
            ])
            ->add('description', TextareaType::class, [
                'empty_data' => '',
            ])
            ->add('price', NumberType::class, [
                'empty_data' => '',
            ])
            ->add('sale_price', NumberType::class, [
                'empty_data' => '',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $product = $event->getData();
                $product->setUpdatedAt(new \DateTimeImmutable());
                $product->setUser($this->security->getUser());

                if ($product->getId() === null) {
                    $product->setCreatedAt(new \DateTimeImmutable());
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
