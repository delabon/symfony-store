<?php

namespace App\Form;

use App\DTO\CheckoutDetails;
use App\Service\CartService;
use CountryEnums\Country;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

class CheckoutDetailsType extends AbstractType
{
    public function __construct(
        private readonly CartService $cartService,
        #[Autowire('%app_currency_symbol%')]
        private readonly string $currencySymbol
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => [
                    'class' => 'checkout-firstName'
                ]
            ])
            ->add('lastName', TextType::class, [
                'attr' => [
                    'class' => 'checkout-lastName'
                ]
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'class' => 'checkout-email'
                ]
            ])
            ->add('address', TextareaType::class, [
                'attr' => [
                    'class' => 'checkout-address'
                ]
            ])
            ->add('country', EnumType::class, [
                'class' => Country::class,
                'choice_label' => fn (Country $country) => $country->label(),
                'attr' => [
                    'class' => 'checkout-country'
                ]
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'checkout-city'
                ]
            ])
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'placeholder' => '81398',
                    'class' => 'checkout-zipCode'
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Pay ' . $this->currencySymbol . $this->cartService->get()['total'],
                'attr' => [
                    'class' => 'btn btn-primary btn-checkout'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckoutDetails::class,
        ]);
    }
}
