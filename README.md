# Laravel Multi-Auth Package

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/chweb/multi-auth)
[![PHP Version](https://img.shields.io/badge/php-%5E8.4-purple.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11.0%20%7C%20%5E12.0-red.svg)](https://laravel.com)

A modern, easy-to-use multi-authentication package for Laravel 12 with full Laravel Breeze integration. Supports Blade, React (Inertia), and Vue (Inertia) stacks.

## ✨ Features

- 🚀 **Quick Setup** - One command to scaffold complete authentication
- 🎨 **Multi-Stack Support** - Blade, React, and Vue with Inertia.js
- 🔍 **Auto-Detection** - Automatically detects and adapts to your stack
- 🔧 **Breeze Integration** - Seamlessly works with Laravel Breeze
- 📦 **Complete Scaffolding** - Models, migrations, controllers, routes, and views
- 🎯 **Guard-Specific** - Separate authentication for different user types
- 💡 **User-Friendly** - Interactive prompts and helpful messages

## 📋 Requirements

- PHP ^8.4
- Laravel ^11.0 | ^12.0
- Composer

## 📦 Installation

Install the package via Composer:

```bash
composer require chweb/multi-auth --dev
```

## 🚀 Quick Start

Create a new authentication guard (e.g., for admins):

```bash
php artisan multi-auth:install admin
```

That's it! The package will:
1. Check if Laravel Breeze is installed (and offer to install it if not)
2. Detect your current stack (Blade, React, or Vue)
3. Generate all necessary files for your new guard
4. Provide instructions for final configuration

## 📖 Usage

### Basic Command

```bash
php artisan multi-auth:install {guard-name}
```

### Examples

Create an admin authentication:
```bash
php artisan multi-auth:install admin
```

Create a manager authentication:
```bash
php artisan multi-auth:install manager
```

Overwrite existing files:
```bash
php artisan multi-auth:install admin --force
```

## 🎯 What Gets Generated?

### For All Stacks

- **Model**: `app/Models/{Guard}.php`
- **Migration**: `database/migrations/{timestamp}_create_{guards}_table.php`
- **Controllers**: `app/Http/Controllers/{Guard}/LoginController.php`, `DashboardController.php`
- **Routes**: `routes/{guard}.php`

### Stack-Specific Files

**Blade Stack:**
- `resources/views/{guard}/login.blade.php`
- `resources/views/{guard}/dashboard.blade.php`
- `resources/views/{guard}/layouts/app.blade.php`
- `resources/views/{guard}/layouts/navigation.blade.php`

**React Stack (Inertia):**
- `resources/js/Pages/{Guard}/Login.jsx`
- `resources/js/Pages/{Guard}/Dashboard.jsx`

**Vue Stack (Inertia):**
- `resources/js/Pages/{Guard}/Login.vue`
- `resources/js/Pages/{Guard}/Dashboard.vue`

## ⚙️ Configuration

After running the install command, update your `config/auth.php`:

```php
'guards' => [
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
],

'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
],

'passwords' => [
    'admins' => [
        'provider' => 'admins',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
    ],
],
```

Register your routes in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')
            ->group(base_path('routes/admin.php'));
    },
)
```

Run the migration:

```bash
php artisan migrate
```

## 🛣️ Routes

For a guard named `admin`, the following routes are created:

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/admin/login` | `admin.login` | Show login form |
| POST | `/admin/login` | `admin.login` | Process login |
| POST | `/admin/logout` | `admin.logout` | Logout |
| GET | `/admin/dashboard` | `admin.dashboard` | Dashboard (protected) |

## 🔐 Authentication

The generated controllers use guard-specific authentication:

```php
// Login
Auth::guard('admin')->attempt($credentials);

// Check authentication
Auth::guard('admin')->check();

// Get authenticated user
Auth::guard('admin')->user();

// Logout
Auth::guard('admin')->logout();
```

## 🎨 Breeze Integration

### Automatic Detection

The package automatically detects if Laravel Breeze is installed. If not, you'll be prompted to install it with your preferred stack.

### Stack Detection

The package detects your stack by checking `package.json`:
- **React**: Looks for `@inertiajs/react`
- **Vue**: Looks for `@inertiajs/vue3`
- **Blade**: Default if no Inertia dependencies found

### Manual Breeze Installation

If you prefer to install Breeze manually:

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade  # or react, vue
```

Then run the multi-auth command:

```bash
php artisan multi-auth:install admin
```

## 📚 Documentation

For detailed documentation, see:
- [Breeze Integration Guide](BREEZE_INTEGRATION.md)
- [Implementation Details](IMPLEMENTATION.md)

## 🔄 Version

Current version: **1.0.0**

Access programmatically:
```php
use Chweb\MultiAuth\MultiAuthServiceProvider;

echo MultiAuthServiceProvider::VERSION; // 1.0.0
```

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for details on updates and changes.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 👨‍💻 Author

**Harshit Chavda**
- Email: harshit.chavda@icloud.com

## 🙏 Credits

This package is inspired by the deprecated `hesto/multi-auth` package, rebuilt for modern Laravel with Breeze integration.
<p style="color:#6c757d; font-size:small;">Disclaimer: This package is built on Laravel Breeze and inspired by the hesto/multi-auth concept. It is provided without any claim to original copyrights.</p>

## ⚠️ Support

For issues, questions, or feature requests, please open an issue on GitHub.

---

Made with ❤️ for the Laravel community
