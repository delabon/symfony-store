<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\EntityStatusEnum;
use App\Repository\FileRepository;
use App\Repository\OrderRepository;
use App\Service\StripeService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/purchases', name: 'app_purchase_')]
class PurchaseController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        OrderRepository $orderRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = (int)$this->getParameter('app_admin_per_page');

        try {
            $paginator = $orderRepository->paginateByCustomer($page, $limit, $this->getUser());
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->render('purchase/index.html.twig', [
            'orders' => $paginator,
            'maxPages' => ceil($paginator->count() / $limit),
            'page' => $page
        ]);
    }

    #[Route('/{id<\d+>}', name: 'show')]
    public function show(
        Order $order,
        FileRepository $fileRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        StripeService $stripeService
    ): Response
    {
        if ($order->getCustomer() != $this->getUser()) {
            $this->addFlash('danger', 'You can only view your purchases.');

            return $this->redirectToRoute('app_purchase_index');
        }

        $files = [];

        foreach ($order->getItems() as $item) {
            /** @var $item OrderItem */
            $product = $item->getProduct();

            if ($product && $product->getStatus() === EntityStatusEnum::PUBLISHED && count($product->getFiles())) {
                $files[$item->getId()] = $fileRepository->findBy(['id' => $product->getFiles()]);
            } elseif (isset($item->getMetadata()['files']) && is_array($item->getMetadata()['files'])) {
                $files[$item->getId()] = $fileRepository->findBy(['id' => $item->getMetadata()['files']]);
            }
        }

        return $this->render('purchase/show.html.twig', [
            'order' => $order,
            'files' => $files,
            'refundCsrfToken' => $csrfTokenManager->getToken('refund_csrf_protection')->getValue(),
            'canRefund' => $stripeService->isInRefundPeriod($order, (int)$this->getParameter('app_refund_days')),
        ]);
    }
}
