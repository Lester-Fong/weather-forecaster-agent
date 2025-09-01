# Performance Testing Documentation

This document outlines the performance testing strategy for the Weather Forecaster Agent application.

## Performance Metrics

### 1. Page Load Metrics
- **Time to First Byte (TTFB)**: < 200ms
- **First Contentful Paint (FCP)**: < 1.0s
- **Largest Contentful Paint (LCP)**: < 2.5s
- **Time to Interactive (TTI)**: < 3.5s
- **Total Page Load Time**: < 3.0s

### 2. Runtime Metrics
- **First Input Delay (FID)**: < 100ms
- **Cumulative Layout Shift (CLS)**: < 0.1
- **Response Time for Chat**: < 1.5s
- **Memory Usage**: < 50MB
- **CPU Usage**: < 30%

## Testing Tools

### 1. Lighthouse
Run Lighthouse audits for performance, best practices, and PWA capabilities:

```bash
npx lighthouse https://your-app-url.com --view
```

### 2. WebPageTest
Use WebPageTest for detailed performance analysis across different devices and network conditions.

### 3. Chrome DevTools
Use Chrome DevTools Performance tab to analyze:
- JavaScript execution time
- Layout and rendering performance
- Memory usage

### 4. Laravel Debugbar
For backend performance monitoring:

```php
// Example usage in development
Debugbar::startMeasure('weather-api', 'Weather API Call');
$weatherData = $this->weatherService->getCurrentWeather($location);
Debugbar::stopMeasure('weather-api');
```

## Performance Testing Scripts

### 1. API Endpoint Performance Test

```php
<?php

namespace Tests\Performance;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test weather query API performance.
     */
    public function test_weather_query_api_performance(): void
    {
        // Create test location
        $location = Location::factory()->create();

        // Measure API response time
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/weather/query', [
            'query' => 'What is the weather in London?',
            'session_id' => 'performance_test',
        ]);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Log the response time
        Log::info("Weather query API response time: {$responseTime}ms");
        
        // API should respond within 1500ms
        $this->assertLessThan(1500, $responseTime);
        $response->assertStatus(200);
    }
}
```

### 2. Frontend Load Time Test

```javascript
// tests/performance/frontend.performance.js
const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  // Enable performance metrics
  const client = await page.context().newCDPSession(page);
  await client.send('Performance.enable');
  
  // Navigate to the page
  const navigationStart = Date.now();
  await page.goto('https://your-app-url.com');
  
  // Wait for chat container to be visible
  await page.waitForSelector('.chat-container');
  const timeToChat = Date.now() - navigationStart;
  
  // Get performance metrics
  const performanceMetrics = await client.send('Performance.getMetrics');
  
  // Extract relevant metrics
  const metrics = {
    timeToChat,
    dcl: performanceMetrics.metrics.find(m => m.name === 'DomContentLoaded').value,
    load: performanceMetrics.metrics.find(m => m.name === 'LoadEvent').value,
    javaScriptExecutionTime: performanceMetrics.metrics.find(m => m.name === 'ScriptDuration').value,
  };
  
  // Log results
  console.log('Performance Metrics:', metrics);
  fs.writeFileSync('performance-results.json', JSON.stringify(metrics, null, 2));
  
  await browser.close();
})();
```

## Performance Optimization Techniques

### 1. Frontend Optimizations
- [ ] Bundle size optimization with code splitting
- [ ] Lazy loading of components
- [ ] Image optimization (WebP, lazy loading)
- [ ] Minification of CSS/JS
- [ ] Critical CSS extraction
- [ ] Use of CDN for static assets
- [ ] Cache API responses in localStorage/IndexedDB

### 2. Backend Optimizations
- [ ] Database query optimization
- [ ] API response caching
- [ ] Rate limiting to prevent abuse
- [ ] Compression of responses (gzip/brotli)
- [ ] Efficient database indexing
- [ ] Optimize Eloquent queries (eager loading)

### 3. Network Optimizations
- [ ] HTTP/2 support
- [ ] Proper Cache-Control headers
- [ ] Minimize HTTP requests
- [ ] Use Keep-Alive connections

## Performance Testing Scenarios

### 1. Load Testing
Test the application with simulated concurrent users:

```bash
# Using k6 for load testing
k6 run --vus 50 --duration 30s load-test.js
```

Example load test script:
```javascript
// load-test.js
import http from 'k6/http';
import { sleep } from 'k6';

export default function () {
  const payload = JSON.stringify({
    query: 'What is the weather in London?',
    session_id: `load_test_${__VU}_${__ITER}`,
  });
  
  const params = {
    headers: {
      'Content-Type': 'application/json',
    },
  };
  
  http.post('https://your-app-url.com/api/weather/query', payload, params);
  sleep(1);
}
```

### 2. Stress Testing
Test the application's limits by gradually increasing the load:

```bash
# Using k6 for stress testing with ramping users
k6 run --stage 30s:100,1m:200,30s:0 stress-test.js
```

### 3. Endurance Testing
Test application performance over extended periods:

```bash
# 1-hour endurance test with 20 users
k6 run --vus 20 --duration 1h endurance-test.js
```

## Performance Budget

| Metric | Budget |
|--------|--------|
| JavaScript Bundle Size | < 200KB (gzipped) |
| CSS Bundle Size | < 50KB (gzipped) |
| API Response Time | < 500ms (p95) |
| Lighthouse Performance Score | > 85 |
| Total Page Weight | < 1MB |

## Monitoring and Reporting

### 1. Real User Monitoring (RUM)
Implement RUM to collect actual user experience data:

```javascript
// Example RUM implementation
if ('performance' in window) {
  window.addEventListener('load', () => {
    setTimeout(() => {
      const timing = window.performance.timing;
      const metrics = {
        pageLoadTime: timing.loadEventEnd - timing.navigationStart,
        domReadyTime: timing.domComplete - timing.domLoading,
        ttfb: timing.responseStart - timing.navigationStart,
      };
      
      // Send metrics to analytics or custom endpoint
      navigator.sendBeacon('/api/metrics', JSON.stringify(metrics));
    }, 0);
  });
}
```

### 2. Server Monitoring
Use Laravel Telescope or custom middleware to monitor server performance:

```php
// app/Http/Middleware/PerformanceMonitoring.php
public function handle($request, Closure $next)
{
    $startTime = microtime(true);
    $response = $next($request);
    $endTime = microtime(true);
    
    $processingTime = ($endTime - $startTime) * 1000;
    Log::channel('performance')->info("Request processed in {$processingTime}ms", [
        'uri' => $request->getRequestUri(),
        'method' => $request->getMethod(),
        'time' => $processingTime,
    ]);
    
    return $response;
}
```

## Continuous Performance Testing

Integrate performance testing into the CI/CD pipeline:

```yaml
# Example GitHub Actions workflow
name: Performance Testing

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run Lighthouse CI
        uses: treosh/lighthouse-ci-action@v8
        with:
          urls: |
            https://staging-app-url.com/
          budgetPath: ./lighthouse-budget.json
          uploadArtifacts: true
```

## Performance Regression Testing

Compare performance metrics against baseline for each release:

```bash
# Store baseline performance metrics
npm run performance-test -- --save-baseline

# Compare against baseline
npm run performance-test -- --compare-baseline
```
