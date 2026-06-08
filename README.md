# 🎓 Uplifts

MentorCore is a mobile-first mentorship platform and web-based management system developed as a Final Year Project (FYP). This platform is designed to bridge the gap between students (mentees) and industry professionals (mentors), accelerating career growth through structured guidance and providing relevant job recommendations.

## ✨ Key Features

* **Secure Authentication & Login:** Multi-role registration (Mentee/Mentor) using Laravel Sanctum, featuring an email verification system via TAC codes (Powered by Mailtrap/SMTP).
* **Mentor Search & Matching:** Allows mentees to find nearby mentors or discover profile matches based on industry expertise. *(Future enhancement: NLP-based matching)*.
* **Scheduling & Management:** Built-in booking system to seamlessly schedule and manage mentorship sessions.
* **Live Job Recommendations:** Displays targeted job vacancies pulled directly from the web using a custom automated scraping script (Python, Selenium, BeautifulSoup).
* **Dynamic Dashboard:** An interactive user interface featuring a modern Neumorphic design, pulling and displaying live data from the database in real-time.
* **Payment Gateway Integration:** Integrated payment processing support using the ToyyibPay gateway.

## 🛠️ Tech Stack

**Mobile App (Frontend):**
* [Flutter](https://flutter.dev/) (Dart) - Responsive Neumorphic UI design.
* State Management & HTTP REST API Integration.

**Web & Admin (Frontend):**
* [Next.js](https://nextjs.org/) / React - For the web view and admin management dashboard.

**Server & Database (Backend):**
* [Laravel](https://laravel.com/) (PHP) - RESTful API development and core business logic.
* Laravel Sanctum - API Authentication and Token Management.
* MySQL - Highly structured relational database managing profiles, schedules, users, and jobs.

**Automation & Data:**
* Python (Selenium & BeautifulSoup) - Automated web scraping for job data collection.

---

> **Note:** This project is developed as part of a Bachelor of Computer Science (Hons.) Netcentric Computing Final Year Project (FYP).
