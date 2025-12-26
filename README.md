# Dental Clinic Management System

A comprehensive dental clinic management system built with Laravel, Inertia.js, and Vue.js. This application streamlines dental practice operations, patient management, appointment scheduling, and treatment tracking.

## 🚀 Features

### Patient Management
- Complete patient records with medical history
- Digital odontogram for dental charting
- Treatment history and progress tracking
- Document storage and management

### Appointment Scheduling
- Interactive calendar view
- Automated reminders
- Multiple provider scheduling
- Treatment room management

### Treatment & Billing
- Treatment planning and tracking
- Insurance claim processing
- Invoicing and payment tracking
- Financial reporting

### User Management
- Role-based access control (Admin, Dentist, Assistant, Receptionist)
- Secure authentication and authorization
- User activity logging
- Profile management with role-specific dashboards

### AI-Powered Features
- Intelligent chat assistant for patient inquiries
- Automated appointment booking via chat
- Smart treatment recommendations
- Patient communication automation

## 🛠️ Tech Stack

### Backend
- PHP 8.1+
- Laravel 10.x
- MySQL/PostgreSQL
- Redis (for caching and queues)

### Frontend
- Vue.js 3 (Composition API)
- Inertia.js
- Tailwind CSS
- Vite

### AI Integration
- Groq API for natural language processing
- Custom AI service layer
- Chat widget with context-aware responses

## 📦 Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/dental-clinic-app.git
cd dental-clinic-app
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

5. Set up database:
```bash
php artisan migrate --seed
```

6. Compile assets:
```bash
npm run dev
```

7. Start the development server:
```bash
php artisan serve
```

## 🔒 Security

- CSRF protection
- XSS prevention
- SQL injection protection
- Role-based access control
- Secure password hashing
- Rate limiting
- Secure headers

## 📄 License

This project is licensed under the [MIT License](LICENSE).

## 🤝 Contributing

Contributions are welcome! Please read our [contributing guidelines](CONTRIBUTING.md) before submitting pull requests.

## 📞 Support

For support, please open an issue or contact the development team at support@dentalclinic.com

---

*Built with ❤️ by the Dental Clinic Team*
