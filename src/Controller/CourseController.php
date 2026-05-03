<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses')]
class CourseController extends AbstractController
{
    #[Route('', name: 'app_courses')]
    public function index(CourseRepository $courseRepository): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'app_course_show')]
    public function show(string $slug, CourseRepository $courseRepository): Response
    {
        $course = $courseRepository->findOneBy(['slug' => $slug]);

        if (!$course) {
            throw $this->createNotFoundException('Cours non trouvé.');
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
        ]);
    }
}
