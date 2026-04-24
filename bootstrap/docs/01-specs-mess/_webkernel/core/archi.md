# WEBKERNEL ARCHITECTURE — MULTI-TENANT DOMAIN ROUTING

## CONTEXT

**Webkernel** is a sovereign application foundation. Each app owner self-hosts their own instance on **any infrastructure** (shared hosting, VPS, Raspberry Pi with static IP, air-gapped server, home NAS, etc.). Within that instance:

- **One root domain:** `appowner.com` (System Panel + business routing)
- **Multiple business domains:**
    - **Subdomains:** `sales.appowner.com`, `operations.appowner.com` (via wildcard A record)
    - **Separate domains:** `separate-business-1.com`, `another-business.com` (via NS delegation)
- **Single Laravel 13+ application** serving all domains (subdomains + separate domains + root)
- **Single SQLite database** (instance control plane)
- **Per-business databases** (encrypted credentials stored in instance DB)
- **Multi-tenant routing** via HTTP Host header (single code path for all domains)
- **Multi-domain SSL** via Let's Encrypt (per-domain certs, auto-issued and auto-renewed)

**Technology Stack:**

- PHP 8.4+
- Laravel 13+
- Filament 5+
- Livewire 4 + Blaze
- SQLite (instance core)
- MySQL / PostgreSQL / SQLite (per-business)

---

## PART 1: DOMAIN ARCHITECTURE (SUBDOMAINS + NS DELEGATION)

### 1.1 The Setup

App owner deploys Webkernel **anywhere**
(VPS, shared hosting, Raspberry Pi with port forwarding, etc.):

- Server IP: `203.0.113.50`
- Infrastructure: **doesn't matter**

App owner owns one root domain: `appowner.com`

```
appowner.com → 203.0.113.50
```

Now app owner wants multiple businesses:

- **Subdomain approach:** `sales.appowner.com`, `operations.appowner.com`
- **Separate domain approach:** `separate-business.com` (different registrar), `another-org.io`

### 1.2 Subdomain Strategy (Wildcard DNS)

**In app owner's registrar (appowner.com):**

```
Type    Name              Value
A       appowner.com      203.0.113.50          (root)
A       *.appowner.com    203.0.113.50          (wildcard)
CNAME   www               appowner.com
```

**Result:**

- `appowner.com` → `203.0.113.50`
- `sales.appowner.com` → `203.0.113.50`
- `operations.appowner.com` → `203.0.113.50`
- `anything.appowner.com` → `203.0.113.50`

**SSL:** Single wildcard cert `*.appowner.com` covers all subdomains.

**Web server:** One catch-all config (Nginx: `server_name _` or Apache: `ServerName _`)

**Routing:** Laravel middleware reads Host header, looks up `domains` table, renders correct business panel.

### 1.3 Separate Domain Strategy (NS Delegation)

Business owns their own domain: `separate-business.com` at a different registrar (Hostinger, Namecheap, etc.).

They want it to route to Webkernel instance (IP: `203.0.113.50`).

#### Approach A: A Record (Simple)

Business owner adds DNS records at their registrar:

```
Type    Name                Value
A       separate-business.com        203.0.113.50
A       *.separate-business.com      203.0.113.50
CNAME   www                          separate-business.com
```

**Result:** All traffic from `separate-business.com` → `203.0.113.50` → Webkernel.

**Pros:** Simple, one-click setup.
**Cons:** No control over other DNS records (MX, TXT, etc.) for that domain in Webkernel.

#### Approach B: NS Delegation (Full Control)

Business owner changes **nameservers** to point to Webkernel instance.

**In business owner's registrar (separate-business.com):**

Change nameservers from default to:

```
ns1.appowner.com
ns2.appowner.com
```

**Or custom NS names:**

```
dns1.appowner.com
dns2.appowner.com
nsd.webkernel-platform.com
```

**What happens:**

When browser requests `separate-business.com`:

1. Browser DNS resolver asks registrar: "Who controls separate-business.com?"
2. Registrar responds: "NS records point to ns1.appowner.com, ns2.appowner.com"
3. Browser queries ns1.appowner.com: "What's the IP for separate-business.com?"
4. **Webkernel's DNS service responds:** "A record: 203.0.113.50"
5. Browser connects to `203.0.113.50`, receives HTTP request with Host: `separate-business.com`
6. Laravel middleware routes to correct business panel

**Webkernel handles DNS queries:**

```php
// bootstrap/webkernel/src/Services/DnsResolver.php
namespace Webkernel\Services;

use Webkernel\Models\Domain;

class DnsResolver
{
    public function resolveDomain(string $domain, string $type = 'A'): ?string
    {
        // Check if domain exists in Webkernel
        $domainRecord = Domain::where('domain', $domain)
                              ->where('is_active', true)
                              ->first();

        if (!$domainRecord) {
            return null;  // NXDOMAIN
        }

        // Return DNS response based on query type
        return match ($type) {
            'A'     => env('WEBKERNEL_SERVER_IP'),      // 203.0.113.50
            'AAAA'  => env('WEBKERNEL_SERVER_IPV6'),    // optional IPv6
            'MX'    => '10 mail.' . env('APP_DOMAIN'),  // email (optional)
            'TXT'   => null,                             // SPF, DKIM (optional)
            default => null,
        };
    }
}
```

**DNS server implementation (pure PHP):**

Option 1: Use `clue/reactphp-dns`

```bash
composer require clue/reactphp-dns
```

Option 2: Use `amphp/dns`

```bash
composer require amphp/dns amphp/socket
```

```php
// bootstrap/webkernel/src/Commands/DnsServerCommand.php
namespace Webkernel\Commands;

use Amp\Loop;
use Amp\Socket\Server;
use Webkernel\Services\DnsResolver;

class DnsServerCommand extends Command
{
    public function handle()
    {
        $loop = Loop::get();
        $dnsResolver = app(DnsResolver::class);

        // Listen on UDP port 53
        $server = $loop->onReadable(
            socket_create(AF_INET, SOCK_DGRAM, SOL_UDP),
            function($socket) use ($dnsResolver) {
                $buffer = '';
                $from = '';
                $port = 0;

                // Receive DNS query
                socket_recvfrom($socket, $buffer, 1024, 0, $from, $port);

                // Parse DNS query (simplified)
                $query = $this->parseDnsQuery($buffer);
                $domain = $query['domain'] ?? null;
                $type = $query['type'] ?? 'A';

                // Resolve via Webkernel
                $ip = $dnsResolver->resolveDomain($domain, $type);

                // Build DNS response
                $response = $this->buildDnsResponse($ip, $query);

                // Send response back to client
                socket_sendto($socket, $response, strlen($response), 0, $from, $port);
            }
        );

        $loop->run();
    }

    private function parseDnsQuery(string $data): array
    {
        // DNS query parsing logic
        // This is DNS protocol-specific, use library if possible
        return [];
    }

    private function buildDnsResponse(string $ip, array $query): string
    {
        // Build DNS response packet
        return '';
    }
}
```

**Or use existing DNS library:**

```php
// Better: Use Symfony DNS component or similar
composer require knplabs/dns-query
```

**Or delegate to system:**

Simplest approach: Let the OS handle DNS, use `hosts` file for testing, or delegate to external DNS provider (Route53, Cloudflare, etc.) that Webkernel queries.

### 1.4 Business Owner Workflows

#### Scenario A: Simple Subdomain

```
1. App owner creates business: "Sales Team"
2. Webkernel auto-generates: sales.appowner.com
3. Wildcard DNS *.appowner.com already covers it
4. SSL: wildcard cert *.appowner.com
5. Business Panel LIVE at: https://sales.appowner.com

Zero DNS setup. Instant.
```

#### Scenario B: Customer's Domain (A Record)

```
1. Business owner buys: separate-business.com at Hostinger

2. Opens Webkernel System Panel
3. Clicks "Add Custom Domain"
4. Enters: separate-business.com

5. Webkernel shows:
   "Add these DNS records:
    A     @ (root)         203.0.113.50
    A     * (wildcard)     203.0.113.50"

6. Business owner logs into Hostinger
7. Adds A records (2 minutes)

8. Webkernel polls DNS, verifies
   → Issues SSL cert (Let's Encrypt)

9. Business Panel LIVE at: https://separate-business.com

Email: Business owner adds MX records themselves if needed.
```

#### Scenario C: Customer's Domain (NS Delegation)

```
1. Business owner buys: separate-business.com at Namecheap

2. Opens Webkernel System Panel
3. Clicks "Add Custom Domain"
4. Enters: separate-business.com

5. Webkernel shows:
   "Change your nameservers to:
    ns1.appowner.com
    ns2.appowner.com

    (Webkernel will handle all DNS for this domain)"

6. Business owner logs into Namecheap DNS settings
7. Changes nameservers (5 minutes)
8. Webkernel's DNS service begins responding to queries

9. Webkernel polls DNS, verifies
   → Issues SSL cert (Let's Encrypt)

10. Business Panel LIVE at: https://separate-business.com

Email: Business owner configures MX records in Webkernel UI (System Panel)
       Webkernel's DNS service responds with those records.

Added benefit: Business owner can manage all DNS records for
separate-business.com through Webkernel UI (no more Namecheap login needed).
```

### 1.5 Comparison

| Feature                 | Subdomain        | A Record            | NS Delegation           |
| ----------------------- | ---------------- | ------------------- | ----------------------- |
| **Effort**              | Auto             | 2-3 min             | 5-10 min                |
| **SSL**                 | Wildcard cert    | Per-domain          | Per-domain              |
| **Email (MX)**          | Not applicable   | Manual in registrar | Managed in Webkernel    |
| **DNS Control**         | Full (Webkernel) | Limited (registrar) | Full (Webkernel)        |
| **Use Case**            | Internal teams   | Quick setup         | Enterprise/full control |
| **Multiple subdomains** | ∞ (wildcard)     | ∞ (A + wildcard)    | ∞ (DNS service)         |

---

## PART 2: DATABASE ARCHITECTURE

### 2.1 Instance Core (SQLite)

Single file: `/srv/webkernel/storage/database/instance.sqlite`

Contains only control plane data — **never business operational data**.

```sql
-- ============================================
-- AUTHENTICATION & APP OWNERSHIP
-- ============================================

CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR NOT NULL,
    email VARCHAR NOT NULL UNIQUE,
    email_verified_at DATETIME,
    password VARCHAR NOT NULL,
    remember_token VARCHAR,
    avatar_url VARCHAR,
    created_at DATETIME,
    updated_at DATETIME
);

-- App owner (you, the proprietor of this instance)
CREATE TABLE app_owners (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL UNIQUE,
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- BUSINESSES & DOMAIN ROUTING
-- ============================================

CREATE TABLE businesses (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR NOT NULL,
    slug VARCHAR NOT NULL UNIQUE,
    status VARCHAR DEFAULT 'active',
    admin_email VARCHAR NOT NULL,
    created_by CHAR(36),
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Domain routing (THE CRITICAL TABLE)
-- Maps domain → business + panel
CREATE TABLE domains (
    id CHAR(36) PRIMARY KEY,
    domain VARCHAR NOT NULL UNIQUE,           -- e.g. "sales.acmecorp.io"
    business_id CHAR(36) NOT NULL,
    panel_type VARCHAR NOT NULL,              -- 'system' | 'business' | 'module'
    module_id CHAR(36),                       -- NULL unless panel_type='module'
    is_primary BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    ssl_cert_path VARCHAR,
    ssl_key_path VARCHAR,
    ssl_expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
);
CREATE INDEX domains_host ON domains(domain, is_active);
CREATE INDEX domains_business ON domains(business_id);

-- ============================================
-- MODULES
-- ============================================

CREATE TABLE modules (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR NOT NULL UNIQUE,
    vendor VARCHAR NOT NULL,
    slug VARCHAR NOT NULL,
    version VARCHAR NOT NULL,
    status VARCHAR DEFAULT 'enabled',
    config_json TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE business_module_map (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    module_id CHAR(36) NOT NULL,
    is_enabled BOOLEAN DEFAULT 1,
    config_json TEXT,
    created_at DATETIME,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE(business_id, module_id)
);

-- ============================================
-- DATABASE CONNECTIONS (ENCRYPTED)
-- ============================================

-- Per-business or per-module database credentials (encrypted with APP_KEY)
CREATE TABLE db_connections (
    id CHAR(36) PRIMARY KEY,
    business_id CHAR(36) NOT NULL,
    module_id CHAR(36),                       -- NULL = business default
    driver VARCHAR NOT NULL,                  -- 'mysql' | 'pgsql' | 'sqlite'
    host VARCHAR,
    port INTEGER,
    database VARCHAR NOT NULL,
    username VARCHAR,
    password_encrypted TEXT NOT NULL,         -- AES-256-GCM via APP_KEY
    verified_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE SET NULL
);
CREATE INDEX db_conn_lookup ON db_connections(business_id, module_id);

-- ============================================
-- SETTINGS & CONFIGURATION
-- ============================================

CREATE TABLE inst_webkernel_setting_categories (
    key VARCHAR PRIMARY KEY,
    label VARCHAR NOT NULL,
    description TEXT,
    icon VARCHAR,
    sort_order INTEGER DEFAULT 0,
    is_system TINYINT(1) DEFAULT 0,
    meta_json TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE inst_webkernel_settings (
    id CHAR(36) PRIMARY KEY,
    category VARCHAR NOT NULL,
    registry VARCHAR DEFAULT 'webkernel',
    vendor VARCHAR,
    module VARCHAR,
    key VARCHAR NOT NULL,
    type VARCHAR DEFAULT 'text',
    label VARCHAR NOT NULL,
    description TEXT,
    value TEXT,
    default_value TEXT,
    options_json TEXT,
    is_sensitive TINYINT(1) DEFAULT 0,
    is_custom TINYINT(1) DEFAULT 0,
    enum_class VARCHAR,
    introduced_in_version VARCHAR NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (category) REFERENCES inst_webkernel_setting_categories(key) ON DELETE CASCADE,
    UNIQUE(category, key)
);

-- ============================================
-- MODULE ECOSYSTEM
-- ============================================

CREATE TABLE inst_webkernel_releases (
    id CHAR(36) PRIMARY KEY,
    target_type VARCHAR NOT NULL,             -- 'kernel' | 'module'
    target_slug VARCHAR NOT NULL,
    registry VARCHAR NOT NULL,
    tag_name VARCHAR NOT NULL,
    version VARCHAR NOT NULL,
    commit_sha VARCHAR,
    zipball_url VARCHAR,
    tarball_url VARCHAR,
    release_notes TEXT,
    published_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE UNIQUE INDEX releases_unique ON inst_webkernel_releases(target_type, target_slug, tag_name);

CREATE TABLE inst_webkernel_background_tasks (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    type VARCHAR NOT NULL,                    -- 'cert_renew' | 'module_update' | etc
    label VARCHAR NOT NULL,
    payload TEXT,
    status VARCHAR DEFAULT 'pending',        -- 'pending' | 'running' | 'success' | 'failed'
    output TEXT,
    error TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

-- ============================================
-- AUTHENTICATION & REGISTRY
-- ============================================

CREATE TABLE inst_modules_src_keys (
    id CHAR(36) PRIMARY KEY,
    registry VARCHAR NOT NULL,
    vendor VARCHAR,
    token_encrypted TEXT NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE UNIQUE INDEX src_keys_unique ON inst_modules_src_keys(registry, vendor);

CREATE TABLE inst_registry_accounts (
    id CHAR(36) PRIMARY KEY,
    registry VARCHAR NOT NULL,
    account_name VARCHAR NOT NULL,
    account_email VARCHAR,
    account_type VARCHAR NOT NULL,
    token_encrypted TEXT NOT NULL,
    metadata_encrypted TEXT,
    verified TINYINT(1) DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE UNIQUE INDEX registry_accounts_unique ON inst_registry_accounts(registry, account_name);

-- ============================================
-- AUDIT & LOGGING
-- ============================================

CREATE TABLE audit_log (
    id CHAR(36) PRIMARY KEY,
    actor_id CHAR(36),
    action VARCHAR NOT NULL,
    resource_type VARCHAR,
    resource_id CHAR(36),
    changes_json TEXT,
    ip_address VARCHAR,
    user_agent TEXT,
    created_at DATETIME
);
CREATE INDEX audit_log_resource ON audit_log(resource_type, resource_id, created_at);

CREATE TABLE connectors_logs (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR NOT NULL,
    status VARCHAR NOT NULL,
    source VARCHAR NOT NULL,
    vendor VARCHAR,
    slug VARCHAR,
    version VARCHAR,
    result TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE user_privileges (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    privilege VARCHAR NOT NULL,
    user_origin VARCHAR DEFAULT 'internal',
    granted_by CHAR(36),
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE UNIQUE INDEX user_priv_unique ON user_privileges(user_id, privilege);

-- ============================================
-- LARAVEL FRAMEWORK
-- ============================================

CREATE TABLE sessions (
    id VARCHAR PRIMARY KEY,
    user_id CHAR(36),
    ip_address VARCHAR,
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE TABLE cache (
    key VARCHAR PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue VARCHAR NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE TABLE failed_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration VARCHAR NOT NULL,
    batch INTEGER NOT NULL
);

CREATE TABLE notifications (
    id VARCHAR PRIMARY KEY,
    type VARCHAR NOT NULL,
    notifiable_type VARCHAR NOT NULL,
    notifiable_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    read_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE password_reset_tokens (
    email VARCHAR PRIMARY KEY,
    token VARCHAR NOT NULL,
    created_at DATETIME
);
```

### 2.2 Database Connection Resolution (Cascade)

```php
// bootstrap/webkernel/src/Services/DatabaseConnectionResolver.php
namespace Webkernel\Services;

use Webkernel\Models\DbConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DatabaseConnectionResolver
{
    public function resolve(string $businessId, ?string $moduleId = null): Connection
    {
        $config = $this->getConfig($businessId, $moduleId);
        $name = "tenant_{$businessId}_{$moduleId}";

        Config::set("database.connections.{$name}", $config);
        return DB::connection($name);
    }

    private function getConfig(string $businessId, ?string $moduleId): array
    {
        // Try module-specific first
        if ($moduleId) {
            $record = DbConnection::query()
                ->where('business_id', $businessId)
                ->where('module_id', $moduleId)
                ->first();

            if ($record) {
                return $this->buildConfig($record);
            }
        }

        // Fallback: business default
        $record = DbConnection::query()
            ->where('business_id', $businessId)
            ->where('module_id', null)
            ->first();

        if ($record) {
            return $this->buildConfig($record);
        }

        // Fallback: instance default
        return config('database.connections.' . config('database.default'));
    }

    private function buildConfig(DbConnection $record): array
    {
        return [
            'driver'     => $record->driver,
            'host'       => $record->host,
            'port'       => $record->port,
            'database'   => $record->database,
            'username'   => $record->username,
            'password'   => decrypt($record->password_encrypted),
            'charset'    => 'utf8mb4',
            'collation'  => 'utf8mb4_unicode_ci',
            'prefix'     => '',
        ];
    }
}
```

---

## PART 3: DOMAIN ROUTING (HTTP HOST HEADER)

### 3.1 ResolveDomainContext Middleware

```php
// bootstrap/webkernel/src/Http/Middleware/ResolveDomainContext.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Models\Domain;
use Webkernel\Models\Business;

class ResolveDomainContext
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        $domain = Domain::where('domain', $host)
                        ->where('is_active', true)
                        ->first();

        if (!$domain) {
            return response()->view('errors.domain-not-found', ['host' => $host], 404);
        }

        $business = Business::findOrFail($domain->business_id);

        $request->attributes->set('domain', $domain);
        $request->attributes->set('business', $business);
        $request->attributes->set('business_id', $domain->business_id);
        $request->attributes->set('panel_type', $domain->panel_type);
        $request->attributes->set('module_id', $domain->module_id);

        return $next($request);
    }
}
```

### 3.2 Panel Dispatcher

```php
// bootstrap/webkernel/support/routes/web.php
use Webkernel\Http\Middleware\ResolveDomainContext;

Route::middleware(['web', ResolveDomainContext::class])->group(function () {
    Route::get('/', function (Request $request) {
        return match ($request->attributes->get('panel_type')) {
            'system'   => redirect('/system'),
            'business' => redirect('/business'),
            'module'   => redirect('/module'),
            default    => response()->view('errors.invalid-panel', [], 500),
        };
    });
});
```

### 3.3 Filament Panels

```php
// config/filament.php (or FilamentServiceProvider)

// System Panel (app owner only)
$systemPanel = Panel::make('system')
    ->path('system')
    ->domain('system.*')                      // only app owner domain
    ->middleware([
        'auth:sanctum',
        'verified',
        CheckSystemPanelAccess::class,
    ])
    ->resources([
        BusinessResource::class,
        DomainResource::class,
        ModuleResource::class,
    ]);

// Business Panel (business admins)
$businessPanel = Panel::make('business')
    ->path('business')
    ->middleware([
        'auth:sanctum',
        'verified',
        CheckBusinessPanelAccess::class,
    ])
    ->resources([
        ModuleMarketplaceResource::class,
        DomainManagementResource::class,
        TeamResource::class,
    ]);

// Module Panels (module-specific, dynamic)
// Each module registers its own panel with Filament
```

---

## PART 4: SSL CERTIFICATES

### 4.1 Wildcard Certificate (Root Domain)

App owner buys certificate for: `*.acmecorp.io`

Single cert covers:

- `sales.acmecorp.io`
- `operations.acmecorp.io`
- `finance.acmecorp.io`
- etc.

**Via Symfony Process (no shell_exec):**

```php
// bootstrap/webkernel/src/Services/CertificateManager.php
namespace Webkernel\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CertificateManager
{
    public function issueWildcardCert(string $domain): void
    {
        $process = new Process([
            'certbot',
            'certonly',
            '--standalone',
            '--non-interactive',
            '--agree-tos',
            '--email', config('app.admin_email'),
            '-d', $domain,
            '-d', "*.$domain",
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    public function renewCerts(): void
    {
        $process = new Process(['certbot', 'renew', '--quiet']);
        $process->setTimeout(600);
        $process->run();
    }
}
```

**Schedule in kernel:**

```php
// bootstrap/webkernel/src/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(CertificateManager::class)->renewCerts();
    })->daily();
}
```

### 4.2 Per-Business Certificate

When app owner creates subdomain `sales.acmecorp.io`:

Certificate is **automatically covered** by wildcard cert `*.acmecorp.io`.

No additional cert issuance needed.

**That's the power of the approach.**

---

## PART 5: WORKFLOW

### Workflow: App Owner Sets Up

```
1. App owner buys: acmecorp.io
2. Points to server: 203.0.113.50
3. DNS records:
   A     acmecorp.io       203.0.113.50
   A     *.acmecorp.io     203.0.113.50

4. Installs Webkernel on 203.0.113.50
5. System Panel: acmecorp.io (logged in as app owner)

6. Creates Business 1:
   - Name: "Sales"
   - Auto-domain: sales.acmecorp.io
   - Business Panel: sales.acmecorp.io

7. Creates Business 2:
   - Name: "Operations"
   - Auto-domain: operations.acmecorp.io
   - Business Panel: operations.acmecorp.io

8. SSL: wildcard cert *.acmecorp.io covers all subdomains automatically

9. Business admins can log into their respective panels:
   - sales.acmecorp.io → Sales Business Panel
   - operations.acmecorp.io → Operations Business Panel
```

### Workflow: Business Adds Module

```
1. Sales admin logs in → sales.acmecorp.io
2. Clicks "Install CRM"
3. System prompts for database:
   - Use shared (default)
   - Use dedicated (provide MySQL creds)

4. If dedicated:
   - Form: host, database, username, password
   - System encrypts password
   - Stores in db_connections table

5. Creates module domain:
   - crm.acmecorp.io → Sales business + CRM module

6. CRM available at crm.acmecorp.io
   (same wildcard cert covers it)
```

---

## PART 6: ENCRYPTION

### APP_KEY

```bash
php artisan key:generate
# Creates: APP_KEY=base64:xxxx in .env
```

Encrypts:

- `db_connections.password_encrypted`
- `inst_modules_src_keys.token_encrypted`
- `inst_registry_accounts.token_encrypted`

**In code:**

```php
$encrypted = encrypt('my-password');
$decrypted = decrypt($encrypted);
```

Decrypted password exists **only in memory** for the request duration.

---

## PART 7: NO DYNAMIC SERVER CONFIG NEEDED

### What You DON'T Do

❌ No dynamic Nginx VirtualHost creation
❌ No shell_exec / passthru
❌ No writing to `/etc/nginx/sites-available/`
❌ No `systemctl reload nginx`
❌ No Let's Encrypt cert per subdomain
❌ No DNS server

### What You DO

✅ Single static Nginx config (catch-all `server_name _`)
✅ Single wildcard SSL cert (covers all subdomains)
✅ Application reads Host header
✅ Database lookup `domains` table
✅ Render correct panel

**That's the entire elegance.**

---

## PART 8: CODE: EXAMPLE ROUTE

```php
// bootstrap/webkernel/support/routes/web.php

Route::middleware(['web', ResolveDomainContext::class])->group(function () {

    // System Panel (app owner only)
    Route::middleware(CheckSystemAccess::class)
        ->prefix('system')
        ->group(function () {
            Route::get('/', SystemPanelController::class);
            // Filament panels registered via FilamentServiceProvider
        });

    // Business Panel
    Route::middleware(CheckBusinessAccess::class)
        ->prefix('business')
        ->group(function () {
            Route::get('/', BusinessPanelController::class);
        });

    // Module Panels
    Route::middleware(CheckModuleAccess::class)
        ->prefix('{moduleSlug}')
        ->group(function () {
            Route::get('/', ModulePanelController::class);
        });
});
```

---

## PART 9: SECURITY

### Password Encryption

```php
class DbConnection extends Model
{
    protected $casts = [
        'password_encrypted' => 'encrypted',  // Laravel's automatic encryption
    ];
}

// Usage:
$conn = DbConnection::create([
    'business_id' => $businessId,
    'driver' => 'mysql',
    'password' => 'my-secret',  // automatically encrypted on save
]);

$password = $conn->password;  // automatically decrypted when accessed
```

### Audit Trail

```php
class AuditLog extends Model
{
    // Log every important action
}

// In controller:
AuditLog::create([
    'actor_id' => auth()->id(),
    'action' => 'domain_created',
    'resource_type' => 'domain',
    'resource_id' => $domain->id,
    'changes_json' => json_encode($domain->toArray()),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

### APP_KEY Backup

- Store `.env` in secure vault (encrypted, offline)
- If APP_KEY is lost → all encrypted passwords become inaccessible
- Implement key rotation script if needed (re-encrypts all secrets)

---

## PART 10: COMPLETE TABLE REFERENCE

| Table                             | Purpose                        | Critical |
| --------------------------------- | ------------------------------ | -------- |
| `users`                           | Platform accounts              | —        |
| `app_owners`                      | System Panel access            | Yes      |
| `businesses`                      | Businesses on instance         | Yes      |
| `domains`                         | Domain → business routing      | **YES**  |
| `modules`                         | Installed modules              | Yes      |
| `business_module_map`             | Module enablement per business | Yes      |
| `db_connections`                  | Encrypted DB credentials       | **YES**  |
| `inst_webkernel_settings`         | Configuration                  | —        |
| `inst_webkernel_releases`         | Release metadata               | —        |
| `inst_webkernel_background_tasks` | Async jobs                     | —        |
| `inst_modules_src_keys`           | Registry auth (encrypted)      | —        |
| `inst_registry_accounts`          | OAuth tokens (encrypted)       | —        |
| `audit_log`                       | Action audit trail             | —        |
| (Laravel framework)               | Sessions, cache, jobs, etc.    | —        |

---

## PART 12: PANELS ARCHITECTURE (FILAMENT V5)

## PART 12: PANELS ARCHITECTURE (FILAMENT V5)

**Webkernel Panels** serve **sovereign organizations** that own their complete software stack.

Each panel is task-focused:

- **System Panel** = Instance infrastructure (for App Owner)
- **Business Panel** = Organization operations (for Business Admins)
- **Module Panels** = Domain-specific work (for Business Users)

Panel structure: **Resources (CRUD)** + **Pages (Custom Logic)**

---

### 12.1 System Panel (App Owner / Super Admin)

**Access:** Root domain only. Instance-level control.

**Purpose:** Manage the entire Webkernel instance.

```php
// bootstrap/webkernel/src/Providers/Filament/SystemPanelProvider.php
namespace Webkernel\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class SystemPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('system')
            ->path('system')
            ->login()
            ->resources([
                // Organizational Structure
                BusinessResource::class,

                // Instance Software
                ModuleResource::class,

                // Infrastructure & Connectivity
                DomainResource::class,
                DbConnectionResource::class,

                // Auditing & Operations
                AuditLogResource::class,
                BackgroundJobResource::class,

                // Security
                ApiKeyResource::class,
                AppOwnerUserResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\InstanceSettings::class,
                Pages\SystemHealth::class,
                Pages\Backups::class,
                Pages\SSLCertificateManagement::class,
                Pages\UpdatesAndMaintenance::class,
            ])
            ->widgets([
                InstanceMetricsWidget::class,
                InstalledModulesWidget::class,
                CertificateExpirationWidget::class,
                DiskSpaceWidget::class,
                ActiveBusinessesWidget::class,
            ]);
    }
}
```

**System Panel Resources & Pages:**

| Item                      | Type     | Purpose                                                               |
| ------------------------- | -------- | --------------------------------------------------------------------- |
| **Dashboard**             | Page     | Instance overview: businesses, modules, health, alerts                |
| **Businesses**            | Resource | CRUD: register/activate organizations on this instance                |
| **Modules**               | Resource | CRUD: install/enable/disable modules (CRM, HR, Finance, etc.)         |
| **Domains**               | Resource | CRUD: add app owner domains + customer domains (NS delegation)        |
| **DB Connections**        | Resource | CRUD: encrypted credentials for per-business databases                |
| **Instance Settings**     | Page     | Global config: timezone, backup schedule, email, SMTP, updates        |
| **System Health**         | Page     | Real-time: CPU, RAM, disk, queue length, error rates, uptime          |
| **Backups**               | Page     | List backups, restore points, backup schedule, retention policy       |
| **SSL Management**        | Page     | Certificate status, renewal schedule, wildcard cert, per-domain certs |
| **Updates & Maintenance** | Page     | Webkernel version, available updates, migration guide, changelog      |
| **Audit Log**             | Resource | View-only: all instance actions (who, what, when, IP)                 |
| **Background Jobs**       | Resource | Monitor async tasks: cert issuance, backups, module updates           |
| **API Keys**              | Resource | CRUD: app owner API tokens for external integrations                  |
| **App Owner Users**       | Resource | CRUD: other app owner accounts with role-based permissions            |

---

### 12.2 Business Panel (Business Admin / Manager)

**Access:** Business-specific domain (`sales.appowner.com`). Organization-level control.

**Purpose:** Manage the business as a complete organization.

```php
// bootstrap/webkernel/src/Providers/Filament/BusinessPanelProvider.php
namespace Webkernel\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('business')
            ->login()
            ->middleware([
                CheckBusinessAccess::class,
            ])
            ->resources([
                // Organization Structure
                EmployeeResource::class,
                DepartmentResource::class,
                JobTitleResource::class,
                TeamResource::class,

                // Access Management
                UserResource::class,
                RoleResource::class,
                PermissionResource::class,

                // Organizational Setup
                BusinessProfileResource::class,
                BranchOfficeResource::class,

                // Communication
                SharedMailboxResource::class,
                EmailTemplateResource::class,

                // Data & Collections
                CollectionResource::class,  // DB Studio: custom tables/fields

                // Integrations & Extensions
                IntegrationResource::class,
                WebhookResource::class,

                // Finance
                BudgetResource::class,
                ExpenseResource::class,

                // Compliance & Documents
                DocumentTemplateResource::class,
                ComplianceChecklistResource::class,

                // Audit
                BusinessAuditLogResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\OrganizationSettings::class,
                Pages\DataManagement::class,
                Pages\CommunicationHub::class,
                Pages\EnabledModules::class,
                Pages\ComplianceCenter::class,
                Pages\ReportsAndAnalytics::class,
            ])
            ->widgets([
                OrganizationStatsWidget::class,
                EmployeeDirectoryWidget::class,
                UpcomingEventsWidget::class,
                BudgetOverviewWidget::class,
                RecentActivityWidget::class,
                ComplianceStatusWidget::class,
            ]);
    }
}
```

**Business Panel Resources & Pages:**

| Item                      | Type     | Purpose                                                                   |
| ------------------------- | -------- | ------------------------------------------------------------------------- |
| **Dashboard**             | Page     | Business overview: employees, departments, active modules, alerts         |
| **Business Profile**      | Resource | Organization name, logo, legal ID, fiscal info, registration, addresses   |
| **Employees**             | Resource | CRUD: hire, track, manage staff (roles, departments, contact info)        |
| **Departments**           | Resource | CRUD: Accounting, HR, Sales, Operations, etc.                             |
| **Job Titles**            | Resource | CRUD: Manager, Accountant, Developer, etc. + salary bands                 |
| **Teams**                 | Resource | CRUD: Project teams, task forces, cross-functional groups                 |
| **Users**                 | Resource | CRUD: System user accounts (linked to employees or external access)       |
| **Roles & Permissions**   | Resource | CRUD: Define who can do what (Admin, Manager, Employee, Guest)            |
| **Branch Offices**        | Resource | CRUD: Multiple locations, subsidiaries, regional offices                  |
| **Collections**           | Resource | **DB Studio**: Create/manage custom tables at runtime (no migrations)     |
| **Shared Mailboxes**      | Resource | CRUD: Team inboxes (support@, sales@, info@) + permissions                |
| **Email Templates**       | Resource | CRUD: Invoice emails, alerts, notifications, campaigns                    |
| **Integrations**          | Resource | CRUD: Connect to external services (Stripe, API endpoints, webhooks)      |
| **Webhooks**              | Resource | CRUD: Subscribe to internal events (user created, invoice sent, etc.)     |
| **Document Templates**    | Resource | CRUD: Contracts, invoices, reports, agreements                            |
| **Budget & Planning**     | Resource | CRUD: Department budgets, expense tracking, approvals                     |
| **Compliance Checklist**  | Resource | CRUD: Regulations, audit trails, certifications to track                  |
| **Organization Settings** | Page     | Locale, currency, fiscal year, time zone, business type                   |
| **Data Management**       | Page     | Collection management UI, data import/export, backups                     |
| **Communication Hub**     | Page     | Mailbox overview, message center, notification settings                   |
| **Enabled Modules**       | Page     | List active modules (ERP, HR, Finance, CRM), quick links to module panels |
| **Compliance Center**     | Page     | Audit logs, compliance status, certifications, document tracking          |
| **Reports & Analytics**   | Page     | Custom dashboards, data exports, KPIs, trends                             |
| **Business Audit Log**    | Resource | View-only: all actions within this business (who, what, when)             |

---

### 12.3 Module Panels (Business Users / Domain-Specific Workers)

**Access:** Module-specific domains (`crm.appowner.com`, `erp.appowner.com`, `training.appowner.com`).

**Purpose:** Perform actual work within a module (CRM, ERP, Finance, HR, Training Platform, etc.).

Each module defines its own panel with domain-specific resources.

#### Example: CRM Module Panel

```php
// modules/webkernel/crm/src/Filament/CrmPanelProvider.php
namespace Webkernel\Crm\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class CrmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('crm')
            ->path('crm')
            ->login()
            ->middleware([
                CheckModuleAccess::class,
            ])
            ->resources([
                ContactResource::class,
                CompanyResource::class,
                DealResource::class,
                ActivityResource::class,
                TaskResource::class,
                NoteResource::class,
                EmailResource::class,
                CallLogResource::class,
                CampaignResource::class,
                LeadSourceResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\Pipeline::class,
                Pages\CustomerJourney::class,
                Pages\Reports::class,
                Pages\CrmSettings::class,
            ])
            ->widgets([
                SalesPipelineWidget::class,
                RevenueWidget::class,
                TopDealsWidget::class,
                ActivityTimelineWidget::class,
                TeamPerformanceWidget::class,
                ForecastingWidget::class,
            ]);
    }
}
```

| Item                 | Type     | Purpose                                                          |
| -------------------- | -------- | ---------------------------------------------------------------- |
| **Dashboard**        | Page     | Sales overview, pipeline status, key metrics                     |
| **Contacts**         | Resource | CRUD: manage individual contacts (name, email, phone, company)   |
| **Companies**        | Resource | CRUD: manage company records, industry, size, location           |
| **Deals**            | Resource | CRUD: sales opportunities, value, stage, probability, close date |
| **Activities**       | Resource | CRUD: calls, meetings, tasks linked to contacts/deals            |
| **Tasks**            | Resource | CRUD: to-dos, reminders, assignments, due dates                  |
| **Notes**            | Resource | CRUD: internal notes, observations about contacts/deals          |
| **Emails**           | Resource | CRUD: email history, templates, campaigns                        |
| **Call Logs**        | Resource | CRUD: call records, duration, notes, linked to contact           |
| **Campaigns**        | Resource | CRUD: marketing campaigns, segmentation, performance tracking    |
| **Lead Sources**     | Resource | CRUD: how leads originated (website, referral, cold call, etc.)  |
| **Pipeline**         | Page     | Kanban board: drag deals by stage, see velocity                  |
| **Customer Journey** | Page     | Timeline view: all interactions with a customer                  |
| **Reports**          | Page     | Sales forecasting, team performance, revenue trends              |
| **CRM Settings**     | Page     | Pipeline stages, deal fields, email sync, automation rules       |

#### Example: ERP Module Panel

```php
// modules/webkernel/erp/src/Filament/ErpPanelProvider.php
namespace Webkernel\Erp\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class ErpPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('erp')
            ->path('erp')
            ->login()
            ->middleware([
                CheckModuleAccess::class,
            ])
            ->resources([
                // Inventory & Warehouse
                ProductResource::class,
                WarehouseResource::class,
                StockMovementResource::class,
                SupplierResource::class,
                PurchaseOrderResource::class,

                // Sales & Fulfillment
                CustomerResource::class,
                SalesOrderResource::class,
                InvoiceResource::class,
                ShipmentResource::class,
                DeliveryResource::class,

                // Manufacturing (if applicable)
                BillOfMaterialResource::class,
                ProductionOrderResource::class,
                WorkOrderResource::class,

                // Finance & Accounting
                GeneralLedgerResource::class,
                VendorResource::class,
                BillResource::class,
                PaymentResource::class,
                JournalEntryResource::class,

                // Reports
                SalesReportResource::class,
                InventoryReportResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\InventoryStatus::class,
                Pages\SalesForecasting::class,
                Pages\FinancialReports::class,
                Pages\SupplyChain::class,
            ])
            ->widgets([
                InventoryLevelWidget::class,
                SalesPerformanceWidget::class,
                CashFlowWidget::class,
                OrderFulfillmentWidget::class,
            ]);
    }
}
```

#### Example: Training Platform Module Panel

```php
// modules/webkernel/training/src/Filament/TrainingPanelProvider.php
namespace Webkernel\Training\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class TrainingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('training')
            ->path('training')
            ->login()
            ->middleware([
                CheckModuleAccess::class,
            ])
            ->resources([
                CourseResource::class,
                ModuleResource::class,
                LessonResource::class,
                QuizResource::class,
                CertificateResource::class,
                StudentResource::class,
                EnrollmentResource::class,
                AssignmentResource::class,
                GradeResource::class,
                InstructorResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\LearningPath::class,
                Pages\CourseBuilder::class,
                Pages\StudentProgress::class,
                Pages\CertificationTracking::class,
            ])
            ->widgets([
                EnrollmentStatsWidget::class,
                CompletionRateWidget::class,
                AverageScoreWidget::class,
                CertificationStatusWidget::class,
            ]);
    }
}
```

---

### 12.4 Developer Documentation (IS_DEVMODE)

**When IS_DEVMODE=true**, offline developer docs available.

```php
// config/app.php
'dev_mode' => env('IS_DEVMODE', false),
```

**Routes:**

```php
// bootstrap/webkernel/support/routes/development.php
if (config('app.dev_mode')) {
    Route::middleware(['web'])
        ->prefix('dev-docs')
        ->group(function () {
            Route::get('/{module?}/{page?}', ViewModuleDocumentation::class);
        });
}
```

**Developer Docs Page:**

```php
// bootstrap/webkernel/src/Filament/Pages/DevDocumentation.php
namespace Webkernel\Filament\Pages;

use Filament\Pages\Page;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Table\TableExtension;

class DevDocumentation extends Page
{
    protected static string $view = 'filament.pages.dev-documentation';
    protected static ?string $title = 'Developer Documentation';
    protected static ?string $slug = 'dev-docs/{module?}/{page?}';

    public ?string $module = null;
    public ?string $page = null;
    public string $html = '';
    public array $tree = [];

    public function mount(): void
    {
        if (!config('app.dev_mode')) {
            abort(403);
        }

        $this->module = request()->route('module');
        $this->page = request()->route('page') ?? 'index';

        $docsPath = $this->getDocsPath();
        if (!$docsPath) abort(404);

        $this->tree = $this->buildDocTree($docsPath);

        $filePath = "{$docsPath}/{$this->page}.md";
        if (!file_exists($filePath)) {
            $filePath = "{$docsPath}/index.md";
        }

        $this->html = $this->renderMarkdown(file_get_contents($filePath));
    }

    private function getDocsPath(): ?string
    {
        if ($this->module === 'core') {
            return base_path('core/docs');
        }

        $modulesPath = base_path('modules');
        foreach (new \DirectoryIterator($modulesPath) as $vendor) {
            if ($vendor->isDot()) continue;
            foreach (new \DirectoryIterator($vendor->getPathname()) as $module) {
                if ($module->isDot()) continue;
                if (strtolower($module->getBasename()) === $this->module) {
                    $docsPath = $module->getPathname() . '/docs';
                    return is_dir($docsPath) ? $docsPath : null;
                }
            }
        }
        return null;
    }

    private function buildDocTree(string $docsPath): array
    {
        $tree = [];
        $iterator = new \RecursiveDirectoryIterator($docsPath, \RecursiveDirectoryIterator::SKIP_DOTS);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            if ($file->getExtension() !== 'md') continue;

            $slug = str_replace(['.md', '/'], ['', '-'], str_replace($docsPath, '', $file->getPathname()));
            $content = file_get_contents($file);
            preg_match('/^# (.*?)$/m', $content, $match);

            $tree[] = [
                'slug' => $slug,
                'title' => $match[1] ?? ucfirst(basename($file, '.md')),
            ];
        }
        return $tree;
    }

    private function renderMarkdown(string $markdown): string
    {
        $environment = new Environment();
        $environment->addExtension(new TableExtension());
        $converter = new CommonMarkConverter([], $environment);
        return $converter->convert($markdown)->getContent();
    }
}
```

---

### 12.5 Middleware Access Control

```php
// bootstrap/webkernel/src/Http/Middleware/CheckBusinessAccess.php
class CheckBusinessAccess
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->attributes->get('domain');
        if (!$domain || $domain->panel_type !== 'business') abort(403);

        $business = Business::findOrFail($domain->business_id);
        if (!auth()->check() || !auth()->user()->hasAccessToBusiness($business)) abort(403);

        $request->attributes->set('business_id', $business->id);
        return $next($request);
    }
}
```

```php
// bootstrap/webkernel/src/Http/Middleware/CheckModuleAccess.php
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->attributes->get('domain');
        if (!$domain || $domain->panel_type !== 'module') abort(403);

        $business = Business::findOrFail($domain->business_id);
        $module = Module::findOrFail($domain->module_id);

        if (!auth()->check() || !auth()->user()->hasAccessToModule($business, $module)) abort(403);

        $request->attributes->set('business_id', $business->id);
        $request->attributes->set('module_id', $module->id);
        return $next($request);
    }
}
```

---

### 12.6 What Gets Built

**For organizations that need sovereignty:**

✅ **System Panel** = App owner controls infrastructure, modules, infrastructure  
✅ **Business Panel** = Business admin controls organization, people, budgets, compliance  
✅ **Module Panels** = Workers perform actual work (sales, accounting, training, etc.)  
✅ **No cloud dependencies** = Everything air-gapped, no external APIs required  
✅ **No forced updates** = Organizations choose when/if to update  
✅ **Complete ownership** = They own all data, all code, all infrastructure

**Access:** Instance root domain only (app owner login required)

**Filament Panel Provider:**

```php
// bootstrap/webkernel/src/Providers/Filament/SystemPanelProvider.php
namespace Webkernel\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class SystemPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('system')
            ->path('system')
            ->login()
            ->resources([
                BusinessResource::class,
                ModuleResource::class,
                DomainResource::class,
                DbConnectionResource::class,
                AuditLogResource::class,
                BackgroundTaskResource::class,
                ApiKeyResource::class,
                UserPrivilegeResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\SystemHealth::class,
                Pages\Backups::class,
                Pages\InstanceSettings::class,
            ])
            ->widgets([
                InstanceStatsWidget::class,
                ModuleStatsWidget::class,
                SystemHealthWidget::class,
            ]);
    }
}
```

**System Panel Structure:**

| Item              | Type     | Purpose                                     |
| ----------------- | -------- | ------------------------------------------- |
| Dashboard         | Page     | Overview of instance status                 |
| Businesses        | Resource | CRUD: create, list, edit, delete businesses |
| Modules           | Resource | CRUD: install, enable, disable modules      |
| Domains           | Resource | CRUD: add, list, edit domains + SSL         |
| DB Connections    | Resource | CRUD: manage encrypted DB credentials       |
| Instance Settings | Page     | Global config (name, email, etc.)           |
| Audit Log         | Resource | View-only: audit trail of all actions       |
| Background Tasks  | Resource | Monitor cert issuance, updates, jobs        |
| API Keys          | Resource | CRUD: manage app owner API tokens           |
| User Privileges   | Resource | CRUD: manage app owner access               |
| System Health     | Page     | System status, disk space, uptime           |
| Backups           | Page     | Backup history, restore options             |

---

### 12.2 Business Panel(s) (Business Admins)

**Access:** Business-specific domain (business admin login required)

Each business accesses their panel via:

- `sales.appowner.com` → Sales Business Panel
- `operations.appowner.com` → Operations Business Panel

**Filament Panel Provider:**

```php
// bootstrap/webkernel/src/Providers/Filament/BusinessPanelProvider.php
namespace Webkernel\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('business')
            ->login()
            ->middleware([
                CheckBusinessAccess::class,
            ])
            ->resources([
                BusinessUsersResource::class,
                UserRolesResource::class,
                TeamsResource::class,
                BusinessDomainsResource::class,
                IntegrationResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\BusinessProfile::class,
                Pages\BusinessSettings::class,
                Pages\TeamManagement::class,
                Pages\EnabledModules::class,
            ])
            ->widgets([
                BusinessStatsWidget::class,
                TeamOverviewWidget::class,
                ModuleActivityWidget::class,
            ]);
    }
}
```

**Business Panel Structure:**

| Item              | Type     | Purpose                                         |
| ----------------- | -------- | ----------------------------------------------- |
| Dashboard         | Page     | Business overview (users, modules, stats)       |
| Business Profile  | Page     | Name, logo, fiscal IDs, contact info, documents |
| Business Settings | Page     | Currency, payment methods, language, timezone   |
| Team Management   | Page     | Organizational structure, departments           |
| Business Users    | Resource | CRUD: add, list, edit, delete users             |
| User Roles        | Resource | CRUD: manage roles + permissions                |
| Teams             | Resource | CRUD: manage teams/departments                  |
| Business Domains  | Resource | CRUD: manage subdomains + custom domains        |
| Integrations      | Resource | CRUD: configure third-party integrations        |
| Enabled Modules   | Page     | List enabled modules, links to module panels    |

---

### 12.3 Module Panels (Within Business Context)

**Access:** Module-specific domain scoped to business

Each module registers its own **Filament Panel** dynamically.

Example: CRM module at `crm.appowner.com`

```php
// modules/acme/crm/src/Filament/CrmPanelProvider.php
namespace Acme\Crm\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class CrmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('crm')
            ->path('crm')
            ->login()
            ->middleware([
                CheckModuleAccess::class,
            ])
            ->resources([
                ContactResource::class,
                CompanyResource::class,
                DealResource::class,
                ActivityResource::class,
                NoteResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\CrmSettings::class,
                Pages\Reports::class,
                Pages\Pipeline::class,
            ])
            ->widgets([
                DealPipelineWidget::class,
                RevenueWidget::class,
                ActivityTimelineWidget::class,
                TeamPerformanceWidget::class,
            ]);
    }
}
```

**CRM Module Panel Structure:**

| Item         | Type     | Purpose                               |
| ------------ | -------- | ------------------------------------- |
| Dashboard    | Page     | Pipeline overview, key metrics        |
| Contacts     | Resource | CRUD: manage contacts                 |
| Companies    | Resource | CRUD: manage companies                |
| Deals        | Resource | CRUD: manage sales deals              |
| Activities   | Resource | CRUD: calls, emails, meetings         |
| Notes        | Resource | CRUD: contact notes                   |
| Pipeline     | Page     | Kanban view of deals by stage         |
| Reports      | Page     | Sales reports, forecasting            |
| CRM Settings | Page     | Pipeline stages, contact fields, etc. |

---

### 12.4 Developer Documentation (IS_DEVMODE)

**When IS_DEVMODE=true**, developer docs are available locally.

No internet required. Markdown files from codebase.

**Implementation:**

```php
// config/app.php
'dev_mode' => env('IS_DEVMODE', false),
```

**Routes (auto-disabled if IS_DEVMODE=false):**

```php
// bootstrap/webkernel/support/routes/development.php

if (config('app.dev_mode')) {
    Route::middleware(['web'])
        ->prefix('dev-docs')
        ->group(function () {
            Route::get('/{module?}/{page?}', ViewModuleDocumentation::class);
        });
}
```

**Documentation Locations:**

```
modules/acme/crm/docs/
├── index.md
├── installation.md
├── configuration.md
├── usage/
│   ├── getting-started.md
│   └── advanced.md
└── api/
    ├── endpoints.md
    └── openapi.yaml

core/docs/
├── architecture.md
├── database-schema.md
├── domain-routing.md
└── encryption.md
```

**Developer Docs Page (Filament):**

```php
// bootstrap/webkernel/src/Filament/Pages/DevDocumentation.php
namespace Webkernel\Filament\Pages;

use Filament\Pages\Page;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Table\TableExtension;

class DevDocumentation extends Page
{
    protected static string $view = 'filament.pages.dev-documentation';
    protected static ?string $title = 'Developer Documentation';
    protected static ?string $slug = 'dev-docs/{module?}/{page?}';

    public ?string $module = null;
    public ?string $page = null;
    public string $html = '';
    public array $tree = [];

    public function mount(): void
    {
        if (!config('app.dev_mode')) {
            abort(403);
        }

        $this->module = request()->route('module');
        $this->page = request()->route('page') ?? 'index';

        $docsPath = $this->getDocsPath();

        if (!$docsPath) {
            abort(404, 'Documentation not found');
        }

        $this->tree = $this->buildDocTree($docsPath);

        $filePath = "{$docsPath}/{$this->page}.md";
        if (!file_exists($filePath)) {
            $filePath = "{$docsPath}/index.md";
        }

        $this->html = $this->renderMarkdown(file_get_contents($filePath));
    }

    private function getDocsPath(): ?string
    {
        if ($this->module === 'core') {
            return base_path('core/docs');
        }

        $modulesPath = base_path('modules');
        foreach (new \DirectoryIterator($modulesPath) as $vendor) {
            if ($vendor->isDot()) continue;

            foreach (new \DirectoryIterator($vendor->getPathname()) as $module) {
                if ($module->isDot()) continue;

                if (strtolower($module->getBasename()) === $this->module) {
                    $docsPath = $module->getPathname() . '/docs';
                    return is_dir($docsPath) ? $docsPath : null;
                }
            }
        }

        return null;
    }

    private function buildDocTree(string $docsPath): array
    {
        $tree = [];
        $iterator = new \RecursiveDirectoryIterator($docsPath, \RecursiveDirectoryIterator::SKIP_DOTS);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            if ($file->getExtension() !== 'md') continue;

            $slug = str_replace(['.md', '/'], ['', '-'], str_replace($docsPath, '', $file->getPathname()));
            $content = file_get_contents($file);
            preg_match('/^# (.*?)$/m', $content, $match);

            $tree[] = [
                'slug' => $slug,
                'title' => $match[1] ?? ucfirst(basename($file, '.md')),
            ];
        }

        return $tree;
    }

    private function renderMarkdown(string $markdown): string
    {
        $environment = new Environment();
        $environment->addExtension(new TableExtension());

        $converter = new CommonMarkConverter([], $environment);
        return $converter->convert($markdown)->getContent();
    }
}
```

**Blade Template:**

```blade
{{-- resources/views/filament/pages/dev-documentation.blade.php --}}

<div class="flex gap-6">
    <aside class="w-64 border-r">
        <nav class="space-y-1 py-4">
            @foreach($tree as $doc)
                <a href="{{ route('filament.pages.dev-documentation', ['module' => $module, 'page' => $doc['slug']]) }}"
                   class="block px-4 py-2 rounded @if($doc['slug'] === $page) bg-blue-50 @endif">
                    {{ $doc['title'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    <main class="flex-1">
        <div class="prose max-w-none">
            {!! $html !!}
        </div>
    </main>
</div>
```

---

### 12.5 Middleware Access Control

**CheckSystemAccess** — Verify app owner:

```php
// bootstrap/webkernel/src/Http/Middleware/CheckSystemAccess.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSystemAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAppOwner()) {
            abort(403);
        }

        return $next($request);
    }
}
```

**CheckBusinessAccess** — Verify business access + inject context:

```php
// bootstrap/webkernel/src/Http/Middleware/CheckBusinessAccess.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Models\Domain;
use Webkernel\Models\Business;

class CheckBusinessAccess
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->attributes->get('domain');

        if (!$domain || $domain->panel_type !== 'business') {
            abort(403);
        }

        $business = Business::findOrFail($domain->business_id);

        if (!auth()->check() || !auth()->user()->hasAccessToBusiness($business)) {
            abort(403);
        }

        $request->attributes->set('business_id', $business->id);
        return $next($request);
    }
}
```

**CheckModuleAccess** — Verify module access + inject context:

```php
// bootstrap/webkernel/src/Http/Middleware/CheckModuleAccess.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Models\Domain;
use Webkernel\Models\Module;
use Webkernel\Models\Business;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->attributes->get('domain');

        if (!$domain || $domain->panel_type !== 'module') {
            abort(403);
        }

        $business = Business::findOrFail($domain->business_id);
        $module = Module::findOrFail($domain->module_id);

        if (!auth()->check() || !auth()->user()->hasAccessToModule($business, $module)) {
            abort(403);
        }

        $request->attributes->set('business_id', $business->id);
        $request->attributes->set('module_id', $module->id);
        return $next($request);
    }
}
```

---

### 12.6 Panel Access Flow

```
Request: https://sales.appowner.com/business/contacts
    ↓
ResolveDomainContext (reads Host header)
    ↓
Lookup: domains WHERE domain='sales.appowner.com'
    ↓
Found: business_id={uuid}, panel_type='business'
    ↓
Inject: request->attributes->set('business_id', ...)
    ↓
Filament routes request to business panel
    ↓
CheckBusinessAccess middleware (verifies user)
    ↓
Business Panel renders
    ↓
All Resources/Pages/Widgets scoped to business_id
```

**Access:** Instance root domain only (app owner login required)

**Filament Panel Provider:**

```php
// bootstrap/webkernel/src/Providers/Filament/SystemPanelProvider.php
namespace Webkernel\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class SystemPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('system')
            ->path('system')
            ->login()
            ->resources([
                // Core Instance
                BusinessResource::class,
                ModuleResource::class,
                DomainResource::class,

                // Infrastructure
                DbConnectionResource::class,
                InstanceSettingsResource::class,

                // Observability
                AuditLogResource::class,
                BackgroundTaskResource::class,

                // Security
                ApiKeyResource::class,
                UserPrivilegeResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\SystemHealth::class,
                Pages\Backups::class,
            ])
            ->widgets([
                InstanceStatsWidget::class,
                ModuleStatsWidget::class,
                SystemHealthWidget::class,
            ]);
    }
}
```

**System Panel Resources:**

| Resource          | Purpose                                            |
| ----------------- | -------------------------------------------------- |
| Businesses        | Create, list, manage businesses                    |
| Modules           | Install, enable, disable, configure modules        |
| Domains           | Add domains, manage SSL, configure routing         |
| DB Connections    | Encrypted database credentials per business/module |
| Instance Settings | Global configuration, instance name, email, etc.   |
| Audit Log         | Complete audit trail of all actions                |
| Background Tasks  | Monitor certificate issuance, updates, jobs        |
| API Keys          | Manage app owner API tokens                        |
| User Privileges   | Manage app owner access                            |

---

### 12.2 Business Panel(s) (Business Admins)

**Access:** Business-specific domain (business admin login required)

Each business accesses their panel via:

- `sales.appowner.com` → Sales Business Panel
- `operations.appowner.com` → Operations Business Panel

**Filament Panel Provider:**

```php
// bootstrap/webkernel/src/Providers/Filament/BusinessPanelProvider.php
namespace Webkernel\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('business')
            ->login()
            ->middleware([
                CheckBusinessAccess::class,  // Verify current business_id
            ])
            ->resources([
                // Business Identity
                BusinessProfileResource::class,
                BusinessDocumentsResource::class,

                // Organization
                TeamsResource::class,
                BusinessUsersResource::class,
                UserRolesResource::class,

                // Configuration
                CurrencySettingsResource::class,
                PaymentMethodsResource::class,

                // Domains
                BusinessDomainsResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\BusinessSettings::class,
            ])
            ->widgets([
                BusinessStatsWidget::class,
                EnabledModulesWidget::class,
                TeamOverviewWidget::class,
            ]);
    }
}
```

**Business Panel Resources:**

| Resource           | Purpose                                       |
| ------------------ | --------------------------------------------- |
| Business Profile   | Name, logo, fiscal IDs, contact info          |
| Business Documents | Contracts, legal docs, certifications         |
| Teams/Departments  | Organizational structure                      |
| Business Users     | Employees, contractors, access management     |
| User Roles         | Role-based permissions for this business      |
| Currency Settings  | Default currency, exchange rates              |
| Payment Methods    | Payment gateways, banking info                |
| Business Domains   | Manage business domains (subdomains + custom) |

---

### 12.3 Module Panels (Within Business Context)

**Access:** Module-specific domain scoped to business

Each module registers its own **Filament Panel** dynamically.

Example: CRM module at `crm.appowner.com`

```php
// modules/acme/crm/src/Filament/CrmPanelProvider.php
namespace Acme\Crm\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class CrmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('crm')
            ->path('crm')
            ->login()
            ->middleware([
                CheckModuleAccess::class,  // Verify user has CRM access
                CheckBusinessContext::class,  // Verify business_id
            ])
            ->resources([
                ContactResource::class,
                CompanyResource::class,
                DealResource::class,
                ActivityResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                Pages\Reports::class,
                Pages\Settings::class,  // Module-specific settings
            ])
            ->widgets([
                DealPipelineWidget::class,
                RevenueWidget::class,
                ActivityWidget::class,
            ]);
    }
}
```

**Module Panel Characteristics:**

- Each module provides its own panel
- Scoped to **current business_id** (from middleware)
- Uses DatabaseConnectionResolver to get correct DB
- Module-specific resources, pages, widgets
- Module-specific user roles/permissions

---

### 12.4 Developer Documentation (IS_DEVMODE)

**When IS_DEVMODE=true**, developer docs are available locally.

No internet required. Markdown files from codebase.

**Implementation:**

```php
// config/app.php
'dev_mode' => env('IS_DEVMODE', false),
```

```php
// bootstrap/webkernel/src/Http/Middleware/CheckDevelopmentDocs.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckDevelopmentDocs
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('app.dev_mode')) {
            abort(403, 'Development mode disabled');
        }

        return $next($request);
    }
}
```

**Routes:**

```php
// bootstrap/webkernel/support/routes/development.php

if (config('app.dev_mode')) {
    Route::middleware(['web', CheckDevelopmentDocs::class])
        ->prefix('dev-docs')
        ->group(function () {
            Route::get('/', DevDocsDashboard::class);
            Route::get('/{module}/{page?}', ViewModuleDocumentation::class);
        });
}
```

**Markdown Documentation Locations:**

```
modules/acme/crm/
├── src/
├── docs/
│   ├── index.md
│   ├── installation.md
│   ├── configuration.md
│   ├── usage/
│   │   ├── getting-started.md
│   │   └── advanced.md
│   └── api/
│       ├── endpoints.md
│       └── openapi.yaml
└── README.md

core/
├── docs/
│   ├── architecture.md
│   ├── database-schema.md
│   ├── domain-routing.md
│   └── encryption.md
└── README.md
```

**Development Docs Viewer (Simple Filament Page):**

```php
// bootstrap/webkernel/src/Filament/Pages/DevDocumentation.php
namespace Webkernel\Filament\Pages;

use Filament\Pages\Page;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Table\TableExtension;

class DevDocumentation extends Page
{
    protected static string $view = 'filament.pages.dev-documentation';
    protected static ?string $title = 'Developer Documentation';
    protected static ?string $slug = 'dev-docs/{module?}/{page?}';

    public ?string $module = null;
    public ?string $page = null;
    public string $html = '';
    public array $tree = [];

    public function mount(): void
    {
        if (!config('app.dev_mode')) {
            abort(403);
        }

        $this->module = request()->route('module');
        $this->page = request()->route('page') ?? 'index';

        // Get module docs path
        $docsPath = $this->getDocsPath();

        if (!$docsPath) {
            abort(404, 'Module documentation not found');
        }

        // Build doc tree for sidebar
        $this->tree = $this->buildDocTree($docsPath);

        // Render requested page
        $filePath = "{$docsPath}/{$this->page}.md";
        if (!file_exists($filePath)) {
            $filePath = "{$docsPath}/index.md";
        }

        $this->html = $this->renderMarkdown(file_get_contents($filePath));
    }

    private function getDocsPath(): ?string
    {
        if ($this->module === 'core') {
            return base_path('core/docs');
        }

        // Search in modules
        $modulesPath = base_path('modules');
        foreach (new \DirectoryIterator($modulesPath) as $vendor) {
            if ($vendor->isDot()) continue;

            foreach (new \DirectoryIterator($vendor->getPathname()) as $module) {
                if ($module->isDot()) continue;

                $slug = strtolower($module->getBasename());
                if ($slug === $this->module) {
                    $docsPath = $module->getPathname() . '/docs';
                    if (is_dir($docsPath)) {
                        return $docsPath;
                    }
                }
            }
        }

        return null;
    }

    private function buildDocTree(string $docsPath): array
    {
        $tree = [];
        $iterator = new \RecursiveDirectoryIterator($docsPath, \RecursiveDirectoryIterator::SKIP_DOTS);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            if ($file->getExtension() !== 'md') continue;

            $relativePath = str_replace($docsPath, '', $file->getPathname());
            $slug = str_replace(['.md', '/'], ['', '-'], $relativePath);

            $content = file_get_contents($file);
            preg_match('/^# (.*?)$/m', $content, $match);

            $tree[] = [
                'slug' => $slug,
                'title' => $match[1] ?? ucfirst(basename($file, '.md')),
            ];
        }

        return $tree;
    }

    private function renderMarkdown(string $markdown): string
    {
        $environment = new Environment();
        $environment->addExtension(new TableExtension());

        $converter = new CommonMarkConverter([], $environment);
        return $converter->convert($markdown)->getContent();
    }
}
```

**Blade Template:**

```blade
{{-- resources/views/filament/pages/dev-documentation.blade.php --}}

<div class="flex gap-6">
    <aside class="w-64 border-r">
        <nav class="space-y-1 py-4">
            @foreach($tree as $doc)
                <a href="{{ route('filament.pages.dev-documentation', ['module' => $module, 'page' => $doc['slug']]) }}"
                   class="block px-4 py-2 rounded @if($doc['slug'] === $page) bg-blue-50 @endif">
                    {{ $doc['title'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    <main class="flex-1">
        <div class="prose max-w-none">
            {!! $html !!}
        </div>
    </main>
</div>
```

---

### 12.5 Middleware Access Control

**CheckSystemAccess** — Verify app owner access:

```php
// bootstrap/webkernel/src/Http/Middleware/CheckSystemAccess.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSystemAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAppOwner()) {
            abort(403, 'System Panel access denied');
        }

        return $next($request);
    }
}
```

**CheckBusinessAccess** — Verify business access + inject business context:

```php
// bootstrap/webkernel/src/Http/Middleware/CheckBusinessAccess.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Models\Domain;
use Webkernel\Models\Business;

class CheckBusinessAccess
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->attributes->get('domain');

        if (!$domain || $domain->panel_type !== 'business') {
            abort(403, 'Business Panel access denied');
        }

        $business = Business::findOrFail($domain->business_id);

        if (!auth()->check() || !auth()->user()->hasAccessTobusiness($business)) {
            abort(403, 'Access denied');
        }

        $request->attributes->set('business_id', $business->id);
        return $next($request);
    }
}
```

**CheckModuleAccess** — Verify module access + inject module context:

```php
// bootstrap/webkernel/src/Http/Middleware/CheckModuleAccess.php
namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Models\Domain;
use Webkernel\Models\Module;
use Webkernel\Models\Business;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->attributes->get('domain');

        if (!$domain || $domain->panel_type !== 'module') {
            abort(403, 'Module Panel access denied');
        }

        $business = Business::findOrFail($domain->business_id);
        $module = Module::findOrFail($domain->module_id);

        if (!auth()->check() || !auth()->user()->hasAccessToModule($business, $module)) {
            abort(403, 'Module access denied');
        }

        $request->attributes->set('business_id', $business->id);
        $request->attributes->set('module_id', $module->id);
        return $next($request);
    }
}
```

---

### 12.6 Panel Access Flow

```
Request arrives: https://sales.appowner.com/business/...
    ↓
ResolveDomainContext middleware (reads Host header)
    ↓
Looks up: domains table WHERE domain='sales.appowner.com'
    ↓
Found: business_id={uuid}, panel_type='business'
    ↓
Inject: request->attributes->set('business_id', ...)
    ↓
CheckBusinessAccess middleware (verifies user has business access)
    ↓
Filament Business Panel renders with current business_id
    ↓
All resources, pages, widgets scoped to business_id
```

---

### Setup (Once)

- [ ] App owner buys `acmecorp.io`
- [ ] Points to VPS: `203.0.113.50`
- [ ] DNS: `acmecorp.io A 203.0.113.50`
- [ ] DNS: `*.acmecorp.io A 203.0.113.50`
- [ ] Wildcard SSL cert: `*.acmecorp.io`
- [ ] Deploy Webkernel to VPS
- [ ] PHP 8.4+, Laravel 13+, Filament 5+ running

### Per Business

- [ ] Create business in System Panel
- [ ] Auto-domain: `sales.acmecorp.io` (covered by wildcard cert)
- [ ] Database connection stored (encrypted)
- [ ] Business Panel live at `sales.acmecorp.io`

### Per Module

- [ ] Business admin installs module
- [ ] Optional: dedicated database
- [ ] Module domain: `crm.acmecorp.io` (covered by wildcard cert)
- [ ] Module Panel live

---

## SUMMARY

**Webkernel is architected for sovereignty:**

- App owner owns infrastructure
- Single domain `acmecorp.io` with wildcard cert `*.acmecorp.io`
- Unlimited subdomains: `sales.acmecorp.io`, `operations.acmecorp.io`, etc.
- Single Laravel app handles all routing via Host header
- Single SQLite core DB + per-business DBs (encrypted credentials)
- No dynamic server config
- No shell_exec nightmares
- No Nginx template hell
- Clean, pragmatic, future-proof

**What was added:**

✅ Complete SQLite schema
✅ DatabaseConnectionResolver (cascade pattern)
✅ ResolveDomainContext middleware
✅ Symfony Process for SSL renewal (no shell_exec)
✅ Encryption via Laravel's built-in (APP_KEY)
✅ Audit logging
✅ Real workflow examples
✅ Security model
✅ No assumptions about external services
