<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_chat')]
    public function default(): \Symfony\Component\HttpFoundation\Response
    {
        // Redirige vers un salon par défaut (ex: cours #1)
        return $this->redirectToRoute('app_chat_room', ['courseId' => 1]);
    }

    #[Route('/chat/{courseId}', name: 'app_chat_room')]
    public function index(int $courseId, MessageRepository $messageRepo)
    {
        // On récupère les 50 derniers messages de ce cours
        $messages = $messageRepo->findBy(['courseId' => $courseId], ['createdAt' => 'ASC'], 50);

        return $this->render('chat/index.html.twig', [
            'courseId' => $courseId,
            'messages' => $messages,
        ]);
    }

    #[Route('/chat/send/{courseId}', name: 'app_chat_send', methods: ['POST'])]
    public function send(
        int $courseId, 
        Request $request, 
        EntityManagerInterface $em, 
        HubInterface $hub
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (empty($content)) return new JsonResponse(['error' => 'Message vide'], 400);

        $message = new Message();
        $message->setContent($content);
        $message->setAuthor($this->getUser());
        $message->setCourseId($courseId);

        $em->persist($message);
        $em->flush();

        // On publie sur le Hub Mercure
        $update = new Update(
            "https://edupulse.com/chat/{$courseId}",
            json_encode([
                'content' => $content,
                'author' => $this->getUser()->getUserIdentifier(),
                'createdAt' => $message->getCreatedAt()->format('H:i')
            ])
        );
        $hub->publish($update);

        return new JsonResponse(['status' => 'Envoyé !']);
    }
}
