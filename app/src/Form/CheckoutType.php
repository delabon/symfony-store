<?php

namespace App\Form;

use App\DTO\CheckoutDTO;
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

class CheckoutType extends AbstractType
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
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('address', TextareaType::class)
            ->add('country', EnumType::class, [
                'class' => Country::class,
                'choice_label' => fn (Country $country) => $country->label()
            ])
            ->add('city', TextType::class)
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'placeholder' => '81398'
                ],
            ])
            ->add('ccNumber', TextType::class, [
                'label' => 'Card number',
                'attr' => [
                    'placeholder' => '1234123412341234'
                ],
            ])
            ->add('ccDate', TextType::class, [
                'label' => 'Card expiration date',
                'attr' => [
                    'placeholder' => '05/29',
                    'maxlength' => '5'
                ],
            ])
            ->add('ccCvc', TextType::class, [
                'label' => 'CVC code',
                'attr' => [
                    'placeholder' => '547',
                    'maxlength' => '5'
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
            'data_class' => CheckoutDTO::class,
        ]);
    }
}
