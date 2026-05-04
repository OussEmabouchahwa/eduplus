<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Message;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    private function handlePhotoUpload($form, User $user, string $projectDir): void
    {
        $photoFile = $form->get('profilePhotoFile')->getData();
        if ($photoFile) {
            $newFilename = uniqid() . '.' . $photoFile->guessExtension();
            $photoFile->move($projectDir . '/public/uploads/profiles', $newFilename);
            $user->setProfilePhoto($newFilename);
        }
    }

    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $allUsers = $userRepository->findAll();
        $admins = $teachers = $students = [];

        foreach ($allUsers as $u) {
            $roles = $u->getRoles();
            if (in_array('ROLE_ADMIN', $roles)) {
                $admins[] = $u;
            } elseif (in_array('ROLE_TEACHER', $roles)) {
                $teachers[] = $u;
            } else {
                $students[] = $u;
            }
        }

        return $this->render('admin/user/index.html.twig', [
            'admins' => $admins,
            'teachers' => $teachers,
            'students' => $students,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', ['user' => $user]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $currentRole = in_array('ROLE_ADMIN', $user->getRoles())
            ? 'ROLE_ADMIN'
            : (in_array('ROLE_TEACHER', $user->getRoles()) ? 'ROLE_TEACHER' : 'ROLE_USER');

        $form = $this->createForm(AdminUserType::class, $user, ['is_edit' => true]);
        $form->get('roles')->setData($currentRole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newRole = $form->get('roles')->getData();
            $user->setRoles($newRole === 'ROLE_USER' ? [] : [$newRole]);

            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }

            $this->handlePhotoUpload($form, $user, $this->getParameter('kernel.project_dir'));
            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Safety: detach courses
            foreach ($em->getRepository(Course::class)->findBy(['teacher' => $user]) as $course) {
                $course->setTeacher(null);
            }
            // Safety: remove messages
            foreach ($em->getRepository(Message::class)->findBy(['author' => $user]) as $message) {
                $em->remove($message);
            }
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
