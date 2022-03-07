<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    public function index(Request $request, ProductRepository $productRepository, SessionInterface $session, ManagerRegistry $doctrine, MailerInterface $mailer): Response
    {
        $basket = $session->get('basket', []);
        $total = array_sum(array_map(function($product) {
            return $product->getPrice();
        }, $basket));

        $order = new Order();

        $form = $this->createFormBuilder($order)
            ->add('name', TextType::class)
            ->add('email', TextType::class)
            ->add('address', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'Confirm Order'])
            ->getForm();

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) { 
            $order = $form->getData();

            foreach ($basket as $product) {
                $order->getProducts()->add($productRepository->find($product->getId()));
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($order);
            $entityManager->flush();

            $this->sendEmailConfirmation($order, $mailer);

            $session->set('basket', []);

            return $this->render('confirmation.html.twig');
        }

        return $this->render('checkout.html.twig', [
            'total' => $total,
            'form' => $form->createView(),
        ]);
    }

    private function sendEmailConfirmation(Order $order, MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->from('symfony@test.com')
            ->to(new Address($order->getEmail(), $order->getName()))
            ->subject('Order confirmation')
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context(['order' => $order]);

        $mailer->send($email);
    }
}
