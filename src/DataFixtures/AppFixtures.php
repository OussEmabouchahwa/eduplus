<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un Admin
        $admin = new User();
        $admin->setEmail('admin@edupulse.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('EduPulse');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Création d'un Professeur
        $teacher = new User();
        $teacher->setEmail('teacher@edupulse.com');
        $teacher->setFirstName('Jean');
        $teacher->setLastName('Dupont');
        $teacher->setRoles(['ROLE_TEACHER']);
        $teacher->setPassword($this->hasher->hashPassword($teacher, 'teacher123'));
        $manager->persist($teacher);

        // Création de quelques étudiants
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $student = new User();
            $student->setEmail("student$i@example.com");
            $student->setFirstName("Étudiant $i");
            $student->setLastName("EduPulse");
            $student->setRoles(['ROLE_USER']);
            $student->setPassword($this->hasher->hashPassword($student, 'student123'));
            $manager->persist($student);
            $students[] = $student;
        }

        // Liste des cours basés sur les images existantes
        $coursesData = [
            ['title' => 'Maîtriser Java de A à Z', 'img' => 'courejava.png', 'slug' => 'java-mastery'],
            ['title' => 'CSS Moderne & Flexbox', 'img' => 'css_course.png', 'slug' => 'css-modern'],
            ['title' => 'HTML5 : Les bases du Web', 'img' => 'htmlcours.jpeg', 'slug' => 'html-basics'],
            ['title' => 'PHP 8.3 & Programmation Objet', 'img' => 'phpcours.png', 'slug' => 'php-oop'],
            ['title' => 'React & Redux Ecosystem', 'img' => 'reactcours.png', 'slug' => 'react-expert'],
            ['title' => 'SQL & Design de Bases de Données', 'img' => 'sqlcours.png', 'slug' => 'sql-design'],
            ['title' => 'Symfony 7 : Le Framework Ultime', 'img' => 'symfonycour.jpg', 'slug' => 'symfony-7'],
        ];

        foreach ($coursesData as $index => $data) {
            $course = new Course();
            $course->setTitle($data['title']);
            $course->setDescription('Apprenez ' . $data['title'] . ' avec des experts du secteur. Ce cours couvre tout ce dont vous avez besoin pour devenir un pro.');
            $course->setImagePath('courses/' . $data['img']);
            $course->setSlug($data['slug']);
            $course->setTeacher($teacher);
            $course->setContent('<h1>Bienvenue dans le cours ' . $data['title'] . '</h1><p>Ceci est le contenu pédagogique du cours...</p>');
            
            // On inscrit 2 ou 3 étudiants au hasard à chaque cours
            $numStudents = rand(2, 4);
            $selectedStudents = (array) array_rand($students, $numStudents);
            foreach ($selectedStudents as $studentIndex) {
                $course->addStudent($students[$studentIndex]);
            }

            $manager->persist($course);
        }

        $manager->flush();
    }
}
