<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CartService
{
    public function __construct(
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    )
    {
    }

    /**
     * @throws Exception
     */
    public function add(Product $product): void
    {
        if (!$product->getId()) {
            throw new InvalidArgumentException('Product must have an ID');
        }

        if (!$this->productRepository->find($product->getId())) {
            throw new OutOfBoundsException('Product not found');
        }


        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            $cart[$product->getId()] = 0;
        }

        $cart[$product->getId()]++;
        $this->requestStack->getSession()->set('cart', $cart);
    }

    public function get(): array
    {
        $cart = $this->requestStack->getSession()->get('cart', []);
        $products = $this->productRepository->findBy(['id' => array_keys($cart)]);
        $items = [];
        $total = 0;

        foreach ($products as $product) {
            $items[] = [
                'product' => $product,
                'quantity' => $cart[$product->getId()],
            ];
            $total += $cart[$product->getId()] * $product->getSalePrice();
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    public function remove(Product $product): void
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

        $cart = $this->requestStack->getSession()->get('cart', []);

        if (!array_key_exists($product->getId(), $cart)) {
            throw new OutOfBoundsException('Product not in cart');
        }

        $cart[$product->getId()] = $quantity;
        $this->requestStack->getSession()->set('cart', $cart);
    }
}