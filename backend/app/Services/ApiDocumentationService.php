<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

/**
 * Custom API Documentation Generator for AutoERP
 * Generates documentation from existing route definitions and controller methods
 */
class ApiDocumentationService
{
    private array $documentation = [];
    private array $moduleRoutes = [];

    public function generateDocumentation(): array
    {
        $this->scanApplicationRoutes();
        $this->scanModuleRoutes();
        $this->buildDocumentationStructure();
        
        return $this->documentation;
    }

    private function scanApplicationRoutes(): void
    {
        $routes = Route::getRoutes();
        
        foreach ($routes as $route) {
            $methods = $route->methods();
            $uri = $route->uri();
            $actionData = $route->getAction();
            
            // Skip non-API routes
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }
            
            $routeInfo = [
                'path' => '/' . ltrim($uri, 'api/'),
                'methods' => array_filter($methods, fn($m) => $m !== 'HEAD'),
                'controller' => $actionData['controller'] ?? null,
                'middleware' => $actionData['middleware'] ?? [],
                'name' => $route->getName(),
            ];
            
            $this->processRouteInfo($routeInfo);
        }
    }

    private function scanModuleRoutes(): void
    {
        $modulesPath = base_path('modules');
        
        if (!is_dir($modulesPath)) {
            return;
        }
        
        $modules = scandir($modulesPath);
        
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }
            
            $routeFile = "{$modulesPath}/{$module}/routes/api.php";
            
            if (file_exists($routeFile)) {
                $this->moduleRoutes[$module] = $routeFile;
            }
        }
    }

    private function processRouteInfo(array $routeInfo): void
    {
        if (!$routeInfo['controller']) {
            return;
        }
        
        [$controllerClass, $method] = explode('@', $routeInfo['controller']);
        
        try {
            $reflection = new ReflectionClass($controllerClass);
            $methodReflection = $reflection->getMethod($method);
            
            $docComment = $methodReflection->getDocComment();
            $parameters = $this->extractMethodParameters($methodReflection);
            
            $middleware = is_array($routeInfo['middleware']) ? $routeInfo['middleware'] : [];
            
            $endpointDoc = [
                'endpoint' => $routeInfo['path'],
                'http_methods' => $routeInfo['methods'],
                'controller' => class_basename($controllerClass),
                'action' => $method,
                'requires_auth' => in_array('auth:sanctum', $middleware),
                'parameters' => $parameters,
                'description' => $this->parseDocComment($docComment),
                'route_name' => $routeInfo['name'],
            ];
            
            $module = $this->detectModuleFromController($controllerClass);
            
            if (!isset($this->documentation[$module])) {
                $this->documentation[$module] = [];
            }
            
            $this->documentation[$module][] = $endpointDoc;
        } catch (\Exception $e) {
            // Skip routes that can't be reflected
        }
    }

    private function extractMethodParameters(ReflectionMethod $method): array
    {
        $params = [];
        
        foreach ($method->getParameters() as $param) {
            $paramType = $param->getType();
            $typeName = $paramType ? $paramType->getName() : 'mixed';
            
            // Check if it's a Form Request
            if ($paramType && !$paramType->isBuiltin()) {
                $className = $paramType->getName();
                if (class_exists($className)) {
                    $params[] = [
                        'name' => $param->getName(),
                        'type' => 'FormRequest',
                        'class' => class_basename($className),
                        'required' => !$param->isDefaultValueAvailable(),
                    ];
                    continue;
                }
            }
            
            $params[] = [
                'name' => $param->getName(),
                'type' => $typeName,
                'required' => !$param->isDefaultValueAvailable(),
            ];
        }
        
        return $params;
    }

    private function parseDocComment($docComment): string
    {
        if (!$docComment) {
            return '';
        }
        
        // Extract description from docblock
        $lines = explode("\n", $docComment);
        $description = '';
        
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B*/");
            
            if (empty($line) || str_starts_with($line, '@')) {
                continue;
            }
            
            $description .= $line . ' ';
        }
        
        return trim($description);
    }

    private function detectModuleFromController(string $controllerClass): string
    {
        if (str_contains($controllerClass, 'Modules\\')) {
            preg_match('/Modules\\\\([^\\\\]+)/', $controllerClass, $matches);
            return $matches[1] ?? 'Core';
        }
        
        return 'Application';
    }

    private function buildDocumentationStructure(): void
    {
        // Group and organize documentation
        foreach ($this->documentation as $module => &$endpoints) {
            usort($endpoints, function($a, $b) {
                return strcmp($a['endpoint'], $b['endpoint']);
            });
        }
    }

    public function exportAsJson(): string
    {
        return json_encode([
            'autoerp_api_version' => '1.0.0',
            'generated_at' => now()->toIso8601String(),
            'modules' => $this->documentation,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function exportAsMarkdown(): string
    {
        $markdown = "# AutoERP API Documentation\n\n";
        $markdown .= "Generated: " . now()->toDateTimeString() . "\n\n";
        $markdown .= "---\n\n";
        
        foreach ($this->documentation as $module => $endpoints) {
            $markdown .= "## Module: {$module}\n\n";
            
            foreach ($endpoints as $endpoint) {
                $methods = implode(', ', $endpoint['http_methods']);
                $markdown .= "### `{$methods}` {$endpoint['endpoint']}\n\n";
                
                if ($endpoint['description']) {
                    $markdown .= "**Description:** {$endpoint['description']}\n\n";
                }
                
                $markdown .= "**Controller:** `{$endpoint['controller']}@{$endpoint['action']}`\n\n";
                
                if ($endpoint['requires_auth']) {
                    $markdown .= "**Authentication:** Required (Bearer Token)\n\n";
                }
                
                if (!empty($endpoint['parameters'])) {
                    $markdown .= "**Parameters:**\n\n";
                    foreach ($endpoint['parameters'] as $param) {
                        $required = $param['required'] ? 'required' : 'optional';
                        $markdown .= "- `{$param['name']}` ({$param['type']}) - {$required}\n";
                    }
                    $markdown .= "\n";
                }
                
                $markdown .= "---\n\n";
            }
        }
        
        return $markdown;
    }
}
