<?php

namespace Chweb\MultiAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'multi-auth:install {name : The name of the guard (e.g. admin)} {--force : Overwrite existing files}';

    protected $description = 'Install multi-auth scaffolding for a specific guard';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): void
    {
        $name = $this->argument('name');
        $guard = Str::snake($name);
        $singular = Str::singular($guard);
        $plural = Str::plural($guard);
        $model = Str::studly($singular);

        // 0. Breeze Check & Install
        $this->installBreezeIfMissing();
        $stack = $this->detectStack();
        $this->info("Detected stack: {$stack}");
        
        $this->info("Scaffolding multi-auth for guard: {$guard}...");

        // 1. Model
        $this->createModel($model);

        // 2. Migration
        $this->createMigration($plural);

        // 3. Controllers
        $this->createControllers($model, $guard, $stack);

        // 4. Routes
        $this->createRoutes($guard);

        // 5. Views
        $this->createViews($guard, $stack);

        // 6. Config (Auth) - Just display info, don't touch file automatically to avoid breaking
        $this->info("\nDone! Now please add the following to your config/auth.php:");
        
        $this->comment("
    'guards' => [
        '{$guard}' => [
            'driver' => 'session',
            'provider' => '{$plural}',
        ],
    ],

    'providers' => [
        '{$plural}' => [
            'driver' => 'eloquent',
            'model' => App\Models\\{$model}::class,
        ],
    ],
    
    'passwords' => [
        '{$plural}' => [
            'provider' => '{$plural}',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
        ");
    }

    protected function installBreezeIfMissing(): void
    {
        if ($this->isBreezeInstalled()) {
            return;
        }

        if ($this->confirm('Laravel Breeze is not installed. Would you like to install it now?', true)) {
            $this->info('Installing Laravel Breeze...');
            $this->runShellCommand(['composer', 'require', 'laravel/breeze', '--dev']);
            
            $stack = $this->choice('Which Breeze stack would you like to install?', ['blade', 'react', 'vue', 'api'], 'blade');
            $this->info("Installing Breeze stack: {$stack}...");
            $this->runShellCommand(['php', 'artisan', 'breeze:install', $stack]);
        }
    }

    protected function isBreezeInstalled(): bool
    {
        $composerJsonPath = base_path('composer.json');
        if (!file_exists($composerJsonPath)) {
            return false;
        }
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        return isset($composerJson['require']['laravel/breeze']) || isset($composerJson['require-dev']['laravel/breeze']);
    }

    protected function detectStack(): string
    {
        $packageJsonPath = base_path('package.json');
        if (!file_exists($packageJsonPath)) {
            return 'blade';
        }
        
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        $dependencies = $packageJson['dependencies'] ?? [];
        $devDependencies = $packageJson['devDependencies'] ?? [];
        $allDeps = array_merge($dependencies, $devDependencies);

        if (isset($allDeps['@inertiajs/react'])) {
            return 'react';
        }

        if (isset($allDeps['@inertiajs/vue3'])) {
            return 'vue';
        }

        return 'blade';
    }

    protected function runShellCommand(array $command): void
    {
        $process = new \Symfony\Component\Process\Process($command);
        $process->setTty(true);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Command failed: ' . implode(' ', $command));
        }
    }

    protected function createModel(string $model): void
    {
        $path = app_path("Models/{$model}.php");
        $stub = __DIR__ . '/stubs/model.stub';
        
        $this->writeFile($path, $stub, ['{{model}}' => $model]);
    }

    protected function createMigration(string $table): void
    {
        $timestamp = date('Y_m_d_His');
        $path = database_path("migrations/{$timestamp}_create_{$table}_table.php");
        $stub = __DIR__ . '/stubs/migration.stub';

        $this->writeFile($path, $stub, ['{{table}}' => $table]);
    }

    protected function createControllers(string $model, string $guard, string $stack): void
    {
        $namespace = "App\\Http\\Controllers\\" . Str::studly($guard);
        $path = app_path("Http/Controllers/" . Str::studly($guard));
        
        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // Determine stub directory based on stack
        $stubDir = __DIR__ . '/stubs/controllers';
        if ($stack === 'react' || $stack === 'vue') {
            $stubDir .= '/inertia';
        } else {
            $stubDir .= '/blade'; // Assuming current stubs are moved to 'blade' subdir or we use default
        }
        
        // Fallback for now if subdirs don't exist, use current flat structure for blade
        if (!is_dir($stubDir) && $stack === 'blade') {
            $stubDir = __DIR__ . '/stubs/controllers';
        }

        // Login Controller
        $this->writeFile(
            "{$path}/LoginController.php",
            "{$stubDir}/LoginController.stub",
            [
                '{{namespace}}' => $namespace,
                '{{guard}}' => $guard,
                '{{model}}' => $model,
                '{{studlyGuard}}' => Str::studly($guard)
            ]
        );

        // Dashboard/Home Controller
        $this->writeFile(
            "{$path}/DashboardController.php",
            "{$stubDir}/DashboardController.stub",
            [
                '{{namespace}}' => $namespace,
                '{{guard}}' => $guard
            ]
        );
    }

    protected function createRoutes(string $guard): void
    {
        $path = base_path("routes/{$guard}.php");
        $stub = __DIR__ . '/stubs/routes.stub';

        $this->writeFile($path, $stub, [
            '{{guard}}' => $guard,
            '{{studlyGuard}}' => Str::studly($guard)
        ]);

        $this->info("Route file created: routes/{$guard}.php");
        $this->info("Please register this route file in bootstrap/app.php or RouteServiceProvider.");
    }

    protected function createViews(string $guard, string $stack): void
    {
        if ($stack === 'blade') {
            $this->createBladeViews($guard);
        } elseif ($stack === 'react') {
            $this->createReactViews($guard);
        } elseif ($stack === 'vue') {
            $this->createVueViews($guard);
        } else {
            $this->warn("Stack '{$stack}' is not yet fully supported. Falling back to Blade views.");
            $this->createBladeViews($guard);
        }
    }

    protected function createBladeViews(string $guard): void
    {
        $path = resource_path("views/{$guard}");
        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
        
        $layoutsPath = resource_path("views/{$guard}/layouts");
        if (!$this->files->exists($layoutsPath)) {
            $this->files->makeDirectory($layoutsPath, 0755, true);
        }

        // Layout
        $this->writeFile(
            "{$layoutsPath}/app.blade.php",
            __DIR__ . '/stubs/views/blade/layout.blade.stub',
            ['{{guard}}' => $guard]
        );

        // Navigation
        $this->writeFile(
            "{$layoutsPath}/navigation.blade.php",
            __DIR__ . '/stubs/views/blade/navigation.blade.stub',
            ['{{guard}}' => $guard]
        );

        // Login
        $this->writeFile(
            "{$path}/login.blade.php",
            __DIR__ . '/stubs/views/blade/login.blade.stub',
            ['{{guard}}' => $guard]
        );

        // Dashboard
        $this->writeFile(
            "{$path}/dashboard.blade.php",
            __DIR__ . '/stubs/views/blade/dashboard.blade.stub',
            ['{{guard}}' => $guard]
        );
    }

    protected function createReactViews(string $guard): void
    {
        $studlyGuard = Str::studly($guard);
        $path = resource_path("js/Pages/{$studlyGuard}");
        
        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // Login
        $this->writeFile(
            "{$path}/Login.jsx",
            __DIR__ . '/stubs/views/react/Login.jsx',
            ['{{guard}}' => $guard]
        );

        // Dashboard
        $this->writeFile(
            "{$path}/Dashboard.jsx",
            __DIR__ . '/stubs/views/react/Dashboard.jsx',
            [
                '{{guard}}' => $guard,
                '{{studlyGuard}}' => $studlyGuard
            ]
        );
        
        $this->info("React views created in resources/js/Pages/{$studlyGuard}");
    }

    protected function createVueViews(string $guard): void
    {
        $studlyGuard = Str::studly($guard);
        $path = resource_path("js/Pages/{$studlyGuard}");
        
        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }

        // Login
        $this->writeFile(
            "{$path}/Login.vue",
            __DIR__ . '/stubs/views/vue/Login.vue',
            ['{{guard}}' => $guard]
        );

        // Dashboard
        $this->writeFile(
            "{$path}/Dashboard.vue",
            __DIR__ . '/stubs/views/vue/Dashboard.vue',
            [
                '{{guard}}' => $guard,
                '{{studlyGuard}}' => $studlyGuard
            ]
        );
        
        $this->info("Vue views created in resources/js/Pages/{$studlyGuard}");
    }

    protected function writeFile(string $path, string $stubPath, array $replacements): void
    {
        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error("File already exists: {$path}");
            return;
        }

        $content = $this->files->get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $this->files->put($path, $content);
        $this->info("Created: {$path}");
    }
}
