<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Hr\Http\Requests\EmployeeRelationRequest;
use Modules\Hr\Http\Requests\ListEmployeeRequest;
use Modules\Hr\Http\Resources\EmployeeAddressResource;
use Modules\Hr\Http\Resources\EmployeeAvailabilityResource;
use Modules\Hr\Http\Resources\EmployeeCertificationAssignmentResource;
use Modules\Hr\Http\Resources\EmployeeContactResource;
use Modules\Hr\Http\Resources\EmployeeDocumentResource;
use Modules\Hr\Http\Resources\EmployeeLicenseAssignmentResource;
use Modules\Hr\Http\Resources\EmployeeRateResource;
use Modules\Hr\Http\Resources\EmployeeSkillAssignmentResource;
use Modules\Hr\Http\Resources\EmployeeStatusHistoryResource;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\EmployeeAddressService;
use Modules\Hr\Services\EmployeeAvailabilityService;
use Modules\Hr\Services\EmployeeCertificationService;
use Modules\Hr\Services\EmployeeContactService;
use Modules\Hr\Services\EmployeeDocumentService;
use Modules\Hr\Services\EmployeeLicenseService;
use Modules\Hr\Services\EmployeeQueryService;
use Modules\Hr\Services\EmployeeRateService;
use Modules\Hr\Services\EmployeeRelationQueryService;
use Modules\Hr\Services\EmployeeSkillService;

final class EmployeeRelationController
{
    public function __construct(private readonly EmployeeQueryService $employees, private readonly EmployeeRelationQueryService $relations, private readonly EmployeeContactService $contacts, private readonly EmployeeAddressService $addresses, private readonly EmployeeDocumentService $documents, private readonly EmployeeSkillService $skills, private readonly EmployeeCertificationService $certifications, private readonly EmployeeLicenseService $licenses, private readonly EmployeeRateService $rates, private readonly EmployeeAvailabilityService $availability) {}

    public function contacts(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeContactResource::collection($this->relations->paginate($this->employee($r, $employee), 'contacts', $r->perPage())); }
    public function storeContact(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeContactResource($this->contacts->create($this->employee($r, $employee), $r->contactData($r->validated())))); }
    public function updateContact(EmployeeRelationRequest $r, int $employee, int $contact): JsonResource { $e = $this->employee($r, $employee); return new EmployeeContactResource($this->contacts->update($e, $this->relations->find($e, 'contacts', $contact), $r->contactData($r->validated()))); }
    public function destroyContact(ListEmployeeRequest $r, int $employee, int $contact): JsonResponse { $e = $this->employee($r, $employee); $this->contacts->delete($e, $this->relations->find($e, 'contacts', $contact)); return response()->json(null, 204); }
    public function addresses(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeAddressResource::collection($this->relations->paginate($this->employee($r, $employee), 'addresses', $r->perPage())); }
    public function storeAddress(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeAddressResource($this->addresses->create($this->employee($r, $employee), $r->addressData($r->validated())))); }
    public function updateAddress(EmployeeRelationRequest $r, int $employee, int $address): JsonResource { $e = $this->employee($r, $employee); return new EmployeeAddressResource($this->addresses->update($e, $this->relations->find($e, 'addresses', $address), $r->addressData($r->validated()))); }
    public function destroyAddress(ListEmployeeRequest $r, int $employee, int $address): JsonResponse { $e = $this->employee($r, $employee); $this->addresses->delete($e, $this->relations->find($e, 'addresses', $address)); return response()->json(null, 204); }
    public function documents(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeDocumentResource::collection($this->relations->paginate($this->employee($r, $employee), 'documents', $r->perPage())); }
    public function storeDocument(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeDocumentResource($this->documents->create($this->employee($r, $employee), $r->documentData($r->validated())))); }
    public function updateDocument(EmployeeRelationRequest $r, int $employee, int $document): JsonResource { $e = $this->employee($r, $employee); return new EmployeeDocumentResource($this->documents->update($e, $this->relations->find($e, 'documents', $document), $r->documentData($r->validated()))); }
    public function destroyDocument(ListEmployeeRequest $r, int $employee, int $document): JsonResponse { $e = $this->employee($r, $employee); $this->documents->delete($e, $this->relations->find($e, 'documents', $document)); return response()->json(null, 204); }
    public function skills(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeSkillAssignmentResource::collection($this->relations->paginate($this->employee($r, $employee), 'skillAssignments', $r->perPage())); }
    public function storeSkill(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeSkillAssignmentResource($this->skills->create($this->employee($r, $employee), $r->skillData($r->validated()))->load('skill'))); }
    public function updateSkill(EmployeeRelationRequest $r, int $employee, int $assignment): JsonResource { $e = $this->employee($r, $employee); return new EmployeeSkillAssignmentResource($this->skills->update($e, $this->relations->find($e, 'skillAssignments', $assignment), $r->skillData($r->validated()))); }
    public function destroySkill(ListEmployeeRequest $r, int $employee, int $assignment): JsonResponse { $e = $this->employee($r, $employee); $this->skills->delete($e, $this->relations->find($e, 'skillAssignments', $assignment)); return response()->json(null, 204); }
    public function certifications(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeCertificationAssignmentResource::collection($this->relations->paginate($this->employee($r, $employee), 'certificationAssignments', $r->perPage())); }
    public function storeCertification(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeCertificationAssignmentResource($this->certifications->create($this->employee($r, $employee), $r->certificationData($r->validated()))->load('certification'))); }
    public function updateCertification(EmployeeRelationRequest $r, int $employee, int $assignment): JsonResource { $e = $this->employee($r, $employee); return new EmployeeCertificationAssignmentResource($this->certifications->update($e, $this->relations->find($e, 'certificationAssignments', $assignment), $r->certificationData($r->validated()))); }
    public function destroyCertification(ListEmployeeRequest $r, int $employee, int $assignment): JsonResponse { $e = $this->employee($r, $employee); $this->certifications->delete($e, $this->relations->find($e, 'certificationAssignments', $assignment)); return response()->json(null, 204); }
    public function licenses(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeLicenseAssignmentResource::collection($this->relations->paginate($this->employee($r, $employee), 'licenseAssignments', $r->perPage())); }
    public function storeLicense(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeLicenseAssignmentResource($this->licenses->create($this->employee($r, $employee), $r->licenseData($r->validated()))->load('license'))); }
    public function updateLicense(EmployeeRelationRequest $r, int $employee, int $assignment): JsonResource { $e = $this->employee($r, $employee); return new EmployeeLicenseAssignmentResource($this->licenses->update($e, $this->relations->find($e, 'licenseAssignments', $assignment), $r->licenseData($r->validated()))); }
    public function destroyLicense(ListEmployeeRequest $r, int $employee, int $assignment): JsonResponse { $e = $this->employee($r, $employee); $this->licenses->delete($e, $this->relations->find($e, 'licenseAssignments', $assignment)); return response()->json(null, 204); }
    public function rates(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeRateResource::collection($this->relations->paginate($this->employee($r, $employee), 'rates', $r->perPage())); }
    public function storeRate(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeRateResource($this->rates->create($this->employee($r, $employee), $r->rateData($r->validated()))->load('currency'))); }
    public function updateRate(EmployeeRelationRequest $r, int $employee, int $rate): JsonResource { $e = $this->employee($r, $employee); return new EmployeeRateResource($this->rates->update($e, $this->relations->find($e, 'rates', $rate), $r->rateData($r->validated()))); }
    public function destroyRate(ListEmployeeRequest $r, int $employee, int $rate): JsonResponse { $e = $this->employee($r, $employee); $this->rates->delete($e, $this->relations->find($e, 'rates', $rate)); return response()->json(null, 204); }
    public function availability(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeAvailabilityResource::collection($this->relations->paginate($this->employee($r, $employee), 'availabilities', $r->perPage())); }
    public function storeAvailability(EmployeeRelationRequest $r, int $employee): JsonResponse { return $this->created(new EmployeeAvailabilityResource($this->availability->create($this->employee($r, $employee), $r->availabilityData($r->validated())))); }
    public function statusHistory(ListEmployeeRequest $r, int $employee): AnonymousResourceCollection { return EmployeeStatusHistoryResource::collection($this->relations->paginate($this->employee($r, $employee), 'statusHistories', $r->perPage())); }
    private function employee(ListEmployeeRequest|EmployeeRelationRequest $r, int $id): HrEmployee { return $this->employees->employee($id, $r->tenantId(), $r->organizationUnitId()); }
    private function created(JsonResource $resource): JsonResponse { return $resource->response()->setStatusCode(201); }
}
