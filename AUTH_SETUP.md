# API Auth Setup (Sanctum) for Laravel 12

This project currently uses only the default `web` session guard and has no auth package installed. The simplest way to add API auth is Laravel Sanctum with personal access tokens.

## 1) Install Sanctum

- Composer install
```bash
composer require laravel/sanctum
```

- Publish migrations and config
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

- Run migrations
```bash
php artisan migrate
```

## 2) Enable tokens on the `User` model

- File: `app/Models/User.php`
- Add the trait import and usage:
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

## 3) Add API routes (using existing `UserController`)

- File: `routes/api.php`
- Keep your existing routes. Add protected variants that require a Bearer token:
```php
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// existing routes ...

// Protected endpoints (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('users/{id}/secure', [UserController::class, 'showProtected']);
    Route::put('users/{id}/secure', [UserController::class, 'updateProtected']);
    Route::patch('users/{id}/secure', [UserController::class, 'updateProtected']);
});
```

## 4) Update `UserController` (no new controller needed)

- File: `app/Http/Controllers/Api/UserController.php`
- Add two methods that will be hit by the protected routes:
```php
public function showProtected(Request $request, string $id) { /* same as show() */ }
public function updateProtected(Request $request, string $id) { /* same as update() */ }
```
These methods behave like `show()` and `update()` but can only be accessed when a valid Sanctum token is provided.

## 5) How to use the token

- Send the token from `register`/`login` as an Authorization header:
```
Authorization: Bearer <token>
```
- Example requests:
```bash
# Example: call protected user show/update
curl http://localhost:8000/api/users/1/secure \
  -H "Authorization: Bearer <token>"

curl -X PATCH http://localhost:8000/api/users/1/secure \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"name":"New Name"}'
```

## 6) Optional: protect your existing user routes

- Wrap your existing routes in `routes/api.php` if you want them protected:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::patch('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
});
```

## 7) Notes

- Sanctum is first‑party and lightweight; no OAuth/JWT complexity.
- No changes to `config/auth.php` are required for token usage.
- For SPA cookie auth, additionally configure Sanctum stateful middleware and CORS. For mobile/3rd‑party clients, the above token flow is sufficient.

## 8) Troubleshooting

- If tokens are not created, confirm migrations ran and `personal_access_tokens` table exists.
- Ensure `app/Models/User.php` uses `HasApiTokens` and your `User` model has the `password` attribute cast as `hashed` (already present here).
- Rate limit login with `throttle` as shown to mitigate brute force.

### How to quickly get a token for testing

- If you don't yet have a login endpoint, you can create a token in Tinker:
```bash
php artisan tinker
>>> $u = \App\Models\User::first();
>>> $u->createToken('dev')->plainTextToken;
```
Copy the token and use it in the `Authorization: Bearer <token>` header.

## 9) Cleanup tips

- To revoke all tokens for a user:
```php
$user->tokens()->delete();
```
- To name/scope tokens, use `createToken('device-name', ['ability'])` and check abilities via `tokenCan('ability')`.
