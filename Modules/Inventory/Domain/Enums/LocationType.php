<?php
namespace Modules\Inventory\Domain\Enums;
enum LocationType: string
{
    case Receive = 'receive';
    case BulkStorage = 'bulk_storage';
    case PickFace = 'pick_face';
    case Output = 'output';
    case Scrap = 'scrap';
    case Transit = 'transit';
    case Virtual = 'virtual';
    case QualityControl = 'quality_control';
}
