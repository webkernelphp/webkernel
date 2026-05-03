<div class="space-y-6">
    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">What is NPM?</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            NPM (Node Package Manager) is the package manager for JavaScript and Node.js. It allows you to discover, download, and manage code packages. NPM powers the JavaScript ecosystem with millions of reusable packages on <strong>npmjs.com</strong>.
        </p>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Security Considerations</h3>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
            <li><strong>Left-Pad Incidents:</strong> Packages can be unpublished, breaking builds; consider caching</li>
            <li><strong>Typosquatting:</strong> Attackers publish packages with names similar to popular ones</li>
            <li><strong>Dependency Bloat:</strong> A single package can pull in hundreds of dependencies recursively</li>
            <li><strong>Audit Required:</strong> Run <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">npm audit</code> regularly to identify known vulnerabilities</li>
            <li><strong>Package Takeovers:</strong> Old maintainers leaving projects unpatched is a real risk</li>
        </ul>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">How It Works</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            NPM reads <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">package.json</code> to get dependency specs, downloads packages from the NPM registry, and creates <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">package-lock.json</code> to lock versions for reproducible installs.
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            The <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">node_modules/</code> directory contains all installed packages. Never commit it; use <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded text-xs">npm install</code> to restore.
        </p>
    </div>

    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Resources</h3>
        <div class="space-y-2">
            <a href="https://www.npmjs.com" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                NPM Registry
            </a>
            <a href="https://docs.npmjs.com" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                Documentation
            </a>
            <a href="https://docs.npmjs.com/cli/v10/commands/npm-audit" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                NPM Audit Guide
            </a>
            <a href="https://cheatsheetseries.owasp.org/cheatsheets/Nodejs_Security_Cheat_Sheet.html" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-4 h-4" />
                Node.js Security Guide
            </a>
        </div>
    </div>
</div>
