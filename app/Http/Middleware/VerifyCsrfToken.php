<?
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    protected $except = [
        'register', // Add your route here
        'login',
        'test',
        // Add any other routes you want to exclude
    ];
}
