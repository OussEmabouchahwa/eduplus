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
    public function default(\App\Repository\CourseRepository $courseRepo): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            $courses = $courseRepo->findAll();
        } elseif (in_array('ROLE_TEACHER', $roles)) {
            $courses = $courseRepo->findBy(['teacher' => $user]);
        } else {
            $courses = $user->getEnrolledCourses()->toArray();
        }

        if (count($courses) > 0) {
            $firstCourse = reset($courses);
            return $this->redirectToRoute('app_chat_room', ['courseId' => $firstCourse->getId()]);
        }

        $this->addFlash('error', 'Vous n\'avez accès à aucun salon de discussion pour le moment.');
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/chat/{courseId}', name: 'app_chat_room')]
    public function index(int $courseId, MessageRepository $messageRepo, \App\Repository\CourseRepository $courseRepo)
    {
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            $courses = $courseRepo->findAll();
        } elseif (in_array('ROLE_TEACHER', $roles)) {
            $courses = $courseRepo->findBy(['teacher' => $user]);
        } else {
            $courses = $user->getEnrolledCourses();
        }

        $activeCourse = $courseRepo->find($courseId);
        
        if (!$activeCourse) {
            throw $this->createNotFoundException('Le cours demandé n\'existe pas.');
        }

        // Vérification des accès (optionnelle mais recommandée)
        $hasAccess = in_array('ROLE_ADMIN', $roles) 
            || (in_array('ROLE_TEACHER', $roles) && $activeCourse->getTeacher() === $user)
            || $user->getEnrolledCourses()->contains($activeCourse);

        if (!$hasAccess) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce salon.');
        }

        // On récupère les 50 derniers messages de ce cours
        $messages = $messageRepo->findBy(['courseId' => $courseId], ['createdAt' => 'ASC'], 50);

        return $this->render('chat/index.html.twig', [
            'activeCourse' => $activeCourse,
            'courses' => $courses,
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
        $content = $request->request->get('content', '');
        $file = $request->files->get('attachment');

        if (empty($content) && !$file) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $message = new Message();
        $message->setContent($content ?: '');
        $message->setAuthor($this->getUser());
        $message->setCourseId($courseId);

        $attachmentData = null;
        if ($file) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/chat';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = uniqid() . '.' . $file->guessExtension();

            try {
                $file->move($uploadDir, $newFilename);
                $message->setAttachmentPath($newFilename);
                $message->setAttachmentName($file->getClientOriginalName());
                $message->setAttachmentType($file->getMimeType());

                $attachmentData = [
                    'path' => $newFilename,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType()
                ];
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Erreur lors de l\'upload'], 500);
            }
        }

        $em->persist($message);
        $em->flush();

        // On publie sur le Hub Mercure
        $update = new Update(
            "https://edupulse.com/chat/{$courseId}",
            json_encode([
                'content' => $content,
                'author' => $this->getUser()->getUserIdentifier(),
                'createdAt' => $message->getCreatedAt()->format('H:i'),
                'attachment' => $attachmentData
            ])
        );
        $hub->publish($update);

        return new JsonResponse(['status' => 'Envoyé !']);
    }
}
