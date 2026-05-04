<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Message;
use App\Form\TeacherType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/teachers')]
#[IsGranted('ROLE_ADMIN')]
class AdminTeacherController extends AbstractController
{
    #[Route('/', name: 'app_admin_teacher_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        // Find users with ROLE_TEACHER
        $qb = $userRepository->createQueryBuilder('u');
        $qb->where('u.roles LIKE :role')
           ->setParameter('role', '%"ROLE_TEACHER"%');
        
        $teachers = $qb->getQuery()->getResult();

        return $this->render('admin/teacher/index.html.twig', [
            'teachers' => $teachers,
        ]);
    }

    #[Route('/new', name: 'app_admin_teacher_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $user->setRoles(['ROLE_TEACHER']);
        
        $form = $this->createForm(TeacherType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Handle profile photo upload if provided
            $photoFile = $form->get('profilePhotoFile')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                $photoFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profiles',
                    $newFilename
                );
                $user->setProfilePhoto($newFilename);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Formateur créé avec succès.');
            return $this->redirectToRoute('app_admin_teacher_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/teacher/new.html.twig', [
            'teacher' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_teacher_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(TeacherType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );
            }

            $photoFile = $form->get('profilePhotoFile')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                $photoFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profiles',
                    $newFilename
                );
                $user->setProfilePhoto($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Formateur mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_teacher_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/teacher/edit.html.twig', [
            'teacher' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_teacher_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Relational Safety: Handle related courses
            $courses = $entityManager->getRepository(Course::class)->findBy(['teacher' => $user]);
            foreach ($courses as $course) {
                $course->setTeacher(null); // Keep the course, just remove the teacher association
            }

            // Relational Safety: Handle messages (optional: we can keep messages by setting author to null or a "Deleted User")
            // Since `Message->author` is non-nullable, we must delete messages or change author. Let's delete them for safety.
            $messages = $entityManager->getRepository(Message::class)->findBy(['author' => $user]);
            foreach ($messages as $message) {
                $entityManager->remove($message);
            }

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Formateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_teacher_index', [], Response::HTTP_SEE_OTHER);
    }
}
