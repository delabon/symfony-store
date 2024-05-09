<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CartService $cartService, Request $request): Response
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');

        if (!$this->isCsrfTokenValid('cart_csrf_protection', $csrfToken)) {
            return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);
        }

        return $this->render('cart/cart.html.twig', [
            'cart' => $cartService->get(),
        ]);
    }

    #[Route('/add/{id<\d+>}', name: 'add', methods: ['POST'])]
    public function add(Product $product, CartService $cartService, Request $request): Response
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');

        if (!$this->isCsrfTokenValid('cart_csrf_protection', $csrfToken)) {
            return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);
        }

        try {
            $cartService->add($product);

            return $this->render('cart/cart.html.twig', [
                'product' => $product,
                'cart' => $cartService->get(),
            ]);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (OutOfBoundsException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return new Response('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/remove/{id<\d+>}', name: 'remove', methods: ['DELETE'])]
    public function remove(Product $product, CartService $cartService, Request $request): Response
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');

        if (!$this->isCsrfTokenValid('cart_csrf_protection', $csrfToken)) {
            return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);
        }

        try {
            $cartService->remove($product);

            return $this->render('cart/cart.html.twig', [
                'product' => $product,
                'cart' => $cartService->get(),
            ]);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (OutOfBoundsException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return new Response('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update/{id<\d+>}/{quantity<\d+>}', name: 'update_quantity', methods: ['PATCH'])]
    public function quantity(Product $product, int $quantity, CartService $cartService, Request $request): Response
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');

        if (!$this->isCsrfTokenValid('cart_csrf_protection', $csrfToken)) {
            return new Response('Invalid CSRF token', Response::HTTP_BAD_REQUEST);
        }

        try {
            $cartService->quantity($product, $quantity);

            return $this->render('cart/cart.html.twig', [
                'product' => $product,
                'cart' => $cartService->get(),
            ]);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (OutOfBoundsException $e) {
            return new Response($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return new Response('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
