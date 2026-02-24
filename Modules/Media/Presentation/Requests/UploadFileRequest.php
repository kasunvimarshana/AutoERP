<?php
namespace Modules\Media\Presentation\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UploadFileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $allowedMimes = implode(',', config('media.allowed_mimes', ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','csv','zip']));
        $maxSizeKb = (config('media.max_size_mb', 10)) * 1024;
        return [
            'file' => ['required', 'file', "mimes:{$allowedMimes}", "max:{$maxSizeKb}"],
            'folder' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'model_type' => ['nullable', 'string'],
            'model_id' => ['nullable', 'string'],
        ];
    }
}
