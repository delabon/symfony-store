<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ProductStatusEnum;
use App\Exception\ProductOutOfStockException;
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

        if ($product->getStatus() !== ProductStatusEnum::PUBLISHED) {
            throw new LogicException('Product is not active');
        }

        if ($product->getQuantity() === 0) {
            throw new ProductOutOfStockException();
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

        $this->validateQuantityBeforeAdding($product, $cart->getItems()[$product->getId()] ?? 0);
        $cart->setUpdatedAt(new DateTimeImmutable());
        $cart->addItem($product->getId());
        $this->cartRepository->save($cart);
    }

    private function addToSession(Product $product): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            $cart[$product->getId()] = 0;
        }

        $this->validateQuantityBeforeAdding($product, $cart[$product->getId()]);
        $cart[$product->getId()]++;
        $this->requestStack->getSession()->set('cart', $cart);
    }

    /**
     * @param Product $product
     * @param $cart
     * @return void
     */
    private function validateQuantityBeforeAdding(Product $product, $cart): void
    {
        if ($product->getQuantity() !== -1) {
            $cartProductQuantity = $cart;

            if ($cartProductQuantity + 1 > $product->getQuantity()) {
                throw new InvalidArgumentException('Quantity exceeds available stock');
            }
        }
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

    public function getQuantity(): int
    {
        return $this->get()['quantity'];
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
                'quantity' => 0,
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
        $products = $this->productRepository->findBy(['id' => array_keys($cartItems)]);
        $items = [];
        $total = 0;
        $totalQuantity = 0;

        foreach ($products as $product) {
            if ($product->getStatus() !== ProductStatusEnum::PUBLISHED) {
                continue;
            }

            if ($product->getQuantity() !== -1 && $cartItems[$product->getId()] > $product->getQuantity()) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'quantity' => $cartItems[$product->getId()],
            ];
            $totalQuantity += $cartItems[$product->getId()];
            $total += $cartItems[$product->getId()] * StringToFloatUtility::convert($product->getSalePrice());
        }

        return [
            'items' => $items,
            'total' => $total,
            'quantity' => $totalQuantity,
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

        if (!array_key_exists($product->getId(), $cart)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        unset($cart[$product->getId()]);
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function quantity(Product $product, int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1');
        }

        $user = $this->security->getUser();

        if ($product->getStatus() !== ProductStatusEnum::PUBLISHED) {
            throw new LogicException('Product is not active');
        }

        if ($product->getQuantity() !== -1 && $quantity > $product->getQuantity()) {
            throw new InvalidArgumentException('Quantity exceeds available stock');
        }

        if ($user instanceof User) {
            $this->updateDbQuantity($product, $quantity, $user);
        } else {
            $this->updateSessionQuantity($product, $quantity);
        }
    }

    private function updateDbQuantity(Product $product, int $quantity, User $user): void
    {
        $cart = $user->getCart();

        if (!$cart instanceof Cart) {
            throw new OutOfBoundsException('Cart not found');
        }

        $cart->updateItemQuantity($product->getId(), $quantity);
        $cart->setUpdatedAt(new DateTimeImmutable());
        $this->cartRepository->save($cart);
    }

    private function updateSessionQuantity(Product $product, int $quantity): void
    {
        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        $cart[$product->getId()] = $quantity;
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