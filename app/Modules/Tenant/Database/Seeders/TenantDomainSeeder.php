<?php

declare(strict_types=1);
namespace Modules\Tenant\Database\Seeders;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Models\TenantDomainModel;
final class TenantDomainSeeder extends Seeder
{
    use ResolvesSeedContext;
    public function run():void
    {
        if(!Schema::hasTable('tenant_domains'))return;
        $tenant=$this->defaultTenant();if($tenant===null)return;
        $raw=trim((string)env('AUTOERP_TENANT_DOMAINS',''));
        if($raw===''&&app()->environment(['local','testing']))$raw='localhost,127.0.0.1,autoerp.local,autoerp.test';
        $domains=array_values(array_unique(array_filter(array_map(fn(string $v):string=>strtolower(rtrim(trim($v),'.')),explode(',',$raw)))));
        if($domains===[])return;
        DB::transaction(function()use($tenant,$domains):void{
            foreach($domains as $index=>$domain){TenantDomainModel::query()->updateOrCreate(['domain'=>$domain],[
                'tenant_id'=>$tenant->getKey(),'is_primary'=>$index===0,'primary_marker'=>$index===0?'primary':null,
                'status'=>'active','verification_method'=>'dns_txt','verified_at'=>now(),'row_version'=>1,
                'metadata'=>['seed_source'=>'tenant_module','development_domain'=>app()->environment(['local','testing'])],
            ]);}
        },3);
    }
}
