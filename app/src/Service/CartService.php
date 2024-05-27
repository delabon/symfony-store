<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\EntityStatusEnum;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Utility\StringToFloatUtility;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository,
        private CartRepository $cartRepository,
        private Security $security,
        #[Autowire('%app_currency%')]
        private string $currency
    )
    {
    }

    /**
     * @throws Exception
     */
    public function add(int $productId): void
    {
        if (!$productId) {
            throw new InvalidArgumentException('Product must have a positive ID');
        }

        $product = $this->productRepository->find($productId);

        if (!$product instanceof Product) {
            throw new OutOfBoundsException('Product not found');
        }

        if ($product->getStatus() !== EntityStatusEnum::PUBLISHED) {
            throw new LogicException('Product is not active');
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->addToDb($product, $user);
        } else {
            $this->addToSession($product);
        }
    }

    /**
     * @throws Exception
     */
    private function addToDb(Product $product, User $user): void
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $cart->setItems([]);
            $cart->setCreatedAt(new DateTimeImmutable());
        }

        $cart->setUpdatedAt(new DateTimeImmutable());
        $cart->addItem($product->getId());
        $this->cartRepository->save($cart);
    }

    private function addToSession(Product $product): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!in_array($product->getId(), $cart)) {
            $cart[] = $product->getId();
        }

        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function get(): array
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $this->getFromDb($user);
        }

        return $this->getFromSession();
    }

    public function getTotal(): float
    {
        return $this->get()['total'];
    }

    public function getItems(): array
    {
        return $this->get()['items'];
    }

    public function getHash(): string
    {
        return $this->get()['hash'];
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    private function getFromDb(User $user): array
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            return [
                'items' => [],
                'total' => 0,
                'hash' => '',
                'currency' => $this->currency
            ];
        }

        $return = $this->prepareItemsAndTotal($cart->getItems());
        $return['hash'] = $cart->getHash();
        $return['currency'] = $this->currency;

        return $return;
    }

    private function getFromSession(): array
    {
        return $this->prepareItemsAndTotal($this->requestStack->getSession()->get('cart', []));
    }

    private function prepareItemsAndTotal(array $cartItems): array
    {
        $products = $this->productRepository->findBy(['id' => $cartItems]);
        $items = [];
        $total = 0;

        foreach ($products as $product) {
            if ($product->getStatus() !== EntityStatusEnum::PUBLISHED) {
                continue;
            }

            $items[] = [
                'product' => $product,
            ];
            $total += StringToFloatUtility::convert($product->getSalePrice());
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function remove(Product $product): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->removeFromDb($product, $user);
        } else {
            $this->removeFromSession($product);
        }
    }

    private function removeFromDb(Product $product, User $user): void
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            throw new OutOfBoundsException('Cart not found');
        }

        $cart->removeItem($product->getId());
        $cart->setUpdatedAt(new DateTimeImmutable());
        $this->cartRepository->save($cart);
    }

    public function removeFromSession(Product $product): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!in_array($product->getId(), $cart)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        $cart = array_filter($cart, function ($itemId) use ($product) {
            return $itemId !== $product->getId();
        });
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function clear(): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $this->clearDbCart($user);
        } else {
            $this->clearSessionCart();
        }
    }

    private function clearDbCart(User $user): void
    {
        $cart = $user->getCart();
        $cart->setItems([]);
        $this->cartRepository->save($cart);
    }

    private function clearSessionCart(): void
    {
        $this->requestStack->getSession()->set('cart', []);
    }
}