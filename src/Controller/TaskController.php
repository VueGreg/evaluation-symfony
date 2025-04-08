<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;

use function Symfony\Component\Clock\now;

#[OA\Response(
    response: 200,
    description: 'Returns the api',
    content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: new Model(type: Task::class, groups: ['full']))
    )
)]

#[Route('/api/task')]
final class TaskController extends AbstractController
{
    #[OA\Tag(name: 'Tasks')]
    #[Route('', name: 'api_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository, SerializerInterface $serializer): JsonResponse
    {
        $tasks = $taskRepository->findAll();
        $data = $serializer->serialize($tasks, 'json', ['groups' => 'task:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[OA\Tag(name: 'Tasks')]
    #[Route('/{user}', name: 'api_task_new', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'description', 'status'],
            properties: [
                new OA\Property(property: 'title', type: 'string', description: 'Title of the Task'),
                new OA\Property(property: 'description', type: 'string', description: 'Description of the Task'),
                new OA\Property(property: 'status', type: 'string', description: 'Status of the Task', enum: ['todo', 'in_progress', 'done']), // Ajout du statut dans la documentation
            ]
        )
    )]
    public function new(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
    
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
    
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
    
        if (!in_array($data['status'], Task::STATUSES)) {
            return new JsonResponse(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
        }
    
        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description']);
        $task->setStatus($data['status']);
        $task->setAssignedUser($user);
        $task->setCreatedAt(now());
    
        $entityManager->persist($task);
        $entityManager->flush();
    
        $taskData = $serializer->serialize($task, 'json', ['groups' => 'task:read']);
        return new JsonResponse($taskData, Response::HTTP_CREATED, [], true);
    }
    


    #[OA\Tag(name: 'Tasks')]
    #[Route('/{id}', name: 'api_task_show', methods: ['GET'])]
    public function show(Task $task, SerializerInterface $serializer): JsonResponse
    {
        $data = $serializer->serialize($task, 'json', ['groups' => 'task:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[OA\Tag(name: 'Tasks')]
    #[Route('/{id}', name: 'api_task_edit', methods: ['PUT'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $task->setTitle($data['title']);
        $task->setDescription($data['description']);

        $entityManager->flush();
        $taskData = $serializer->serialize($task, 'json', ['groups' => 'task:read']);

        return new JsonResponse($taskData, Response::HTTP_OK, [], true);
    }

    #[OA\Tag(name: 'Tasks')]
    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    public function delete(Task $task, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($task);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Task deleted'], Response::HTTP_NO_CONTENT);
    }
}
