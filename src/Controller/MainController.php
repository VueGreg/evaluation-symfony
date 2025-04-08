<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_tasks')]
    public function index(EntityManagerInterface $entity_manager): Response
    {
        $tasks = $entity_manager->getRepository(Task::class)->findAll();

        return $this->render('main/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }
}
