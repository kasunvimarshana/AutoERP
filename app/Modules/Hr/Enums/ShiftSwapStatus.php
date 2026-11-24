<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum ShiftSwapStatus: string
{
    case PendingPeer = 'pending_peer';
    case PeerAccepted = 'peer_accepted';
    case PeerRejected = 'peer_rejected';
    case ManagerApproved = 'manager_approved';
    case ManagerRejected = 'manager_rejected';
    case Cancelled = 'cancelled';
}
