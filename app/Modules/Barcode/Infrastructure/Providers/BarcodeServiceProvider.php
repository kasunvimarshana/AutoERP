<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Barcode\Application\Contracts\GenerateBarcodeServiceInterface;
use Modules\Barcode\Application\Contracts\ManageBarcodeServiceInterface;
use Modules\Barcode\Application\Contracts\ManageLabelTemplateServiceInterface;
use Modules\Barcode\Application\Contracts\PrintBarcodeLabelServiceInterface;
use Modules\Barcode\Application\Contracts\RecordBarcodeScanServiceInterface;
use Modules\Barcode\Application\Services\GenerateBarcodeService;
use Modules\Barcode\Application\Services\ManageBarcodeService;
use Modules\Barcode\Application\Services\ManageLabelTemplateService;
use Modules\Barcode\Application\Services\PrintBarcodeLabelService;
use Modules\Barcode\Application\Services\RecordBarcodeScanService;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodePrintJobRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeScanRepositoryInterface;
use Modules\Barcode\Domain\RepositoryInterfaces\LabelTemplateRepositoryInterface;
use Modules\Barcode\Domain\ValueObjects\BarcodeType;
use Modules\Barcode\Infrastructure\Generators\BarcodeGeneratorDispatcher;
use Modules\Barcode\Infrastructure\Generators\Drivers\AztecDriver;
use Modules\Barcode\Infrastructure\Generators\Drivers\CodabarDriver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Code128Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Code39Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Code93Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\DataMatrixDriver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Ean13Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Ean8Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Interleaved2of5Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Itf14Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\MsiDriver;
use Modules\Barcode\Infrastructure\Generators\Drivers\Pdf417Driver;
use Modules\Barcode\Infrastructure\Generators\Drivers\QrCodeDriver;
use Modules\Barcode\Infrastructure\Generators\Drivers\UpcADriver;
use Modules\Barcode\Infrastructure\Generators\Drivers\UpcEDriver;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\BarcodePrintJobModel;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\BarcodeDefinitionModel;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\BarcodeScanModel;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\LabelTemplateModel;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories\EloquentBarcodePrintJobRepository;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories\EloquentBarcodeDefinitionRepository;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories\EloquentBarcodeScanRepository;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories\EloquentLabelTemplateRepository;
use Modules\Barcode\Infrastructure\Printing\BarcodePrinterDispatcher;
use Modules\Barcode\Infrastructure\Printing\Drivers\EplPrinterDriver;
use Modules\Barcode\Infrastructure\Printing\Drivers\SvgLabelDriver;
use Modules\Barcode\Infrastructure\Printing\Drivers\ZplPrinterDriver;

class BarcodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Generator Dispatcher ───────────────────────────────────────────────
        $this->app->singleton(BarcodeGeneratorDispatcher::class, function () {
            $dispatcher = new BarcodeGeneratorDispatcher();
            $dispatcher->addDriver(BarcodeType::CODE128,        new Code128Driver());
            $dispatcher->addDriver(BarcodeType::CODE39,         new Code39Driver());
            $dispatcher->addDriver(BarcodeType::CODE93,         new Code93Driver());
            $dispatcher->addDriver(BarcodeType::EAN13,          new Ean13Driver());
            $dispatcher->addDriver(BarcodeType::EAN8,           new Ean8Driver());
            $dispatcher->addDriver(BarcodeType::UPCA,           new UpcADriver());
            $dispatcher->addDriver(BarcodeType::UPCE,           new UpcEDriver());
            $dispatcher->addDriver(BarcodeType::ITF14,          new Itf14Driver());
            $dispatcher->addDriver(BarcodeType::CODABAR,        new CodabarDriver());
            $dispatcher->addDriver(BarcodeType::MSI,            new MsiDriver());
            $dispatcher->addDriver(BarcodeType::INTERLEAVED2OF5, new Interleaved2of5Driver());
            $dispatcher->addDriver(BarcodeType::QR,             new QrCodeDriver());
            $dispatcher->addDriver(BarcodeType::DATAMATRIX,     new DataMatrixDriver());
            $dispatcher->addDriver(BarcodeType::PDF417,         new Pdf417Driver());
            $dispatcher->addDriver(BarcodeType::AZTEC,          new AztecDriver());
            return $dispatcher;
        });

        // ── Repository Bindings ────────────────────────────────────────────────
        $this->app->bind(BarcodeDefinitionRepositoryInterface::class, fn($app) =>
            new EloquentBarcodeDefinitionRepository($app->make(BarcodeDefinitionModel::class))
        );

        $this->app->bind(BarcodeScanRepositoryInterface::class, fn($app) =>
            new EloquentBarcodeScanRepository($app->make(BarcodeScanModel::class))
        );

        $this->app->bind(LabelTemplateRepositoryInterface::class, fn($app) =>
            new EloquentLabelTemplateRepository($app->make(LabelTemplateModel::class))
        );

        $this->app->bind(BarcodePrintJobRepositoryInterface::class, fn($app) =>
            new EloquentBarcodePrintJobRepository($app->make(BarcodePrintJobModel::class))
        );

        // ── Printer Dispatcher ─────────────────────────────────────────────────
        $this->app->singleton(BarcodePrinterDispatcher::class, function ($app) {
            $dispatcher = new BarcodePrinterDispatcher();
            $dispatcher->addDriver(new ZplPrinterDriver());
            $dispatcher->addDriver(new EplPrinterDriver());
            $dispatcher->addDriver(new SvgLabelDriver($app->make(BarcodeGeneratorDispatcher::class)));
            return $dispatcher;
        });

        // ── Service Bindings ───────────────────────────────────────────────────
        $this->app->bind(ManageBarcodeServiceInterface::class, fn($app) =>
            new ManageBarcodeService($app->make(BarcodeDefinitionRepositoryInterface::class))
        );

        $this->app->bind(GenerateBarcodeServiceInterface::class, fn($app) =>
            new GenerateBarcodeService($app->make(BarcodeGeneratorDispatcher::class))
        );

        $this->app->bind(RecordBarcodeScanServiceInterface::class, fn($app) =>
            new RecordBarcodeScanService(
                $app->make(BarcodeScanRepositoryInterface::class),
                $app->make(BarcodeDefinitionRepositoryInterface::class),
            )
        );

        $this->app->bind(ManageLabelTemplateServiceInterface::class, fn($app) =>
            new ManageLabelTemplateService($app->make(LabelTemplateRepositoryInterface::class))
        );

        $this->app->bind(PrintBarcodeLabelServiceInterface::class, fn($app) =>
            new PrintBarcodeLabelService(
                $app->make(BarcodePrintJobRepositoryInterface::class),
                $app->make(BarcodeDefinitionRepositoryInterface::class),
                $app->make(LabelTemplateRepositoryInterface::class),
                $app->make(BarcodePrinterDispatcher::class),
            )
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
    }
}
