<div class="space-y-6">
    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">What is Composer?</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Composer is a dependency manager for PHP. It allows you to declare the libraries your project depends on and it will manage (install/update) them for you. Composer resolves dependency conflicts and ensures all packages are compatible.
        </p>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Security Considerations</h3>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
            <li><strong>Supply Chain Risk:</strong> Packages come from third-party sources; malicious code can be injected</li>
            <li><strong>Outdated Packages:</strong> Vulnerabilities may exist in older versions; keeping packages updated is critical</li>
            <li><strong>Dependency Explosion:</strong> A single package can introduce dozens of transitive dependencies you don't control</li>
            <li><strong>Maintenance:</strong> Packages may become unmaintained; evaluate package health before using</li>
        </ul>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">How It Works</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            Composer reads your <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">composer.json</code> file, downloads packages from <strong>Packagist.org</strong> (the official repository), and creates a <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">composer.lock</code> file with locked versions for reproducible installs.
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            The <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">vendor/</code> directory contains all installed packages. Never commit it; use <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">composer install</code> to restore.
        </p>
    </div>

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Resources</h3>
        <div class="space-y-2">
            <a href="https://getcomposer.org" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                Official Website
            </a>
            <a href="https://getcomposer.org/doc/" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                Documentation
            </a>
            <a href="https://packagist.org" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                Packagist Repository
            </a>
            <a href="https://getcomposer.org/security/" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                Security Advisories
            </a>
        </div>
    </div>
</div>
