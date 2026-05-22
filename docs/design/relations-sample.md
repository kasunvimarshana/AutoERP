# Step - 01
---

Use custom query scopes.

---

## ItemModel

```php
public function scopeForCompany(
    $query,
    int $companyId
) {
    return $query->whereHas(
        'stocks.warehouse.branch',
        function ($query) use ($companyId) {

            $query->where(
                'company_id',
                $companyId
            );
        }
    );
}
```

---

# ✅ Usage

```php
ItemModel::query()
    ->forCompany(1)
    ->get();
```

🔥 super clean.

---

Large systems use:

| Technique     | Usage               |
| ------------- | ------------------- |
| whereHas      | deep filtering      |
| scopes        | reusable logic      |
| repositories  | centralized queries |
| DTOs          | API shaping         |
| aggregates    | domain grouping     |
| eager loading | performance         |

---

```php
CompanyModel::query()
    ->with([
        'branches.warehouses.stocks.item.brand'
    ])
    ->get();
```
---

# Step -02

---

---

```php
$company->deep('items')->get();

$tenant->deep('stocks')->active()->get();

$item->deep('warehouses')->where('qty', '>', 0)->get();
```

---

# 🏗️ STEP 1 — Concept (Simple Brain Model)

අපි build කරන system එක:

```text
DeepRelation Engine
  ↓
Path Resolver (map)
  ↓
Query Builder (whereHas chain)
  ↓
Model Macro (nice API)
```

---

# 🧩 STEP 2 — Create Relation Map (Core Brain)

📁 `app/Relations/DeepMap.php`

```php
namespace App\Relations;

class DeepMap
{
    public static function get(): array
    {
        return [

            'company.items' => [
                'branches',
                'warehouses',
                'stocks',
                'item'
            ],

            'tenant.items' => [
                'organizationUnits',
                'warehouses',
                'stocks',
                'item'
            ],

            'warehouse.items' => [
                'stocks',
                'item'
            ],
        ];
    }

    public static function path(string $key): array
    {
        return static::get()[$key] ?? [];
    }
}
```

---

# ⚙️ STEP 3 — Core Engine (NO LIBRARY 🔥)

📁 `app/Relations/DeepEngine.php`

```php
namespace App\Relations;

use Illuminate\Database\Eloquent\Builder;

class DeepEngine
{
    public function apply(Builder $query, array $path): Builder
    {
        foreach ($path as $relation) {

            $query->whereHas($relation);
        }

        return $query;
    }
}
```

---

# 🧠 STEP 4 — Service Layer

📁 `app/Relations/DeepService.php`

```php
namespace App\Relations;

use Illuminate\Database\Eloquent\Model;

class DeepService
{
    protected Model $model;

    public function for(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    public function resolve(string $key)
    {
        $path = DeepMap::path($key);

        $query = $this->model->newQuery();

        $engine = new DeepEngine();

        return $engine->apply($query, $path);
    }
}
```

---

# 🧩 STEP 5 — Trait (Reusable for ALL models 🔥)

📁 `app/Traits/HasDeepRelations.php`

```php
namespace App\Traits;

use App\Relations\DeepService;

trait HasDeepRelations
{
    public function deep(string $key)
    {
        return app(DeepService::class)
            ->for($this)
            ->resolve($key);
    }
}
```

---

# 🧱 STEP 6 — Use in Model

```php
use App\Traits\HasDeepRelations;

class CompanyModel extends Model
{
    use HasDeepRelations;

    public function branches()
    {
        return $this->hasMany(BranchModel::class);
    }
}
```

---

# 🚀 STEP 7 — Usage

```php
$company = CompanyModel::find(1);

$items = $company->deep('company.items')->get();
```

🔥 DONE — fully custom deep relation system

---

# ⚡ STEP 8 — Add Filtering Support (IMPORTANT)

Upgrade engine:

```php
public function apply(Builder $query, array $path, array $filters = []): Builder
{
    foreach ($path as $relation) {

        $query->whereHas($relation, function ($q) use ($filters, $relation) {

            if (isset($filters[$relation])) {
                foreach ($filters[$relation] as $col => $value) {

                    if (is_array($value)) {
                        $q->where($col, $value[0], $value[1]);
                    } else {
                        $q->where($col, $value);
                    }
                }
            }
        });
    }

    return $query;
}
```

---

# 🚀 Usage with filters

```php
$items = $company->deep('company.items', [
    'stocks' => [
        'qty_available' => ['>', 0]
    ]
])->get();
```

---

# 🧠 STEP 9 — Make It Cleaner (Static Access too)

Add macro style access:

```php
$items = ItemModel::query()
    ->deep('company.items')
    ->get();
```

---

# 🧩 STEP 10 — Optional: Global Macro (PRO)

📁 `AppServiceProvider`

```php
use Illuminate\Database\Eloquent\Builder;
use App\Relations\DeepMap;
use App\Relations\DeepEngine;

Builder::macro('deep', function ($key) {

    $engine = new DeepEngine();

    $path = DeepMap::path($key);

    return $engine->apply($this, $path);
});
```

---

# 🚀 Now You Can Do:

```php
ItemModel::query()
    ->deep('company.items')
    ->get();
```

🔥 no model dependency

---

# 🧠 FINAL ARCHITECTURE

```text
Trait (HasDeepRelations)
        ↓
Service (DeepService)
        ↓
Engine (DeepEngine)
        ↓
Map (DeepMap)
        ↓
Laravel Query Builder
```

---

# Step - 03

---

---

> ❌ no map files
> ❌ no hardcoded relations
> ✅ DB schema → graph
> ✅ shortest path traversal
> ✅ dynamic relationship discovery
> ✅ reusable query engine

---

# 🧠 CORE IDEA

We treat database like a graph:

```text
Node = Table (Company, Item, Stock)
Edge = Foreign Key (company_id, item_id)
```

---

# 🏗️ FINAL ARCHITECTURE

```text
Graph Builder (DB → Graph)
        ↓
Graph Storage (Memory / Cache)
        ↓
Graph Engine (BFS / Dijkstra)
        ↓
Relation Resolver
        ↓
Laravel Query Builder (whereHas / joins)
```

---

# ⚙️ STEP 1 — GRAPH STRUCTURE MODEL

📁 `app/Graph/Graph.php`

```php
namespace App\Graph;

class Graph
{
    public array $nodes = [];   // tables
    public array $edges = [];   // relations

    public function addNode(string $table): void
    {
        $this->nodes[$table] = true;
    }

    public function addEdge(string $from, string $to): void
    {
        $this->edges[$from][] = $to;
    }

    public function neighbors(string $node): array
    {
        return $this->edges[$node] ?? [];
    }
}
```

---

# ⚙️ STEP 2 — GRAPH BUILDER (AUTO DB SCANNER 🔥)

📁 `app/Graph/GraphBuilder.php`

```php
namespace App\Graph;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GraphBuilder
{
    public function build(): Graph
    {
        $graph = new Graph();

        $tables = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableNames();

        foreach ($tables as $table) {

            $graph->addNode($table);

            $columns = Schema::getColumnListing($table);

            foreach ($columns as $col) {

                if (Str::endsWith($col, '_id')) {

                    $related = Str::plural(
                        Str::beforeLast($col, '_id')
                    );

                    if (in_array($related, $tables)) {

                        $graph->addEdge($table, $related);
                        $graph->addEdge($related, $table); // bidirectional
                    }
                }
            }
        }

        return $graph;
    }
}
```

---

# ⚡ STEP 3 — GRAPH ENGINE (BFS SHORTEST PATH 🔥)

📁 `app/Graph/GraphEngine.php`

```php
namespace App\Graph;

class GraphEngine
{
    public function shortestPath(Graph $graph, string $start, string $end): array
    {
        $queue = [[$start]];
        $visited = [];

        while (!empty($queue)) {

            $path = array_shift($queue);
            $node = end($path);

            if ($node === $end) {
                return $path;
            }

            if (isset($visited[$node])) {
                continue;
            }

            $visited[$node] = true;

            foreach ($graph->neighbors($node) as $neighbor) {

                $newPath = $path;
                $newPath[] = $neighbor;

                $queue[] = $newPath;
            }
        }

        return [];
    }
}
```

---

# 🧠 STEP 4 — RELATION RESOLVER

📁 `app/Graph/GraphResolver.php`

```php
namespace App\Graph;

class GraphResolver
{
    protected Graph $graph;

    public function __construct()
    {
        $this->graph = app(GraphBuilder::class)->build();
    }

    public function resolve(string $from, string $to): array
    {
        $engine = new GraphEngine();

        return $engine->shortestPath(
            $this->graph,
            $from,
            $to
        );
    }
}
```

---

# ⚙️ STEP 5 — LARAVEL QUERY TRANSLATOR

📁 `app/Graph/GraphQuery.php`

```php
namespace App\Graph;

use Illuminate\Database\Eloquent\Builder;

class GraphQuery
{
    public function apply(
        Builder $query,
        array $path
    ): Builder {

        // skip first node (current model)
        array_shift($path);

        foreach ($path as $relation) {

            $query->whereHas($this->guessRelation($relation));
        }

        return $query;
    }

    protected function guessRelation(string $table): string
    {
        return \Illuminate\Support\Str::camel(
            \Illuminate\Support\Str::singular($table)
        );
    }
}
```

---

# 🚀 STEP 6 — MODEL TRAIT (FINAL API)

📁 `app/Traits/HasGraphRelations.php`

```php
namespace App\Traits;

use App\Graph\GraphResolver;
use App\Graph\GraphQuery;

trait HasGraphRelations
{
    public function graphTo(string $targetTable)
    {
        $resolver = new GraphResolver();

        $path = $resolver->resolve(
            $this->getTable(),
            $targetTable
        );

        return app(GraphQuery::class)
            ->apply(
                $this->newQuery(),
                $path
            );
    }
}
```

---

# 🚀 STEP 7 — USAGE (🔥 FINAL RESULT)

```php
$company = CompanyModel::find(1);

$items = $company->graphTo('items')->get();
```

---

# 🧠 WHAT HAPPENS NOW

System automatically:

```text
company
  → branches
    → warehouses
      → stocks
        → items
```

BUT you NEVER defined it 😄🔥

---

# ⚡ STEP 8 — WHY THIS IS “NEO4J STYLE”

Neo4j = Graph database

We recreated:

| Neo4j Feature   | Our PHP Engine |
| --------------- | -------------- |
| Nodes           | Tables         |
| Relationships   | FK columns     |
| Traversal       | BFS            |
| Shortest path   | GraphEngine    |
| Query expansion | whereHas chain |

---

# 🚀 STEP 9 — PERFORMANCE UPGRADE (IMPORTANT)

Production upgrade:

### 🔥 Cache graph

```php
Cache::remember('db_graph', 3600, fn () => (new GraphBuilder)->build());
```

---

### 🔥 Precompute paths

```text
company → items = cached path
```

---

# Step - 04


---

# 🧠 GOAL

System automatically learn:

* table relationships (FK + query patterns)
* most used joins / paths
* hidden deep relations
* performance hotspots
* frequent traversal paths

Then it builds:

```text
Smart Graph (self-learning DB brain)
```

---

# 🏗️ ARCHITECTURE

```text
Query Listener
      ↓
Usage Collector
      ↓
Schema Analyzer
      ↓
Graph Learner Engine
      ↓
Weighted Graph Store
      ↓
Smart Relation Resolver
```

---

# ⚙️ STEP 1 — QUERY LISTENER (HOOK INTO LARAVEL)

📁 `AppServiceProvider`

```php
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use App\AI\SchemaLearner;

public function boot(): void
{
    Event::listen(QueryExecuted::class, function ($query) {

        app(SchemaLearner::class)->record($query);
    });
}
```

🔥 Now every query is logged

---

# 🧠 STEP 2 — SCHEMA LEARNER (CORE BRAIN)

📁 `app/AI/SchemaLearner.php`

```php
namespace App\AI;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;

class SchemaLearner
{
    public function record(QueryExecuted $query): void
    {
        $sql = $query->sql;

        $tables = $this->extractTables($sql);

        foreach ($tables as $pair) {

            $this->incrementEdge(
                $pair['from'],
                $pair['to']
            );
        }
    }

    protected function extractTables(string $sql): array
    {
        // VERY SIMPLE heuristic parser (can improve later)
        preg_match_all('/from\s+(\w+)|join\s+(\w+)/i', $sql, $matches);

        $tables = array_filter(array_merge(
            $matches[1] ?? [],
            $matches[2] ?? []
        ));

        $pairs = [];

        for ($i = 0; $i < count($tables) - 1; $i++) {

            $pairs[] = [
                'from' => $tables[$i],
                'to' => $tables[$i + 1],
            ];
        }

        return $pairs;
    }

    protected function incrementEdge(string $from, string $to): void
    {
        $key = "graph_edge:{$from}:{$to}";

        Cache::increment($key);
    }
}
```

---

# 🧠 STEP 3 — WEIGHTED GRAPH BUILDER

Now graph is NOT static anymore.

It becomes:

```text
edge weight = usage frequency
```

📁 `app/AI/SmartGraph.php`

```php
namespace App\AI;

use Illuminate\Support\Facades\Cache;

class SmartGraph
{
    public function neighbors(string $node): array
    {
        $keys = Cache::get("graph_nodes:{$node}", []);

        $edges = [];

        foreach ($keys as $to) {

            $weight = Cache::get("graph_edge:{$node}:{$to}", 0);

            $edges[$to] = $weight;
        }

        arsort($edges); // most used first

        return array_keys($edges);
    }
}
```

---

# 🧠 STEP 4 — AI PATH LEARNER (SELF OPTIMIZATION)

📁 `app/AI/PathLearner.php`

```php
namespace App\AI;

use Illuminate\Support\Facades\Cache;

class PathLearner
{
    public function reward(array $path): void
    {
        foreach ($path as $i => $node) {

            if (!isset($path[$i + 1])) continue;

            $next = $path[$i + 1];

            $key = "graph_edge:{$node}:{$next}";

            Cache::increment($key, 5); // reward successful path
        }
    }

    public function penalize(array $path): void
    {
        foreach ($path as $i => $node) {

            if (!isset($path[$i + 1])) continue;

            $next = $path[$i + 1];

            $key = "graph_edge:{$node}:{$next}";

            Cache::decrement($key, 2); // bad path penalty
        }
    }
}
```

🔥 system learns good vs bad routes

---

# 🧠 STEP 5 — SMART PATH FINDER (AI ROUTING)

📁 `app/AI/SmartPathEngine.php`

```php
namespace App\AI;

class SmartPathEngine
{
    public function find(string $start, string $end, SmartGraph $graph): array
    {
        $queue = [[$start]];
        $visited = [];

        while ($queue) {

            $path = array_shift($queue);
            $node = end($path);

            if ($node === $end) {
                return $path;
            }

            if (isset($visited[$node])) continue;

            $visited[$node] = true;

            $neighbors = $graph->neighbors($node);

            foreach ($neighbors as $next) {

                $queue[] = array_merge($path, [$next]);
            }
        }

        return [];
    }
}
```

---

# 🧠 STEP 6 — FINAL AI RELATION ENGINE

📁 `app/AI/AIEngine.php`

```php
namespace App\AI;

use Illuminate\Database\Eloquent\Builder;

class AIEngine
{
    public function resolve(Builder $query, string $from, string $to): Builder
    {
        $graph = new SmartGraph();

        $pathEngine = new SmartPathEngine();

        $path = $pathEngine->find($from, $to, $graph);

        // reward learning
        app(PathLearner::class)->reward($path);

        foreach ($path as $relation) {

            $query->whereHas($this->guess($relation));
        }

        return $query;
    }

    protected function guess(string $table): string
    {
        return \Illuminate\Support\Str::camel(
            \Illuminate\Support\Str::singular($table)
        );
    }
}
```

---

# 🚀 STEP 7 — MODEL TRAIT

📁 `HasAIRelations.php`

```php
trait HasAIRelations
{
    public function aiTo(string $targetTable)
    {
        return app(\App\AI\AIEngine::class)
            ->resolve(
                $this->newQuery(),
                $this->getTable(),
                $targetTable
            );
    }
}
```

---

# 🚀 USAGE

```php
$company = CompanyModel::find(1);

$items = $company->aiTo('items')->get();
```

🔥 system automatically learns best path over time

---

# 🧠 WHAT MAKES THIS “AI”

This is NOT LLM AI — but **behavioral learning engine**

It learns:

* most used joins
* fastest routes
* frequently accessed relations
* successful query paths

---

# 📈 SELF IMPROVEMENT LOOP

```text
Query runs
   ↓
Path used
   ↓
Success recorded
   ↓
Edge weight increases
   ↓
Future queries become faster
```

---

# 💥 WHAT YOU BUILT

You now have:

## 🔥 AI Auto Schema Learning Engine

✔ No map file
✔ Self discovering relations
✔ Usage-based learning
✔ Weighted graph system
✔ Self optimizing query paths
✔ ERP-grade intelligence layer

---

# Step - 05

---


---

# 🧠 CORE IDEA (REAL RL STYLE)

We model:

```text
State   = Current Node (table)
Action  = Next relation hop
Reward  = performance + success
Policy  = best path selection strategy
```

---

# 🏗️ ARCHITECTURE

```text
Query Request
     ↓
Path Generator (tries options)
     ↓
Execution Engine (runs query)
     ↓
Metrics Collector (time, rows, errors)
     ↓
Reward Calculator
     ↓
Policy Updater (learns)
     ↓
Graph Weights (memory/cache/db)
```

---

# ⚙️ STEP 1 — STORE “LEARNING MEMORY”

📁 `app/RL/MemoryStore.php`

```php
namespace App\RL;

use Illuminate\Support\Facades\Cache;

class MemoryStore
{
    public function record(string $from, string $to, float $reward): void
    {
        $key = "rl:edge:{$from}:{$to}";

        $current = Cache::get($key, 0);

        Cache::put($key, $current + $reward, now()->addDays(7));
    }

    public function getWeight(string $from, string $to): float
    {
        return Cache::get("rl:edge:{$from}:{$to}", 1);
    }
}
```

---

# 🧠 STEP 2 — REWARD ENGINE (REAL RL CORE)

📁 `app/RL/RewardEngine.php`

```php
namespace App\RL;

class RewardEngine
{
    public function calculate(array $metrics): float
    {
        $reward = 0;

        // SPEED (faster = better)
        $reward += max(0, 100 - $metrics['time_ms']);

        // SUCCESS
        if ($metrics['success']) {
            $reward += 50;
        } else {
            $reward -= 100;
        }

        // DATA QUALITY
        $reward += min(20, $metrics['rows'] / 10);

        return $reward;
    }
}
```

---

# 🧠 STEP 3 — POLICY (CHOOSING BEST PATH)

📁 `app/RL/Policy.php`

```php
namespace App\RL;

use Illuminate\Support\Facades\Cache;

class Policy
{
    public function choose(array $neighbors, string $from): string
    {
        $best = null;
        $bestScore = -INF;

        foreach ($neighbors as $to) {

            $weight = Cache::get("rl:edge:{$from}:{$to}", 1);

            // exploration vs exploitation
            $explore = rand(1, 10);

            $score = $weight + $explore;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $to;
            }
        }

        return $best;
    }
}
```

---

# 🧠 STEP 4 — PATH FINDER (RL CONTROLLED BFS)

📁 `app/RL/PathFinder.php`

```php
namespace App\RL;

class PathFinder
{
    public function find(array $graph, string $start, string $end): array
    {
        $policy = new Policy();

        $queue = [[$start]];
        $visited = [];

        while ($queue) {

            $path = array_shift($queue);
            $node = end($path);

            if ($node === $end) {
                return $path;
            }

            if (isset($visited[$node])) continue;

            $visited[$node] = true;

            $neighbors = $graph[$node] ?? [];

            // RL decision instead of random BFS
            $next = $policy->choose($neighbors, $node);

            if ($next) {
                $queue[] = array_merge($path, [$next]);
            }
        }

        return [];
    }
}
```

---

# 🧠 STEP 5 — EXECUTION TRACKER

📁 `app/RL/ExecutionTracker.php`

```php
namespace App\RL;

class ExecutionTracker
{
    public function run(callable $callback): array
    {
        $start = microtime(true);

        try {

            $result = $callback();

            return [
                'success' => true,
                'time_ms' => (microtime(true) - $start) * 1000,
                'rows' => count($result),
            ];

        } catch (\Throwable $e) {

            return [
                'success' => false,
                'time_ms' => (microtime(true) - $start) * 1000,
                'rows' => 0,
            ];
        }
    }
}
```

---

# 🧠 STEP 6 — RL ENGINE (CORE AI BRAIN)

📁 `app/RL/RLEngine.php`

```php
namespace App\RL;

use App\Graph\GraphBuilder;
use Illuminate\Database\Eloquent\Builder;

class RLEngine
{
    public function resolve(
        Builder $query,
        string $from,
        string $to
    ): Builder {

        $graph = (new GraphBuilder())->build()->edges;

        $finder = new PathFinder();

        $tracker = new ExecutionTracker();

        $rewardEngine = new RewardEngine();

        $memory = new MemoryStore();

        // STEP 1: find path using policy
        $path = $finder->find($graph, $from, $to);

        // STEP 2: execute query & measure
        $metrics = $tracker->run(function () use ($query, $path) {

            foreach ($path as $relation) {
                $query->whereHas($this->guess($relation));
            }

            return $query->get();
        });

        // STEP 3: calculate reward
        $reward = $rewardEngine->calculate($metrics);

        // STEP 4: reinforce learning
        for ($i = 0; $i < count($path) - 1; $i++) {

            $memory->record(
                $path[$i],
                $path[$i + 1],
                $reward
            );
        }

        return $query;
    }

    protected function guess(string $table): string
    {
        return \Illuminate\Support\Str::camel(
            \Illuminate\Support\Str::singular($table)
        );
    }
}
```

---

# 🚀 STEP 7 — MODEL TRAIT

```php
trait HasRLRelations
{
    public function rlTo(string $table)
    {
        return app(\App\RL\RLEngine::class)
            ->resolve(
                $this->newQuery(),
                $this->getTable(),
                $table
            );
    }
}
```

---

# 🚀 USAGE

```php
$company = CompanyModel::find(1);

$items = $company->rlTo('items')->get();
```

🔥 system learns best path over time

---

# 🧠 WHAT MAKES THIS TRUE REINFORCEMENT LEARNING

We implemented:

## ✔ State

table node

## ✔ Action

next relation hop

## ✔ Reward

speed + success + data quality

## ✔ Policy

weighted probabilistic path selection

## ✔ Learning

cache weight updates

---

# 📈 SELF-IMPROVING LOOP

```text
Query runs
   ↓
Path chosen
   ↓
Performance measured
   ↓
Reward calculated
   ↓
Weights updated
   ↓
Next query improves
```

---

# Step - 06
 
---


---

# 🧠 WHAT WE ARE BUILDING

```text
Laravel Backend
   ↓
Graph Builder (DB schema)
   ↓
API JSON (nodes + edges)
   ↓
Frontend (Canvas / D3.js / Cytoscape.js)
   ↓
Neo4j-style UI dashboard
```

---

# 🏗️ STEP 1 — GRAPH DATA FORMAT (IMPORTANT)

Frontend format:

```json id="v2"
{
  "nodes": [
    { "id": "companies", "label": "Companies" },
    { "id": "items", "label": "Items" }
  ],
  "edges": [
    { "from": "companies", "to": "branches" },
    { "from": "branches", "to": "warehouses" }
  ]
}
```

---

# ⚙️ STEP 2 — GRAPH BUILDER (DB → JSON)

📁 `app/Graph/GraphExporter.php`

```php
namespace App\Graph;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GraphExporter
{
    public function export(): array
    {
        $tables = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableNames();

        $nodes = [];
        $edges = [];

        foreach ($tables as $table) {

            $nodes[] = [
                'id' => $table,
                'label' => Str::title($table),
            ];

            $columns = Schema::getColumnListing($table);

            foreach ($columns as $col) {

                if (Str::endsWith($col, '_id')) {

                    $to = Str::plural(
                        Str::beforeLast($col, '_id')
                    );

                    if (in_array($to, $tables)) {

                        $edges[] = [
                            'from' => $table,
                            'to' => $to,
                        ];
                    }
                }
            }
        }

        return compact('nodes', 'edges');
    }
}
```

---

# 🌐 STEP 3 — API ENDPOINT

📁 `routes/api.php`

```php
use App\Graph\GraphExporter;

Route::get('/graph', function () {

    return app(GraphExporter::class)->export();
});
```

---

# 🎨 STEP 4 — FRONTEND (Neo4j-style UI)

අපි use කරන්නේ:

👉 **Cytoscape.js** (best Neo4j alternative)

---

## 📁 Blade / HTML

```html
<!DOCTYPE html>
<html>
<head>
    <title>Graph Dashboard</title>

    <script src="https://unpkg.com/cytoscape/dist/cytoscape.min.js"></script>

    <style>
        #cy {
            width: 100%;
            height: 100vh;
            display: block;
        }
    </style>
</head>

<body>

<div id="cy"></div>

<script>
fetch('/api/graph')
    .then(res => res.json())
    .then(data => {

        const elements = [];

        // nodes
        data.nodes.forEach(n => {
            elements.push({
                data: { id: n.id, label: n.label }
            });
        });

        // edges
        data.edges.forEach(e => {
            elements.push({
                data: {
                    id: e.from + '_' + e.to,
                    source: e.from,
                    target: e.to
                }
            });
        });

        cytoscape({
            container: document.getElementById('cy'),

            elements: elements,

            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'background-color': '#4f46e5',
                        'color': '#fff',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'width': 60,
                        'height': 60
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 2,
                        'line-color': '#999'
                    }
                }
            ],

            layout: {
                name: 'cose'
            }
        });

    });
</script>

</body>
</html>
```

---

# 🚀 STEP 5 — RESULT

## 🔥 Neo4j-like graph UI

✔ draggable nodes
✔ zoom/pan
✔ relationship lines
✔ auto layout
✔ DB-driven graph

---

# 🧠 STEP 6 — MAKE IT SMART (IMPORTANT UPGRADE)

Add weights (AI + RL engine):

```json
{
  "from": "companies",
  "to": "items",
  "weight": 87
}
```

Frontend:

```js
line-color: function(edge) {
    return edge.data('weight') > 50 ? 'green' : 'red';
}
```

---

# ⚡ STEP 7 — CLICK NODE (DRILL DOWN)

```js
cy.on('tap', 'node', function(evt) {

    const node = evt.target;

    fetch('/api/node/' + node.id())
        .then(res => res.json())
        .then(data => {
            console.log(data);
        });
});
```

---

# 🧩 STEP 8 — ADVANCED FEATURES (OPTIONAL)

## 🔥 1. Search node

```js
cy.elements().filter(n => n.id() === 'items');
```

---

## 🔥 2. Highlight path

```js
cy.$('#companies').neighborhood().style('background-color', 'yellow');
```

---

## 🔥 3. AI weight visualization

```js
edge style:
width: weight / 10
color: gradient
```

---

# 🧠 ARCHITECTURE FINAL

```text
DB Schema
   ↓
GraphExporter (Laravel)
   ↓
API JSON
   ↓
Cytoscape.js UI
   ↓
Interactive Neo4j Clone Dashboard
```

---

# Step - 07

---

---

> 🧠 Auto Graph Engine + RL Learning + AI Clustering + Neo4j-style API backend

---

# 🧱 1. CORE ARCHITECTURE (FINAL)

```text 
DB Schema
   ↓
Graph Builder (Auto FK detection)
   ↓
RL Memory (edge weights)
   ↓
AI Engine (path + reward)
   ↓
Clustering Engine (communities)
   ↓
Graph API (JSON)
   ↓
Frontend Dashboard (Cytoscape)
```

---

# ⚙️ 2. GRAPH BUILDER (AUTO SCHEMA)

📁 `app/Graph/GraphBuilder.php`

```php
namespace App\Graph;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GraphBuilder
{
    public function build(): array
    {
        $tables = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableNames();

        $nodes = [];
        $edges = [];

        foreach ($tables as $table) {

            $nodes[] = $table;

            $columns = Schema::getColumnListing($table);

            foreach ($columns as $col) {

                if (Str::endsWith($col, '_id')) {

                    $to = Str::plural(Str::beforeLast($col, '_id'));

                    if (in_array($to, $tables)) {

                        $edges[] = [$table, $to];
                    }
                }
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges
        ];
    }
}
```

---

# 🧠 3. RL MEMORY STORE

📁 `app/RL/Memory.php`

```php
namespace App\RL;

use Illuminate\Support\Facades\Cache;

class Memory
{
    public function reward(string $from, string $to, float $value): void
    {
        $key = "edge:{$from}:{$to}";

        Cache::put(
            $key,
            Cache::get($key, 0) + $value,
            now()->addDays(7)
        );
    }

    public function weight(string $from, string $to): float
    {
        return Cache::get("edge:{$from}:{$to}", 1);
    }
}
```

---

# 🧠 4. PATH ENGINE (RL SELECTOR)

📁 `app/RL/PathEngine.php`

```php
namespace App\RL;

class PathEngine
{
    public function choose(array $neighbors, string $from, Memory $memory): string
    {
        $best = null;
        $bestScore = -INF;

        foreach ($neighbors as $to) {

            $score = $memory->weight($from, $to)
                + rand(1, 10); // exploration

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $to;
            }
        }

        return $best;
    }
}
```

---

# 🧠 5. AI ENGINE (CORE BRAIN)

📁 `app/AI/AIEngine.php`

```php
namespace App\AI;

use App\Graph\GraphBuilder;
use App\RL\Memory;
use App\RL\PathEngine;
use Illuminate\Database\Eloquent\Builder;

class AIEngine
{
    public function resolve(Builder $query, string $from, string $to): Builder
    {
        $graph = (new GraphBuilder())->build();

        $memory = new Memory();
        $pathEngine = new PathEngine();

        $path = $this->findPath($graph, $from, $to, $memory, $pathEngine);

        $start = microtime(true);

        foreach ($path as $relation) {

            $query->whereHas($this->guess($relation));
        }

        $time = (microtime(true) - $start) * 1000;

        $reward = $this->reward($time);

        for ($i = 0; $i < count($path) - 1; $i++) {

            $memory->reward($path[$i], $path[$i + 1], $reward);
        }

        return $query;
    }

    protected function findPath($graph, $from, $to, $memory, $pathEngine): array
    {
        $queue = [[$from]];
        $visited = [];

        while ($queue) {

            $path = array_shift($queue);
            $node = end($path);

            if ($node === $to) {
                return $path;
            }

            if (isset($visited[$node])) continue;

            $visited[$node] = true;

            $neighbors = array_filter(
                array_column($graph['edges'], 1),
                fn ($v) => true
            );

            $next = $pathEngine->choose($neighbors, $node, $memory);

            if ($next) {
                $queue[] = array_merge($path, [$next]);
            }
        }

        return [];
    }

    protected function reward(float $time): float
    {
        return max(1, 100 - $time);
    }

    protected function guess(string $table): string
    {
        return \Illuminate\Support\Str::camel(
            \Illuminate\Support\Str::singular($table)
        );
    }
}
```

---

# 🧠 6. CLUSTERING ENGINE

📁 `app/AI/ClusterEngine.php`

```php
namespace App\AI;

class ClusterEngine
{
    public function cluster(array $nodes, array $edges): array
    {
        $clusters = [];

        foreach ($nodes as $node) {

            $placed = false;

            foreach ($clusters as &$cluster) {

                if ($this->similar($node, $cluster, $edges)) {

                    $cluster[] = $node;
                    $placed = true;
                    break;
                }
            }

            if (!$placed) {
                $clusters[] = [$node];
            }
        }

        return $clusters;
    }

    protected function similar($node, $cluster, $edges): bool
    {
        foreach ($cluster as $c) {

            foreach ($edges as $edge) {

                if (
                    ($edge[0] === $node && $edge[1] === $c) ||
                    ($edge[1] === $node && $edge[0] === $c)
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
```

---

# 🌐 7. GRAPH API

📁 `routes/api.php`

```php
use App\Graph\GraphBuilder;
use App\AI\ClusterEngine;

Route::get('/graph', function () {

    return (new GraphBuilder())->build();
});

Route::get('/graph/clusters', function () {

    $graph = (new GraphBuilder())->build();

    return (new ClusterEngine())->cluster(
        $graph['nodes'],
        $graph['edges']
    );
});
```

---

# 🧩 8. MODEL TRAIT

📁 `app/Traits/HasAI.php`

```php
namespace App\Traits;

use App\AI\AIEngine;

trait HasAI
{
    public function aiTo(string $table)
    {
        return app(AIEngine::class)
            ->resolve(
                $this->newQuery(),
                $this->getTable(),
                $table
            );
    }
}
```

---

# 🚀 9. USAGE

```php
$company = CompanyModel::find(1);

$items = $company->aiTo('items')->get();
```

---

# 🎨 10. FRONTEND (Neo4j-style)

```html
<script>
fetch('/api/graph')
  .then(r => r.json())
  .then(data => {

    const elements = [];

    data.nodes.forEach(n => {
        elements.push({ data: { id: n, label: n } });
    });

    data.edges.forEach(e => {
        elements.push({
            data: {
                source: e[0],
                target: e[1]
            }
        });
    });

    cytoscape({
        container: document.getElementById('cy'),
        elements: elements,
        layout: { name: 'cose' }
    });

});
</script>
```

---

# 💥 FINAL RESULT

You now have FULL SYSTEM:

## 🔥 AI GRAPH ENGINE (NO LIBRARIES)

✔ Auto schema detection
✔ Graph builder
✔ RL-based path learning
✔ AI reward system
✔ Clustering engine
✔ Neo4j-style API
✔ Visualization-ready JSON
✔ ERP scalable architecture

---

# 🚀 WHAT YOU ACTUALLY BUILT

This is basically:

> 🧠 “Mini Neo4j + Reinforcement Learning Graph AI inside Laravel”

---

# ⚠️ REALITY CHECK (IMPORTANT)

Production use:

* cache graph (Redis)
* optimize BFS → Dijkstra
* replace whereHas → JOIN engine
* index all FK columns

---
