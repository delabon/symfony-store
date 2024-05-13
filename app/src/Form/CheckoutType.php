<?php

namespace App\Form;

use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function __construct(
        private readonly FormListenerFactory $formListenerFactory,
        private readonly CartService $cartService,
        private readonly Security $security,
        #[Autowire('%app_currency%')]
        private readonly string $currency = ''
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
            ->add('zipCode')
            ->add('currency', HiddenType::class)
            ->add('total', HiddenType::class)
            ->add('save', SubmitType::class, [
                'label' => 'Checkout Now'
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event) {
                $data = $event->getData();
                $data['currency'] = $this->currency;
                $data['total'] = $this->cartService->get()['total'];
                $event->setData($data);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) {
                $cart = $this->cartService->get();

                /** @var Order $order */
                $order = $event->getData();
                $order->setCustomer($this->security->getUser());

                if (!empty($cart['items'])) {
                    $order->setUser($cart['items'][0]['product']->getUser());
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, $this->formListenerFactory->timestamps())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
