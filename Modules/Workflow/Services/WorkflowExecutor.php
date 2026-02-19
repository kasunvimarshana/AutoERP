<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\Event;
use Modules\Workflow\Enums\ActionType;
use Modules\Workflow\Events\WorkflowActionExecuted;
use Modules\Workflow\Exceptions\WorkflowExecutionException;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Models\WorkflowStep;

class WorkflowExecutor
{
    /**
     * Pattern for validating safe mathematical expressions
     */
    private const MATH_EXPRESSION_PATTERN = '/^[\d\s\+\-\*\/\(\)\.]+$/';

    public function executeAction(WorkflowInstance $instance, WorkflowStep $step): array
    {
        $config = $step->action_config ?? [];
        $actionType = ActionType::tryFrom($config['type'] ?? '');

        if (! $actionType) {
            throw new WorkflowExecutionException('Invalid action type');
        }

        $result = match ($actionType) {
            ActionType::CREATE_RECORD => $this->createRecord($instance, $config),
            ActionType::UPDATE_RECORD => $this->updateRecord($instance, $config),
            ActionType::DELETE_RECORD => $this->deleteRecord($instance, $config),
            ActionType::SEND_NOTIFICATION => $this->sendNotification($instance, $config),
            ActionType::SEND_EMAIL => $this->sendEmail($instance, $config),
            ActionType::WEBHOOK => $this->callWebhook($instance, $config),
            ActionType::SCRIPT => $this->executeScript($instance, $config),
            ActionType::WAIT => $this->wait($instance, $config),
        };

        event(new WorkflowActionExecuted($instance, $step, $actionType, $result));

        return $result;
    }

    public function evaluateConditions(WorkflowInstance $instance, WorkflowStep $step): array
    {
        $conditions = $step->conditions()->orderBy('sequence')->get();
        $context = $instance->context ?? [];

        foreach ($conditions as $condition) {
            if ($condition->evaluate($context)) {
                return [
                    'matched' => true,
                    'condition_id' => $condition->id,
                    'next_steps' => [$condition->nextStep],
                ];
            }
        }

        $defaultCondition = $conditions->where('is_default', true)->first();
        if ($defaultCondition && $defaultCondition->nextStep) {
            return [
                'matched' => true,
                'default' => true,
                'next_steps' => [$defaultCondition->nextStep],
            ];
        }

        return ['matched' => false, 'next_steps' => []];
    }

    public function executeParallel(WorkflowInstance $instance, WorkflowStep $step): array
    {
        $nextStepIds = $step->getNextSteps();
        $steps = WorkflowStep::whereIn('id', $nextStepIds)->get();

        return [
            'parallel' => true,
            'next_steps' => $steps->all(),
        ];
    }

    private function createRecord(WorkflowInstance $instance, array $config): array
    {
        $modelClass = $config['model'] ?? null;
        $data = $this->interpolateData($config['data'] ?? [], $instance->context);

        if (! $modelClass || ! class_exists($modelClass)) {
            throw new WorkflowExecutionException('Invalid model class for create action');
        }

        $record = $modelClass::create($data);

        return ['action' => 'create_record', 'record_id' => $record->id, 'model' => $modelClass];
    }

    private function updateRecord(WorkflowInstance $instance, array $config): array
    {
        $modelClass = $config['model'] ?? null;
        $recordId = $config['record_id'] ?? $instance->context['entity_id'] ?? null;
        $data = $this->interpolateData($config['data'] ?? [], $instance->context);

        if (! $modelClass || ! class_exists($modelClass) || ! $recordId) {
            throw new WorkflowExecutionException('Invalid configuration for update action');
        }

        $record = $modelClass::findOrFail($recordId);
        $record->update($data);

        return ['action' => 'update_record', 'record_id' => $recordId, 'model' => $modelClass];
    }

    private function deleteRecord(WorkflowInstance $instance, array $config): array
    {
        $modelClass = $config['model'] ?? null;
        $recordId = $config['record_id'] ?? $instance->context['entity_id'] ?? null;

        if (! $modelClass || ! class_exists($modelClass) || ! $recordId) {
            throw new WorkflowExecutionException('Invalid configuration for delete action');
        }

        $record = $modelClass::findOrFail($recordId);
        $record->delete();

        return ['action' => 'delete_record', 'record_id' => $recordId, 'model' => $modelClass];
    }

    private function sendNotification(WorkflowInstance $instance, array $config): array
    {
        if (! class_exists(\Modules\Notification\Services\NotificationService::class)) {
            throw new WorkflowExecutionException('Notification module not available');
        }

        Event::dispatch('workflow.notification.send', [
            'instance' => $instance,
            'config' => $config,
        ]);

        return ['action' => 'send_notification', 'sent' => true];
    }

    private function sendEmail(WorkflowInstance $instance, array $config): array
    {
        $to = $config['to'] ?? null;
        $subject = $this->interpolateString($config['subject'] ?? '', $instance->context);
        $body = $this->interpolateString($config['body'] ?? '', $instance->context);

        if (! $to) {
            throw new WorkflowExecutionException('Email recipient is required');
        }

        Event::dispatch('workflow.email.send', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'instance' => $instance,
        ]);

        return ['action' => 'send_email', 'to' => $to, 'sent' => true];
    }

    private function callWebhook(WorkflowInstance $instance, array $config): array
    {
        $url = $config['url'] ?? null;
        $method = $config['method'] ?? 'POST';
        $data = $this->interpolateData($config['data'] ?? [], $instance->context);

        if (! $url) {
            throw new WorkflowExecutionException('Webhook URL is required');
        }

        Event::dispatch('workflow.webhook.call', [
            'url' => $url,
            'method' => $method,
            'data' => $data,
            'instance' => $instance,
        ]);

        return ['action' => 'webhook', 'url' => $url, 'method' => $method];
    }

    private function executeScript(WorkflowInstance $instance, array $config): array
    {
        $script = $config['script'] ?? null;
        $language = $config['language'] ?? 'expression';

        if (! $script) {
            throw new WorkflowExecutionException('Script content is required');
        }

        // Only support safe expression language - no arbitrary code execution
        if ($language !== 'expression') {
            throw new WorkflowExecutionException('Only "expression" language is supported for security');
        }

        try {
            $result = $this->evaluateExpression($script, $instance->context);
            
            return [
                'action' => 'execute_script',
                'language' => $language,
                'result' => $result,
                'success' => true,
            ];
        } catch (\Throwable $e) {
            throw new WorkflowExecutionException('Script execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Evaluate safe expressions (mathematical, string operations, comparisons)
     * 
     * Supported operations:
     * - Mathematical: +, -, *, /, %
     * - Comparisons: ==, !=, <, >, <=, >=
     * - Logical: &&, ||
     * - String operations: concat, upper, lower, trim
     * - Context access: {{variable}}
     * 
     * Examples:
     * - "{{price}} * {{quantity}}"
     * - "{{status}} == 'approved'"
     * - "concat({{firstName}}, ' ', {{lastName}})"
     */
    private function evaluateExpression(string $expression, array $context): mixed
    {
        // Interpolate context variables
        $interpolated = $this->interpolateString($expression, $context);
        
        // Remove whitespace
        $interpolated = trim($interpolated);
        
        // Handle string functions
        if (preg_match('/^(concat|upper|lower|trim)\((.*)\)$/i', $interpolated, $matches)) {
            return $this->evaluateStringFunction($matches[1], $matches[2], $context);
        }
        
        // Handle simple mathematical expressions (safe subset)
        if (preg_match(self::MATH_EXPRESSION_PATTERN, $interpolated)) {
            return $this->evaluateMathExpression($interpolated);
        }
        
        // Handle comparison operations
        if (preg_match('/(.*?)\s*(==|!=|<=|>=|<|>)\s*(.*)/', $interpolated, $matches)) {
            return $this->evaluateComparison(trim($matches[1]), $matches[2], trim($matches[3]));
        }
        
        // Handle logical operations
        if (preg_match('/(.*?)\s*(&&|\|\|)\s*(.*)/', $interpolated, $matches)) {
            return $this->evaluateLogical(trim($matches[1]), $matches[2], trim($matches[3]), $context);
        }
        
        // Return the interpolated value as-is
        return $interpolated;
    }

    /**
     * Evaluate string function
     */
    private function evaluateStringFunction(string $function, string $args, array $context): string
    {
        // Parse arguments (simple comma-separated)
        $argList = array_map('trim', explode(',', $args));
        $argList = array_map(fn($arg) => trim($arg, '\'"'), $argList);
        
        return match(strtolower($function)) {
            'concat' => implode('', $argList),
            'upper' => strtoupper($argList[0] ?? ''),
            'lower' => strtolower($argList[0] ?? ''),
            'trim' => trim($argList[0] ?? ''),
            default => throw new WorkflowExecutionException("Unknown string function: {$function}"),
        };
    }

    /**
     * Evaluate mathematical expression (safe subset only)
     * 
     * Implements a safe mathematical expression evaluator without using eval().
     * Supports: +, -, *, /, %, parentheses
     * Uses operator precedence and proper parsing
     */
    private function evaluateMathExpression(string $expression): float|int
    {
        // Only allow numbers, basic operators, parentheses, and decimal points
        if (! preg_match(self::MATH_EXPRESSION_PATTERN, $expression)) {
            throw new WorkflowExecutionException('Invalid mathematical expression');
        }

        try {
            return $this->parseExpression($expression);
        } catch (\Throwable $e) {
            throw new WorkflowExecutionException('Mathematical expression error: ' . $e->getMessage());
        }
    }

    /**
     * Parse and evaluate mathematical expression using recursive descent parser
     * Implements proper operator precedence: () > * / % > + -
     */
    private function parseExpression(string $expr): float|int
    {
        $expr = str_replace(' ', '', $expr); // Remove whitespace
        $pos = 0;
        return $this->parseAddSub($expr, $pos);
    }

    /**
     * Parse addition and subtraction (lowest precedence)
     */
    private function parseAddSub(string $expr, int &$pos): float|int
    {
        $left = $this->parseMulDiv($expr, $pos);
        
        while ($pos < strlen($expr)) {
            $op = $expr[$pos] ?? '';
            
            if ($op === '+' || $op === '-') {
                $pos++; // Consume operator
                $right = $this->parseMulDiv($expr, $pos);
                
                if ($op === '+') {
                    $left = bcadd((string)$left, (string)$right, 6);
                } else {
                    $left = bcsub((string)$left, (string)$right, 6);
                }
            } else {
                break;
            }
        }
        
        return (float)$left;
    }

    /**
     * Parse multiplication, division, and modulo (higher precedence)
     */
    private function parseMulDiv(string $expr, int &$pos): float|int
    {
        $left = $this->parseUnary($expr, $pos);
        
        while ($pos < strlen($expr)) {
            $op = $expr[$pos] ?? '';
            
            if ($op === '*' || $op === '/' || $op === '%') {
                $pos++; // Consume operator
                $right = $this->parseUnary($expr, $pos);
                
                if ($op === '*') {
                    $left = bcmul((string)$left, (string)$right, 6);
                } elseif ($op === '/') {
                    if ((float)$right == 0) {
                        throw new WorkflowExecutionException('Division by zero');
                    }
                    $left = bcdiv((string)$left, (string)$right, 6);
                } else { // %
                    if ((float)$right == 0) {
                        throw new WorkflowExecutionException('Modulo by zero');
                    }
                    $left = bcmod((string)$left, (string)$right);
                }
            } else {
                break;
            }
        }
        
        return (float)$left;
    }

    /**
     * Parse unary operators and numbers/parentheses (highest precedence)
     */
    private function parseUnary(string $expr, int &$pos): float|int
    {
        // Handle unary minus
        if (isset($expr[$pos]) && $expr[$pos] === '-') {
            $pos++;
            return -$this->parseUnary($expr, $pos);
        }
        
        // Handle unary plus
        if (isset($expr[$pos]) && $expr[$pos] === '+') {
            $pos++;
            return $this->parseUnary($expr, $pos);
        }
        
        return $this->parsePrimary($expr, $pos);
    }

    /**
     * Parse numbers and parenthesized expressions
     */
    private function parsePrimary(string $expr, int &$pos): float|int
    {
        // Handle parentheses
        if (isset($expr[$pos]) && $expr[$pos] === '(') {
            $pos++; // Skip opening parenthesis
            $result = $this->parseAddSub($expr, $pos);
            
            if (!isset($expr[$pos]) || $expr[$pos] !== ')') {
                throw new WorkflowExecutionException('Mismatched parentheses');
            }
            
            $pos++; // Skip closing parenthesis
            return $result;
        }
        
        // Parse number
        $numStr = '';
        while ($pos < strlen($expr) && (ctype_digit($expr[$pos]) || $expr[$pos] === '.')) {
            $numStr .= $expr[$pos];
            $pos++;
        }
        
        if ($numStr === '') {
            throw new WorkflowExecutionException('Expected number or expression');
        }
        
        return (float)$numStr;
    }

    /**
     * Evaluate comparison
     */
    private function evaluateComparison(string $left, string $operator, string $right): bool
    {
        // Try to convert to numbers if possible
        $leftNum = is_numeric($left) ? (float)$left : $left;
        $rightNum = is_numeric($right) ? (float)$right : $right;
        
        return match($operator) {
            '==' => $leftNum == $rightNum,
            '!=' => $leftNum != $rightNum,
            '<' => $leftNum < $rightNum,
            '>' => $leftNum > $rightNum,
            '<=' => $leftNum <= $rightNum,
            '>=' => $leftNum >= $rightNum,
            default => throw new WorkflowExecutionException("Unknown comparison operator: {$operator}"),
        };
    }

    /**
     * Evaluate logical operation
     */
    private function evaluateLogical(string $left, string $operator, string $right, array $context): bool
    {
        $leftResult = $this->evaluateExpression($left, $context);
        $rightResult = $this->evaluateExpression($right, $context);
        
        // Convert to boolean
        $leftBool = filter_var($leftResult, FILTER_VALIDATE_BOOLEAN);
        $rightBool = filter_var($rightResult, FILTER_VALIDATE_BOOLEAN);
        
        return match($operator) {
            '&&' => $leftBool && $rightBool,
            '||' => $leftBool || $rightBool,
            default => throw new WorkflowExecutionException("Unknown logical operator: {$operator}"),
        };
    }

    private function wait(WorkflowInstance $instance, array $config): array
    {
        $duration = $config['duration'] ?? 0;
        $until = $config['until'] ?? null;

        return ['action' => 'wait', 'duration' => $duration, 'until' => $until];
    }

    private function interpolateData(array $data, array $context): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->interpolateString($value, $context);
            } elseif (is_array($value)) {
                $result[$key] = $this->interpolateData($value, $context);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function interpolateString(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) use ($context) {
            $key = trim($matches[1]);

            return data_get($context, $key, $matches[0]);
        }, $template);
    }
}
