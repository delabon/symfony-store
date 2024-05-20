<?php

namespace App\Form;

use App\DTO\PaidCheckoutDTO;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

class PaidCheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('checkoutDetails', CheckoutDetailsType::class)
            ->add('ccNumber', TextType::class, [
                'label' => 'Card number',
                'attr' => [
                    'placeholder' => '1234123412341234',
                    'class' => 'checkout-ccNumber'
                ],
            ])
            ->add('ccDate', TextType::class, [
                'label' => 'Card expiration date',
                'attr' => [
                    'placeholder' => '05/29',
                    'maxlength' => '5',
                    'class' => 'checkout-ccDate'
                ],
            ])
            ->add('ccCvc', TextType::class, [
                'label' => 'CVC code',
                'attr' => [
                    'placeholder' => '547',
                    'maxlength' => '5',
                    'class' => 'checkout-ccCvc'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaidCheckoutDTO::class,
        ]);
    }
}
