<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiting
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildException($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request)
    {
        if ($user = $request->user()) {
            return sha1('api_rate_limit:' . $user->id);
        }

        return sha1('api_rate_limit:' . $request->ip());
    }

    /**
     * Create a 'too many attempts' exception.
     */
    protected function buildException($key, $maxAttempts)
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        if (!is_null($retryAfter)) {
            $response->headers->add(['Retry-After' => $retryAfter]);
        }

        return $response;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts($key, $maxAttempts)
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }

    /**
     * Get the number of seconds until the next retry.
     */
    protected function getTimeUntilNextRetry($key)
    {
        return $this->limiter->availableIn($key);
    }
} 