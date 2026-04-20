Domain/Entities/<Entity>.php            # Pure PHP, no framework
Domain/Exceptions/<Entity>NotFoundException.php
Domain/RepositoryInterfaces/<Entity>RepositoryInterface.php
Application/DTOs/<Entity>Data.php       # fromArray() factory
Application/Contracts/{Create,Find,Update,Delete}<Entity>ServiceInterface.php
Application/Services/{Create,Find,Update,Delete}<Entity>Service.php
Infrastructure/Persistence/Eloquent/Models/<Entity>Model.php
Infrastructure/Persistence/Eloquent/Repositories/Eloquent<Entity>Repository.php
Infrastructure/Http/Requests/{Store,Update,List}<Entity>Request.php
Infrastructure/Http/Resources/<Entity>Resource.php + <Entity>Collection.php
Infrastructure/Http/Controllers/<Entity>Controller.php