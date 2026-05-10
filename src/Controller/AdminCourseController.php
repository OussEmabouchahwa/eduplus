<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/courses')]
class AdminCourseController extends AbstractController
{
    #[Route('/', name: 'app_admin_course_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(CourseRepository $courseRepository): Response
    {
        return $this->render('admin/course/index.html.twig', [
            'courses' => $courseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_course_new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('new_course', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_course_index');
        }

        $title = trim($request->request->get('title', ''));
        $description = trim($request->request->get('description', ''));

        if (!$title || !$description) {
            $this->addFlash('error', 'Le titre et la description sont obligatoires.');
            return $this->redirectToRoute('app_admin_course_index');
        }

        $course = new Course();
        $course->setTitle($title);
        $course->setDescription($description);

        // Generate unique slug
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . uniqid();
        $course->setSlug($slug);

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/courses';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadDir, $newFilename);
            $course->setImagePath($newFilename);
        } else {
            $course->setImagePath(''); // nullable – set fallback later
        }

        $em->persist($course);
        $em->flush();

        $this->addFlash('success', '🎉 Formation "' . $title . '" créée avec succès !');
        return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
    }

    #[Route('/{id}', name: 'app_admin_course_show', methods: ['GET'])]
    #[IsGranted('ROLE_TEACHER')]
    public function show(Course $course, UserRepository $userRepository): Response
    {
        // Teachers can only see their own courses
        if (!$this->isGranted('ROLE_ADMIN') && $course->getTeacher() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce cours.');
        }

        $teachers = $userRepository->findUsersByRole('ROLE_TEACHER');
        $students = $userRepository->findUsersByRole('ROLE_USER');

        return $this->render('admin/course/show.html.twig', [
            'course'   => $course,
            'teachers' => $teachers,
            'students' => $students,
        ]);
    }

    /** Assign or remove a teacher from a course */
    #[Route('/{id}/assign-teacher', name: 'app_admin_course_assign_teacher', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function assignTeacher(
        Course $course,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if (!$this->isCsrfTokenValid('assign_teacher' . $course->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
        }

        $teacherId = $request->request->get('teacher_id');

        if ($teacherId === '' || $teacherId === null) {
            $course->setTeacher(null);
            $this->addFlash('success', 'Formateur retiré du cours.');
        } else {
            $teacher = $userRepository->find($teacherId);
            if ($teacher && in_array('ROLE_TEACHER', $teacher->getRoles())) {
                $course->setTeacher($teacher);
                $this->addFlash('success', 'Formateur assigné avec succès.');
            } else {
                $this->addFlash('error', 'Formateur introuvable.');
            }
        }

        $em->flush();
        return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
    }

    /** Enroll a student in a course */
    #[Route('/{id}/enroll/{studentId}', name: 'app_admin_course_enroll', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function enroll(
        Course $course,
        int $studentId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if (!$this->isCsrfTokenValid('enroll' . $course->getId() . $studentId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
        }

        $student = $userRepository->find($studentId);
        if ($student) {
            $course->addStudent($student);
            $em->flush();
            $this->addFlash('success', $student->getFullName() . ' inscrit au cours avec succès.');
        }

        return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
    }

    /** Remove a student from a course */
    #[Route('/{id}/unenroll/{studentId}', name: 'app_admin_course_unenroll', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unenroll(
        Course $course,
        int $studentId,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if (!$this->isCsrfTokenValid('unenroll' . $course->getId() . $studentId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
        }

        $student = $userRepository->find($studentId);
        if ($student) {
            $course->removeStudent($student);
            $em->flush();
            $this->addFlash('success', $student->getFullName() . ' retiré du cours.');
        }

        return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
    }

    /** Edit course group info (title & image) — also used by teachers */
    #[Route('/{id}/edit', name: 'app_admin_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function edit(Course $course, Request $request, EntityManagerInterface $em): Response
    {
        // Teachers can only edit their own courses
        if (!$this->isGranted('ROLE_ADMIN') && $course->getTeacher() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            if ($title) {
                $course->setTitle($title);
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/courses',
                    $newFilename
                );
                $course->setImagePath($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Cours mis à jour.');
            return $this->redirectToRoute('app_admin_course_show', ['id' => $course->getId()]);
        }

        return $this->render('admin/course/edit.html.twig', ['course' => $course]);
    }
}
