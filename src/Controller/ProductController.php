<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProductController extends AbstractController
{
    /**
     * @Route ("/")
     */
    public function homepage(ProductRepository $productRepository): Response
    {
        $bikes = $productRepository->findBy([]);
        
        return $this->render('homepage.html.twig', [
            'bikes' => $bikes,
        ]);
    }

    /**
     * @Route ("products/{id}")
     */
    public function details($id, Request $request, ProductRepository $productRepository, SessionInterface $session): Response
    {
        $bike = $productRepository->find($id);

        if ($bike === null) {
            throw $this->createNotFoundException('This product does not exist');
        }

        $basket = $session->get('basket', []);

        if ($request->isMethod('POST')) {
            $basket[$bike->getId()] = $bike;
            $session->set('basket', $basket);
        }

        $isInBasket = array_key_exists($bike->getId(), $basket);

        return $this->render('details.html.twig', [
            'bike' => $bike,
            'inBasket' => $isInBasket,
        ]);
    }
}