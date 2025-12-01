# Kursagenten Admin Integration Plan

## Oversikt

Dette dokumentet beskriver planen for å integrere WordPress admin-funksjonalitet i kursadmin.kursagenten.no. Løsningen består av to hoveddeler:

1. **SSO til WordPress admin** - Snarveier som logger brukeren automatisk inn i WordPress admin
2. **Moderne admin-app i iframe** - React/Vue-app for redigering av innstillinger og taksonomier

---

## Del 1: SSO til WordPress Admin (Snarveier)

### Mål
Brukeren skal kunne klikke på snarveier i kursadmin (f.eks. "Forside", "Sider", "Artikler") og automatisk bli logget inn i WordPress admin på riktig side.

### Hvordan det fungerer

```
kursadmin.kursagenten.no
  └─> Bruker klikker "Forside"
      └─> Kursadmin genererer JWT-token med brukerinfo
          └─> Redirect til: https://kundeside.no/wp-admin/?kursagenten_sso=TOKEN&redirect=edit.php
              └─> WordPress-plugin validerer token
                  └─> Automatisk innlogging hvis e-post matcher admin-bruker
                      └─> Redirect til riktig admin-side
```

### Teknisk implementering

#### 1. WordPress-plugin (kursagenten)

**Ny fil: `includes/api/api_admin_sso.php`**

```php
<?php
/**
 * SSO Authentication for WordPress Admin
 */

namespace Kursagenten;

class AdminSSO {
    
    private $secret_key;
    
    public function __construct() {
        // Use same secret key as server plugin or generate shared secret
        $this->secret_key = get_option('kursagenten_sso_secret', '');
        
        // Hook into WordPress init
        add_action('init', [$this, 'handle_sso_request']);
    }
    
    /**
     * Handle SSO request from kursadmin
     */
    public function handle_sso_request() {
        if (!isset($_GET['kursagenten_sso'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['kursagenten_sso']);
        $redirect = isset($_GET['redirect']) ? sanitize_text_field($_GET['redirect']) : 'index.php';
        
        // Validate token
        $user_data = $this->validate_token($token);
        
        if (!$user_data) {
            wp_die('Invalid SSO token', 'SSO Error', ['response' => 401]);
            return;
        }
        
        // Find WordPress user by email
        $user = get_user_by('email', $user_data['email']);
        
        if (!$user) {
            wp_die('No WordPress user found with email: ' . $user_data['email'], 'SSO Error', ['response' => 403]);
            return;
        }
        
        // Check if user has admin capabilities
        if (!user_can($user->ID, 'manage_options')) {
            wp_die('User does not have admin privileges', 'SSO Error', ['response' => 403]);
            return;
        }
        
        // Log in the user
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Redirect to requested admin page
        $redirect_url = admin_url($redirect);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Validate JWT token from kursadmin
     */
    private function validate_token($token) {
        if (empty($this->secret_key)) {
            return false;
        }
        
        // Decode JWT token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', $header . '.' . $payload, $this->secret_key, true);
        $expected_signature_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));
        
        if (!hash_equals($expected_signature_base64, $signature)) {
            return false;
        }
        
        // Decode payload
        $payload_data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if (!$payload_data) {
            return false;
        }
        
        // Check expiration (5 minutes)
        if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
            return false;
        }
        
        return $payload_data;
    }
    
    /**
     * Generate SSO secret key (run once)
     */
    public static function generate_secret_key() {
        if (empty(get_option('kursagenten_sso_secret'))) {
            $secret = wp_generate_password(64, true, true);
            update_option('kursagenten_sso_secret', $secret);
        }
    }
}
```

**I hovedplugin-filen (`kursagenten.php`):**

```php
// Add SSO functionality
require_once plugin_dir_path(__FILE__) . 'includes/api/api_admin_sso.php';
new \Kursagenten\AdminSSO();
```

#### 2. Kursadmin implementering

**C# kodeeksempel:**

```csharp
public class WordPressSSO
{
    private readonly string _secretKey; // Same secret as WordPress plugin
    
    public WordPressSSO(string secretKey)
    {
        _secretKey = secretKey;
    }
    
    public string GenerateSSOToken(string email, string siteUrl)
    {
        var header = new { alg = "HS256", typ = "JWT" };
        var payload = new
        {
            email = email,
            site_url = siteUrl,
            exp = DateTimeOffset.UtcNow.AddMinutes(5).ToUnixTimeSeconds(),
            iat = DateTimeOffset.UtcNow.ToUnixTimeSeconds()
        };
        
        var headerJson = JsonSerializer.Serialize(header);
        var payloadJson = JsonSerializer.Serialize(payload);
        
        var headerBase64 = Base64UrlEncode(Encoding.UTF8.GetBytes(headerJson));
        var payloadBase64 = Base64UrlEncode(Encoding.UTF8.GetBytes(payloadJson));
        
        var signature = HMACSHA256($headerBase64 + "." + payloadBase64, _secretKey);
        var signatureBase64 = Base64UrlEncode(signature);
        
        return $"{headerBase64}.{payloadBase64}.{signatureBase64}";
    }
    
    public string GetWordPressAdminUrl(string siteUrl, string adminPage = "index.php")
    {
        var token = GenerateSSOToken(GetCurrentUserEmail(), siteUrl);
        return $"{siteUrl}/wp-admin/?kursagenten_sso={token}&redirect={adminPage}";
    }
    
    // Helper methods for Base64Url encoding and HMAC
    private string Base64UrlEncode(byte[] input)
    {
        return Convert.ToBase64String(input)
            .TrimEnd('=')
            .Replace('+', '-')
            .Replace('/', '_');
    }
    
    private byte[] HMACSHA256(string data, string key)
    {
        using (var hmac = new System.Security.Cryptography.HMACSHA256(Encoding.UTF8.GetBytes(key)))
        {
            return hmac.ComputeHash(Encoding.UTF8.GetBytes(data));
        }
    }
}
```

**Snarveier i kursadmin:**

```csharp
// Eksempel på snarveier
var sso = new WordPressSSO(secretKey);

var shortcuts = new[]
{
    new { Name = "Forside", Url = sso.GetWordPressAdminUrl(siteUrl, "index.php") },
    new { Name = "Sider", Url = sso.GetWordPressAdminUrl(siteUrl, "edit.php?post_type=page") },
    new { Name = "Artikler", Url = sso.GetWordPressAdminUrl(siteUrl, "edit.php") },
    new { Name = "Media", Url = sso.GetWordPressAdminUrl(siteUrl, "upload.php") },
    new { Name = "Kurskategorier", Url = sso.GetWordPressAdminUrl(siteUrl, "edit-tags.php?taxonomy=kurskategori") },
    new { Name = "Kurssteder", Url = sso.GetWordPressAdminUrl(siteUrl, "edit-tags.php?taxonomy=kurssted") },
    new { Name = "Instruktører", Url = sso.GetWordPressAdminUrl(siteUrl, "edit-tags.php?taxonomy=instruktor") },
    new { Name = "Kursagenten Innstillinger", Url = sso.GetWordPressAdminUrl(siteUrl, "admin.php?page=kursagenten") }
};
```

### Sikkerhet

1. **JWT-token med HMAC-SHA256** - Signert med delt secret key
2. **Kort levetid** - Token utløper etter 5 minutter
3. **E-post-validering** - Kun brukere med admin-tilgang kan logge inn
4. **HTTPS påkrevd** - Token sendes kun over HTTPS

---

## Del 2: Moderne Admin-App i Iframe

### Mål
Bygge en moderne React/Vue-app for redigering av:
- Kurskategorier (taksonomi)
- Kurssteder (taksonomi)
- Instruktører (taksonomi)
- Kursagenten-sider
- Synkronisering-innstillinger
- Kursdesign-innstillinger

### Arkitektur

```
kursadmin.kursagenten.no
  └─> Iframe: https://kundeside.no/kursagenten-admin/
      └─> React/Vue App
          ├─> Kommuniserer med WordPress REST API
          ├─> Moderne UI (Material UI / Tailwind)
          └─> SSO-autentisering via JWT token
```

### Teknisk implementering

#### 1. WordPress REST API Endepunkter

**Ny fil: `includes/api/api_admin_rest.php`**

```php
<?php
/**
 * REST API for Admin App
 */

namespace Kursagenten;

class AdminREST {
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Categories endpoint
        register_rest_route('kursagenten/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('kursagenten/v1', '/categories', [
            'methods' => 'POST',
            'callback' => [$this, 'create_category'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('kursagenten/v1', '/categories/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_category'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        register_rest_route('kursagenten/v1', '/categories/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_category'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Locations endpoint
        register_rest_route('kursagenten/v1', '/locations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_locations'],
            'permission_callback' => [$this, 'check_permission']
        ]);
        
        // Similar for instructors, pages, settings...
    }
    
    /**
     * Check if user is authenticated and has admin privileges
     */
    public function check_permission($request) {
        // Check JWT token from Authorization header
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            return new WP_Error('unauthorized', 'Missing or invalid authorization header', ['status' => 401]);
        }
        
        $token = substr($auth_header, 7);
        $user_data = $this->validate_token($token);
        
        if (!$user_data) {
            return new WP_Error('unauthorized', 'Invalid token', ['status' => 401]);
        }
        
        $user = get_user_by('email', $user_data['email']);
        
        if (!$user || !user_can($user->ID, 'manage_options')) {
            return new WP_Error('forbidden', 'Insufficient permissions', ['status' => 403]);
        }
        
        return true;
    }
    
    /**
     * Get all categories
     */
    public function get_categories($request) {
        $terms = get_terms([
            'taxonomy' => 'kurskategori',
            'hide_empty' => false,
        ]);
        
        $categories = array_map(function($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'count' => $term->count
            ];
        }, $terms);
        
        return rest_ensure_response($categories);
    }
    
    /**
     * Create new category
     */
    public function create_category($request) {
        $params = $request->get_json_params();
        
        $term = wp_insert_term(
            sanitize_text_field($params['name']),
            'kurskategori',
            [
                'description' => sanitize_textarea_field($params['description'] ?? ''),
                'slug' => sanitize_title($params['slug'] ?? $params['name'])
            ]
        );
        
        if (is_wp_error($term)) {
            return new WP_Error('create_failed', $term->get_error_message(), ['status' => 400]);
        }
        
        return rest_ensure_response([
            'id' => $term['term_id'],
            'name' => $params['name'],
            'message' => 'Category created successfully'
        ]);
    }
    
    /**
     * Update category
     */
    public function update_category($request) {
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        $term = wp_update_term($id, 'kurskategori', [
            'name' => sanitize_text_field($params['name']),
            'description' => sanitize_textarea_field($params['description'] ?? ''),
            'slug' => sanitize_title($params['slug'] ?? $params['name'])
        ]);
        
        if (is_wp_error($term)) {
            return new WP_Error('update_failed', $term->get_error_message(), ['status' => 400]);
        }
        
        return rest_ensure_response([
            'id' => $id,
            'message' => 'Category updated successfully'
        ]);
    }
    
    /**
     * Delete category
     */
    public function delete_category($request) {
        $id = $request->get_param('id');
        
        $result = wp_delete_term($id, 'kurskategori');
        
        if (is_wp_error($result)) {
            return new WP_Error('delete_failed', $result->get_error_message(), ['status' => 400]);
        }
        
        return rest_ensure_response([
            'message' => 'Category deleted successfully'
        ]);
    }
    
    // Similar methods for locations, instructors, pages, settings...
    
    /**
     * Validate JWT token (same as SSO)
     */
    private function validate_token($token) {
        $secret_key = get_option('kursagenten_sso_secret', '');
        
        if (empty($secret_key)) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expected_signature = hash_hmac('sha256', $header . '.' . $payload, $secret_key, true);
        $expected_signature_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));
        
        if (!hash_equals($expected_signature_base64, $signature)) {
            return false;
        }
        
        $payload_data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if (!$payload_data || (isset($payload_data['exp']) && $payload_data['exp'] < time())) {
            return false;
        }
        
        return $payload_data;
    }
}
```

#### 2. Admin App Route i WordPress

**Ny fil: `includes/admin/admin_app.php`**

```php
<?php
/**
 * Admin App Route Handler
 */

namespace Kursagenten;

class AdminApp {
    
    public function __construct() {
        add_action('init', [$this, 'handle_admin_app_route']);
    }
    
    /**
     * Handle /kursagenten-admin/ route
     */
    public function handle_admin_app_route() {
        if (strpos($_SERVER['REQUEST_URI'], '/kursagenten-admin/') === false) {
            return;
        }
        
        // Validate SSO token
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        if (empty($token)) {
            wp_die('Missing authentication token', 'Authentication Error', ['response' => 401]);
            return;
        }
        
        // Validate token (same validation as SSO)
        $user_data = $this->validate_token($token);
        
        if (!$user_data) {
            wp_die('Invalid authentication token', 'Authentication Error', ['response' => 401]);
            return;
        }
        
        // Check user exists and has admin privileges
        $user = get_user_by('email', $user_data['email']);
        
        if (!$user || !user_can($user->ID, 'manage_options')) {
            wp_die('Insufficient permissions', 'Authorization Error', ['response' => 403]);
            return;
        }
        
        // Serve the React/Vue app
        $this->serve_admin_app($token);
    }
    
    /**
     * Serve the admin app HTML
     */
    private function serve_admin_app($token) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Kursagenten Admin</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                }
                #kursagenten-admin-app {
                    width: 100%;
                    height: 100vh;
                }
            </style>
        </head>
        <body>
            <div id="kursagenten-admin-app"></div>
            <script>
                // Store token for API calls
                window.KURSAGENTEN_TOKEN = '<?php echo esc_js($token); ?>';
                window.KURSAGENTEN_API_URL = '<?php echo esc_url(rest_url('kursagenten/v1')); ?>';
                window.KURSAGENTEN_SITE_URL = '<?php echo esc_url(home_url()); ?>';
            </script>
            <script src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../assets/js/admin-app.js'); ?>"></script>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Validate JWT token
     */
    private function validate_token($token) {
        // Same validation as SSO class
        $secret_key = get_option('kursagenten_sso_secret', '');
        
        if (empty($secret_key)) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expected_signature = hash_hmac('sha256', $header . '.' . $payload, $secret_key, true);
        $expected_signature_base64 = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));
        
        if (!hash_equals($expected_signature_base64, $signature)) {
            return false;
        }
        
        $payload_data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if (!$payload_data || (isset($payload_data['exp']) && $payload_data['exp'] < time())) {
            return false;
        }
        
        return $payload_data;
    }
}
```

#### 3. React/Vue App Struktur

**Prosjektstruktur:**

```
kursagenten-admin-app/
├── src/
│   ├── components/
│   │   ├── CategoryList.tsx
│   │   ├── CategoryForm.tsx
│   │   ├── LocationList.tsx
│   │   ├── LocationForm.tsx
│   │   ├── InstructorList.tsx
│   │   ├── InstructorForm.tsx
│   │   ├── SettingsForm.tsx
│   │   └── PageList.tsx
│   ├── services/
│   │   └── api.ts
│   ├── App.tsx
│   └── main.tsx
├── package.json
└── vite.config.ts
```

**Eksempel: `src/services/api.ts`**

```typescript
const API_URL = window.KURSAGENTEN_API_URL;
const TOKEN = window.KURSAGENTEN_TOKEN;

export const api = {
  async request(endpoint: string, options: RequestInit = {}) {
    const response = await fetch(`${API_URL}${endpoint}`, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${TOKEN}`,
        ...options.headers,
      },
    });

    if (!response.ok) {
      throw new Error(`API error: ${response.statusText}`);
    }

    return response.json();
  },

  // Categories
  getCategories: () => api.request('/categories'),
  createCategory: (data: any) => api.request('/categories', { method: 'POST', body: JSON.stringify(data) }),
  updateCategory: (id: number, data: any) => api.request(`/categories/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deleteCategory: (id: number) => api.request(`/categories/${id}`, { method: 'DELETE' }),

  // Locations
  getLocations: () => api.request('/locations'),
  createLocation: (data: any) => api.request('/locations', { method: 'POST', body: JSON.stringify(data) }),
  updateLocation: (id: number, data: any) => api.request(`/locations/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deleteLocation: (id: number) => api.request(`/locations/${id}`, { method: 'DELETE' }),

  // Instructors
  getInstructors: () => api.request('/instructors'),
  createInstructor: (data: any) => api.request('/instructors', { method: 'POST', body: JSON.stringify(data) }),
  updateInstructor: (id: number, data: any) => api.request(`/instructors/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deleteInstructor: (id: number) => api.request(`/instructors/${id}`, { method: 'DELETE' }),

  // Settings
  getSettings: () => api.request('/settings'),
  updateSettings: (data: any) => api.request('/settings', { method: 'PUT', body: JSON.stringify(data) }),
};
```

**Eksempel: `src/components/CategoryList.tsx`**

```typescript
import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import CategoryForm from './CategoryForm';

interface Category {
  id: number;
  name: string;
  slug: string;
  description: string;
  count: number;
}

export default function CategoryList() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState<Category | null>(null);

  useEffect(() => {
    loadCategories();
  }, []);

  const loadCategories = async () => {
    try {
      const data = await api.getCategories();
      setCategories(data);
    } catch (error) {
      console.error('Failed to load categories:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Er du sikker på at du vil slette denne kategorien?')) {
      return;
    }

    try {
      await api.deleteCategory(id);
      loadCategories();
    } catch (error) {
      console.error('Failed to delete category:', error);
      alert('Kunne ikke slette kategori');
    }
  };

  if (loading) {
    return <div>Laster...</div>;
  }

  return (
    <div className="category-list">
      <div className="header">
        <h1>Kurskategorier</h1>
        <button onClick={() => setEditing({} as Category)}>+ Ny kategori</button>
      </div>

      {editing && (
        <CategoryForm
          category={editing}
          onSave={() => {
            setEditing(null);
            loadCategories();
          }}
          onCancel={() => setEditing(null)}
        />
      )}

      <div className="categories">
        {categories.map((category) => (
          <div key={category.id} className="category-card">
            <h3>{category.name}</h3>
            <p>{category.description || 'Ingen beskrivelse'}</p>
            <div className="meta">
              <span>{category.count} kurs</span>
            </div>
            <div className="actions">
              <button onClick={() => setEditing(category)}>Rediger</button>
              <button onClick={() => handleDelete(category.id)}>Slett</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

#### 4. Kursadmin Iframe Integration

**C# kodeeksempel:**

```csharp
public class AdminAppFrame
{
    private readonly WordPressSSO _sso;
    
    public AdminAppFrame(WordPressSSO sso)
    {
        _sso = sso;
    }
    
    public string GetAdminAppUrl(string siteUrl)
    {
        var token = _sso.GenerateSSOToken(GetCurrentUserEmail(), siteUrl);
        return $"{siteUrl}/kursagenten-admin/?token={token}";
    }
}
```

**Syncfusion Iframe komponent:**

```csharp
// I kursadmin UI
var adminAppUrl = adminAppFrame.GetAdminAppUrl(siteUrl);

// Iframe i Syncfusion
<SfDialog>
    <iframe src="@adminAppUrl" 
            style="width: 100%; height: 600px; border: none;" 
            allow="fullscreen">
    </iframe>
</SfDialog>
```

---

## Implementeringsplan

### Fase 1: SSO (Snarveier)
1. ✅ WordPress-plugin: SSO-endepunkt
2. ✅ Kursadmin: Token-generering og redirect
3. ✅ Testing av innlogging

**Estimert tid: 2-3 dager**

### Fase 2: REST API
1. ✅ WordPress-plugin: REST API-endepunkter
2. ✅ Autentisering og validering
3. ✅ Testing av API-endepunkter

**Estimert tid: 3-5 dager**

### Fase 3: Admin App
1. ✅ React/Vue app setup
2. ✅ UI-komponenter for redigering
3. ✅ API-integrasjon
4. ✅ Testing

**Estimert tid: 1 uke**

### Fase 4: Iframe Integration
1. ✅ WordPress-plugin: Admin app route
2. ✅ Kursadmin: Iframe-integrasjon
3. ✅ Testing av hele flyten

**Estimert tid: 2-3 dager**

**Total estimert tid: 2-3 uker**

---

## Sikkerhet

### SSO Token
- ✅ JWT med HMAC-SHA256 signering
- ✅ Kort levetid (5 minutter)
- ✅ HTTPS påkrevd
- ✅ E-post-validering

### REST API
- ✅ Bearer token autentisering
- ✅ Admin-tilgang påkrevd
- ✅ Input sanitization
- ✅ Nonce-verifisering (valgfritt)

### Admin App
- ✅ Token-validering ved innlasting
- ✅ Token lagres i minnet (ikke localStorage)
- ✅ CORS-beskyttelse

---

## Testing

### SSO Testing
1. Test innlogging med gyldig token
2. Test innlogging med utløpt token
3. Test innlogging med ugyldig token
4. Test redirect til riktig admin-side

### REST API Testing
1. Test GET requests
2. Test POST/PUT/DELETE requests
3. Test autentisering
4. Test feilhåndtering

### Admin App Testing
1. Test innlasting av app
2. Test CRUD-operasjoner
3. Test feilhåndtering
4. Test responsivt design

---

## Vedlikehold

### WordPress-plugin
- REST API-endepunkter kan utvides etter behov
- Nye taksonomier kan legges til enkelt
- SSO-secret kan regenereres ved behov

### Admin App
- Kan oppdateres uavhengig av WordPress-plugin
- Nye funksjoner kan legges til enkelt
- UI kan forbedres kontinuerlig

---

## Notater

- SSO-secret key må deles mellom WordPress-plugin og kursadmin
- Token-levetid kan justeres etter behov
- Admin app kan hostes separat hvis ønskelig
- CORS må konfigureres hvis app hostes separat

---

## Referanser

- WordPress REST API: https://developer.wordpress.org/rest-api/
- JWT: https://jwt.io/
- React: https://react.dev/
- Vue: https://vuejs.org/

