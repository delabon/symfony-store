<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReceiptController extends AbstractController
{
    #[Route('/receipt/{uid}', name: 'app_receipt_show')]
    public function show(string $uid): Response
    {
        dd($uid);

        return $this->render('receipt/index.html.twig', [
            'controller_name' => 'ReceiptController',
        ]);
    }
}
