# EduPulse ⚡ - Modern Learning Management System

EduPulse est une plateforme LMS (Learning Management System) hybride et ultra-moderne, conçue avec **Symfony 7** et **Tailwind CSS**. Elle offre une expérience utilisateur premium basée sur le **Glassmorphism**, permettant une gestion fluide des cours, des utilisateurs et des communications en temps réel.

---

## 📸 Captures d'écran

### 🏠 Page d'Accueil & Authentification
<div align="center">
  <p><strong>Landing Page</strong></p>
  <img src="scrinshouts/landinPag.png" alt="Landing Page" width="800">
  <br><br>
  <table width="100%">
    <tr>
      <td width="50%" align="center"><strong>Connexion</strong><br><img src="scrinshouts/login.png" alt="Login"></td>
      <td width="50%" align="center"><strong>Inscription</strong><br><img src="scrinshouts/registerPage.png" alt="Register"></td>
    </tr>
  </table>
</div>

### 👨‍💼 Panneau d'Administration
<div align="center">
  <p><strong>Tableau de bord Admin</strong></p>
  <img src="scrinshouts/dashbourd%20admin%20%20.png" alt="Admin Dashboard" width="800">
  <br><br>
  <table width="100%">
    <tr>
      <td width="50%" align="center"><strong>Gestion des Utilisateurs</strong><br><img src="scrinshouts/gere%20les%20utilister%20from%20admin%20.png" alt="User Management"></td>
      <td width="50%" align="center"><strong>Gestion des Cours</strong><br><img src="scrinshouts/see_courses_Section_from_admin_pannel.png" alt="Course Management"></td>
    </tr>
  </table>
</div>

### 🎓 Espace Professeur & Étudiant
<div align="center">
  <p><strong>Dashboard Professeur</strong></p>
  <img src="scrinshouts/dashbourd%20prof.png" alt="Teacher Dashboard" width="800">
  <br><br>
  <table width="100%">
    <tr>
      <td width="50%" align="center"><strong>Liste des Cours</strong><br><img src="scrinshouts/liste_Courses.png" alt="Course List"></td>
      <td width="50%" align="center"><strong>Profil Utilisateur</strong><br><img src="scrinshouts/monProfilepage.png" alt="Profile Page"></td>
    </tr>
  </table>
</div>

### 💬 Communication & Outils
<div align="center">
  <p><strong>Messagerie en Temps Réel (Mercure)</strong></p>
  <img src="scrinshouts/messageSectionr.png" alt="Real-time Chat" width="800">
</div>

---

## 🔐 Comptes de Test

Pour tester les différentes fonctionnalités et tableaux de bord, vous pouvez utiliser les identifiants suivants (Mot de passe par défaut : `password` ou celui défini lors de la migration) :

| Rôle | Email |
| :--- | :--- |
| **Administrateur** | `admin@edupulse.com` |
| **Professeur** | `teacher@edupulse.com` |
| **Étudiant** | `student2@example.com` |

---

## 🚀 Fonctionnalités Clés

- **⚡ Temps Réel** : Chat de groupe par cours propulsé par **Symfony Mercure**.
- **🎨 Design Premium** : Interface moderne "Glassmorphism" avec **Tailwind CSS**.
- **📊 Dashboards Dédiés** : Expérience personnalisée pour les Admins, Professeurs et Étudiants.
- **📚 Gestion de Contenu** : CRUD complet pour les cours, chapitres et inscriptions.
- **🛠 Stack Moderne** : Symfony 7, AssetMapper (sans Webpack), MySQL.
- **🐳 Docker Ready** : Configuration incluse pour un déploiement rapide.

---

## 🛠 Installation & Configuration

1. **Cloner le projet** :
   ```bash
   git clone https://github.com/OussEmabouchahwa/eduplus.git
   cd eduplus
   ```

2. **Configurer l'environnement** :
   Copiez le fichier `.env` et ajustez vos accès à la base de données (MySQL).
   ```bash
   cp .env .env.local
   ```

3. **Installer les dépendances** :
   ```bash
   composer install
   npm install
   ```

4. **Préparer la base de données** :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Lancer le projet** :
   ```bash
   symfony serve
   # Dans un autre terminal pour les assets
   npm run dev
   ```

---

## 🎨 Technologies Utilisées

- **Backend** : Symfony 7.4 + Doctrine ORM
- **Frontend** : Twig + Tailwind CSS + Stimulus
- **Real-time** : Symfony Mercure
- **Database** : MySQL / MariaDB

---
Développé avec ❤️ par **[OussEma](https://github.com/OussEmabouchahwa)**
