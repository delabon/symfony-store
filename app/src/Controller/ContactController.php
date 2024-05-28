<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact_index')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class, new ContactDTO());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactDTO = $form->getData();

            $email = (new TemplatedEmail())
                ->from($contactDTO->getEmail())
                ->to(new Address($this->getParameter('app_support_email')))
                ->subject('New contact message from ' . $contactDTO->getName())
                ->htmlTemplate('contact/email.html.twig')
                ->context([
                    'name' => $contactDTO->getName(),
                    'emailAddress' => $contactDTO->getEmail(),
                    'message' => $contactDTO->getMessage(),
                ]);

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Your message has been sent.');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('danger', 'An error occurred while sending your message.');
            }

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
