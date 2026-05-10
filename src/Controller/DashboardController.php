<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_dashboard_admin');
        }

        if ($this->isGranted('ROLE_TEACHER')) {
            return $this->redirectToRoute('app_dashboard_teacher');
        }

        return $this->redirectToRoute('app_dashboard_student');
    }

    #[Route('/student', name: 'app_dashboard_student')]
    public function student(): Response
    {
        $user = $this->getUser();
        $enrolledCourses = $user->getEnrolledCourses();

        return $this->render('dashboard/student.html.twig', [
            'enrolledCourses' => $enrolledCourses,
        ]);
    }

    #[Route('/teacher', name: 'app_dashboard_teacher')]
    #[IsGranted('ROLE_TEACHER')]
    public function teacher(CourseRepository $courseRepository): Response
    {
        $user = $this->getUser();
        $courses = $courseRepository->findBy(['teacher' => $user]);

        return $this->render('dashboard/teacher.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/admin', name: 'app_dashboard_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(UserRepository $userRepository, CourseRepository $courseRepository): Response
    {
        $teachers = $userRepository->findUsersByRole('ROLE_TEACHER');
        $students = $userRepository->findUsersByRole('ROLE_USER');
        $courses = $courseRepository->findAll();

        return $this->render('dashboard/admin.html.twig', [
            'teachers' => $teachers,
            'students' => $students,
            'courses' => $courses,
        ]);
    }
}
