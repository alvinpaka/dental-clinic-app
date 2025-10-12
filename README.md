<h1 align="center">Dental Clinic App</h1>

Centralized management for patients, appointments, inventory, staff, prescriptions, invoices, and reports. Built with Laravel 10, Inertia.js, and Vue 3.

---

## Prerequisites

- PHP ≥ 8.2
- Composer ≥ 2.5
- Node.js ≥ 18 and npm ≥ 9
- Database (MySQL/MariaDB/PostgreSQL)
- Optional: [Laravel Sail](https://laravel.com/docs/sail) for Docker-based setup

---

## 1. Clone & Install Dependencies

```bash
git clone https://github.com/alvinpaka/dental-clinic-app.git
cd dental-clinic-app

# PHP dependencies
composer install

# Frontend dependencies
npm install
```

---

## 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to reflect your database connection, mail transport, storage driver, and any third-party keys.

---

## 3. Database Setup

```bash
php artisan migrate
# php artisan db:seed   # run when seeders are available
```

For a clean slate:

```bash
php artisan migrate:fresh --seed
```

---

## 4. Running the Application

### Laravel backend

```bash
php artisan serve
# or php artisan serve --host=127.0.0.1 --port=8000
```

### Vite frontend

```bash
npm run dev
# Vite exposes hot module reload at http://localhost:5173
```

Visit the URL output by the artisan server (default `http://127.0.0.1:8000`). Keep both servers running for full-stack development with Inertia.

---

## 5. Building for Production

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Deploy the `public/` folder behind Nginx/Apache or a managed host such as Laravel Forge/Vapor. Ensure `storage/` and `bootstrap/cache/` are writable.

---

## 6. Storage & File Access

```bash
php artisan storage:link
```

Required for serving generated PDFs or uploaded assets.

---

## 7. Testing & Quality

```bash
php artisan test
# npm run lint   # add scripts as needed
```

---

## 8. Helpful Artisan Commands

- `php artisan optimize:clear` – reset app caches
- `php artisan queue:work` – process queued jobs
- `php artisan schedule:work` – run scheduled tasks continuously

---

## 9. Troubleshooting

- Verify `.env` credentials if migrations fail.
- Clear caches after changing config/routes/views: `php artisan optimize:clear`.
- If Vite cannot connect, pass `--host` and check firewall/SSL settings.
- Run `npm install` again after changing front-end dependencies.

---

## Contributing

1. Fork the repository and create a feature branch.
2. Run automated checks (`php artisan test`, linters).
3. Submit a pull request with a concise summary of changes.

---

## License

This project continues to use the [MIT License](https://opensource.org/licenses/MIT) provided by the Laravel starter kit.

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
