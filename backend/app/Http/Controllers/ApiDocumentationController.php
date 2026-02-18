<?php

namespace App\Http\Controllers;

use App\Services\ApiDocumentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * API Documentation Controller
 * Provides endpoints for accessing generated API documentation
 */
class ApiDocumentationController extends Controller
{
    public function __construct(
        private ApiDocumentationService $docService
    ) {}

    /**
     * Display API documentation in JSON format
     */
    public function json(): JsonResponse
    {
        $documentation = $this->docService->generateDocumentation();
        
        return response()->json([
            'autoerp_api_version' => '1.0.0',
            'generated_at' => now()->toIso8601String(),
            'documentation' => $documentation,
        ]);
    }

    /**
     * Display API documentation in Markdown format
     */
    public function markdown(): Response
    {
        $markdown = $this->docService->generateDocumentation();
        $content = $this->docService->exportAsMarkdown();
        
        return response($content, 200, [
            'Content-Type' => 'text/markdown',
        ]);
    }

    /**
     * Display interactive API documentation UI
     */
    public function ui()
    {
        $documentation = $this->docService->generateDocumentation();
        
        return view('api-documentation', [
            'modules' => $documentation,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Export documentation as downloadable file
     */
    public function export(string $format = 'json'): Response
    {
        $documentation = $this->docService->generateDocumentation();
        
        if ($format === 'markdown' || $format === 'md') {
            $content = $this->docService->exportAsMarkdown();
            $filename = 'autoerp-api-docs-' . date('Y-m-d') . '.md';
            $contentType = 'text/markdown';
        } else {
            $content = $this->docService->exportAsJson();
            $filename = 'autoerp-api-docs-' . date('Y-m-d') . '.json';
            $contentType = 'application/json';
        }
        
        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
